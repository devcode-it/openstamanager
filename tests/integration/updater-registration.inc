<?php

/**
 * Standalone integration regression for the real OpenSTAManager Update class.
 * Requires PHP 8.3 with pdo_mysql and a DISPOSABLE MySQL/MariaDB database.
 * No production application, migration contents, dependencies or credentials are loaded.
 *
 * OSM_TEST_DSN=mysql:host=db;dbname=osm_updater_lab;charset=utf8mb4
 * OSM_TEST_USER=root OSM_TEST_PASSWORD=... php tests/integration/updater-registration.inc
 * An optional source-file argument permits running the same checks against a baseline.
 * The schema fixture always comes from this checkout unless OSM_TEST_SCHEMA is set.
 * Optional: OSM_EXPECT=baseline records known failures without failing the command.
 */

declare(strict_types=1);

final class LabDatabase
{
    private PDO $pdo;
    private int $inserts = 0;

    public function __construct()
    {
        $dsn = getenv('OSM_TEST_DSN') ?: '';
        if (!preg_match('/(?:^|;)dbname=osm_updater_lab[a-zA-Z0-9_]*(?:;|$)/', $dsn)) {
            throw new RuntimeException('OSM_TEST_DSN must name a disposable osm_updater_lab* database.');
        }
        $this->pdo = new PDO($dsn, getenv('OSM_TEST_USER') ?: 'root', getenv('OSM_TEST_PASSWORD') ?: '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        if (!preg_match('/^osm_updater_lab[a-zA-Z0-9_]*$/D', (string) $this->pdo->query('SELECT DATABASE()')->fetchColumn())) {
            throw new RuntimeException('Connected database must be a disposable osm_updater_lab* database.');
        }
    }

    public function isConnected(): bool { return true; }
    public function isInstalled(): bool { return true; }
    public function getPDO(): PDO { return $this->pdo; }
    public function getDatabaseName(): string { return (string) $this->pdo->query('SELECT DATABASE()')->fetchColumn(); }
    public function getMySQLVersion(): string { return (string) $this->pdo->query('SELECT VERSION()')->fetchColumn(); }
    public function tableExists(string $table): bool
    {
        return (bool) $this->fetchOne('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]);
    }
    public function query(string $query, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        return $statement;
    }
    public function fetchArray(string $query, array $params = []): array
    {
        $rows = $this->query($query, $params)->fetchAll();
        if (trim($query) === 'SELECT * FROM `updates`' && getenv('OSM_LAB_ADVANCE_AFTER_SNAPSHOT') === '1') {
            // Independent executor progresses a migration after registration's read.
            // Use another connection so the registration snapshot remains stale.
            $executor = new self();
            $executor->query('UPDATE updates SET done = 1 WHERE id = 1001');
            putenv('OSM_LAB_ADVANCE_AFTER_SNAPSHOT=0');
        }
        return $rows;
    }
    public function fetchOne(string $query, array $params = []): array|false { return $this->query($query.' LIMIT 1', $params)->fetch(); }
    public function multiQuery(string $file): void { $this->pdo->exec(file_get_contents($file)); }
    public function insert(string $table, array $data): void
    {
        $fields = array_keys($data);
        $this->query('INSERT INTO `'.$table.'` (`'.implode('`, `', $fields).'`) VALUES ('.implode(', ', array_fill(0, count($fields), '?')).')', array_values($data));
        ++$this->inserts;
        $pause = (int) (getenv('OSM_LAB_INSERT_DELAY_US') ?: 0);
        if ($pause > 0) {
            usleep($pause);
        }
        $failAfter = (int) (getenv('OSM_FAIL_AFTER') ?: 0);
        if ($failAfter && $this->inserts === $failAfter) {
            // Emulate abrupt PHP process termination: no catch/finally or rollback is run.
            exit(75);
        }
        if (getenv('OSM_LAB_THROW_AFTER_INSERT') === '1') {
            throw new RuntimeException('Injected registration failure');
        }
    }
    public function beginTransaction(): bool { return $this->pdo->beginTransaction(); }
    public function commitTransaction(): bool { return $this->pdo->commit(); }
    public function rollbackTransaction(): bool { return $this->pdo->inTransaction() ? $this->pdo->rollBack() : false; }
}

function database(): LabDatabase { return $GLOBALS['labDatabase'] ??= new LabDatabase(); }
function base_dir(): string { return $GLOBALS['labFixture']; }
function prepare(mixed $value): string { return $value === null ? 'NULL' : database()->getPDO()->quote((string) $value); }
function string_contains(string $string, string $needle): bool { return str_contains($string, $needle); }
function tr(string $message): string { return $message; }

function resetUpdateCache(): void
{
    foreach (['updates', 'current_version'] as $name) {
        $property = new ReflectionProperty(Update::class, $name);
        $property->setValue(null, null);
    }
}

function createFixture(string $name, int $oldCount = 354, int $newCount = 1): array
{
    $fixture = $GLOBALS['labRuntime'].'/'.$name;
    if (is_dir($fixture)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $path) {
            $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }
    }
    foreach (['update', 'modules/lab_component/update', 'plugins/lab_plugin/update'] as $directory) {
        if (!is_dir($fixture.'/'.$directory)) {
            mkdir($fixture.'/'.$directory, 0777, true);
        }
    }
    $schema = getenv('OSM_TEST_SCHEMA') ?: dirname(__DIR__, 2).'/update/create_updates.sql';
    copy($schema, $fixture.'/update/create_updates.sql');
    $GLOBALS['labFixture'] = $fixture;
    database()->query('DROP TABLE IF EXISTS `updates`');
    database()->multiQuery($fixture.'/update/create_updates.sql');
    $saved = [];
    for ($i = 1; $i <= $oldCount; ++$i) {
        $directory = $i <= 100 ? '' : 'modules/lab_component';
        $version = $i <= 100 ? '2.4.'.$i : '1.0.'.($i - 100);
        $path = $fixture.'/'.($directory ? $directory.'/' : '').'update/'.str_replace('.', '_', $version).'.sql';
        file_put_contents($path, 'SELECT 1;'.PHP_EOL);
        $row = ['id' => 1000 + $i, 'directory' => $directory, 'version' => $version, 'sql' => 1, 'script' => 0, 'done' => 1];
        database()->insert('updates', $row);
        $saved[] = $row;
    }
    for ($i = 1; $i <= $newCount; ++$i) {
        file_put_contents($fixture.'/modules/lab_component/update/1_1_'.$i.'.sql', 'SELECT 1;'.PHP_EOL);
        file_put_contents($fixture.'/modules/lab_component/update/1_1_'.$i.'.php', '<?php /* synthetic no-op */'.PHP_EOL);
    }
    resetUpdateCache();
    return $saved;
}

function snapshot(): array
{
    return database()->fetchArray('SELECT `id`, `directory`, `version`, `sql`, `script`, `done` FROM `updates` ORDER BY `id`');
}

function childProcess(string $fixture, array $extraEnv = [], string $cwd = ''): array
{
    $env = array_merge(getenv(), $extraEnv);
    $command = [PHP_BINARY, __FILE__, $GLOBALS['labSource'], '--register', $fixture];
    $pipes = [];
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd ?: $fixture, $env);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start PHP subprocess.');
    }
    fclose($pipes[0]);
    return [$process, $pipes];
}

function finishChild(array $child): array
{
    [$process, $pipes] = $child;
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return [proc_close($process), $output, $error];
}

function historyRetained(array $old): bool
{
    $current = [];
    foreach (snapshot() as $row) {
        $current[$row['id']] = $row;
    }
    foreach ($old as $row) {
        if (($current[$row['id']] ?? null) != $row) {
            return false;
        }
    }
    return true;
}

$source = $argv[1] ?? dirname(__DIR__, 2).'/src/Update.php';
if (!is_file($source)) {
    fwrite(STDERR, 'Usage: php tests/integration/updater-registration.inc [/checkout/src/Update.php]'.PHP_EOL);
    exit(2);
}
$GLOBALS['labSource'] = realpath($source);
require $source;
Update::setAutoInstall(false);

if (($argv[2] ?? '') === '--register') {
    $GLOBALS['labFixture'] = $argv[3];
    try {
        echo json_encode(['pending' => count(Update::getTodoUpdates())]).PHP_EOL;
        exit(0);
    } catch (Throwable $error) {
        if ($error->getMessage() === 'Injected registration failure') {
            $probe = new LabDatabase();
            $lock = $probe->fetchOne('SELECT IS_FREE_LOCK(?) AS available', ['osm_updates_'.sha1(database()->getDatabaseName())]);
            echo json_encode(['lock_released_after_failure' => (int) $lock['available'] === 1]).PHP_EOL;
            exit((int) $lock['available'] === 1 ? 0 : 1);
        }
        fwrite(STDERR, $error->getMessage().PHP_EOL);
        exit(1);
    }
}

$GLOBALS['labRuntime'] = sys_get_temp_dir().'/osm-updater-regression-'.getmypid().'-'.bin2hex(random_bytes(8));
mkdir($GLOBALS['labRuntime'], 0700, true);
register_shutdown_function(static function (): void {
    $directory = $GLOBALS['labRuntime'];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $path) {
        $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
    }
    rmdir($directory);
});

$results = [];
function check(string $name, bool $ok, array $details = []): void
{
    $GLOBALS['results'][] = ['test' => $name, 'pass' => $ok, 'details' => $details];
    echo json_encode(end($GLOBALS['results']), JSON_UNESCAPED_SLASHES).PHP_EOL;
}

// New migration with a complete historical table; terminate inside real registration.
$old = createFixture('interrupted');
$interrupted = finishChild(childProcess(base_dir(), ['OSM_FAIL_AFTER' => '5']));
$afterInterrupt = snapshot();
$retry = finishChild(childProcess(base_dir()));
$todo = Update::getTodoUpdates();
$historicalPending = count(array_filter($todo, static fn ($row) => $row['version'] !== '1.1.1'));
check('interruption_preserves_completed_history', historyRetained($old) && $historicalPending === 0, [
    'old_completed' => 354,
    'interrupted_exit' => $interrupted[0],
    'rows_after_interruption' => count($afterInterrupt),
    'retry_exit' => $retry[0],
    'historical_requeued' => $historicalPending,
    'pending' => count($todo),
    'errors' => $interrupted[2].$retry[2],
]);
// Also terminate after the first insert so an incremental implementation is interrupted.
$old = createFixture('first_insert_interrupted');
$interrupted = finishChild(childProcess(base_dir(), ['OSM_FAIL_AFTER' => '1']));
$retry = finishChild(childProcess(base_dir()));
$historicalPending = count(array_filter(Update::getTodoUpdates(), static fn ($row) => $row['version'] !== '1.1.1'));
check('first_insert_interruption_preserves_completed_history', $interrupted[0] === 75 && $retry[0] === 0 && historyRetained($old) && $historicalPending === 0, [
    'interrupted_exit' => $interrupted[0], 'retry_exit' => $retry[0], 'historical_requeued' => $historicalPending, 'errors' => $interrupted[2].$retry[2],
]);

// Change CWD while source discovery remains anchored to fixture base_dir().
$old = createFixture('outside_cwd', 10);
$registered = finishChild(childProcess(base_dir(), [], __DIR__));
$new = database()->fetchOne('SELECT * FROM updates WHERE version = ?', ['1.1.1']);
check('registration_uses_absolute_file_paths', $registered[0] === 0 && $new && (int) $new['sql'] === 1 && (int) $new['script'] === 1, [
    'exit' => $registered[0], 'sql' => $new['sql'] ?? null, 'script' => $new['script'] ?? null, 'error' => $registered[2],
]);

// Preserve missing-file audit records and SQL/PHP checkpoints exactly, including IDs.
$old = createFixture('checkpoints', 10);
database()->query('UPDATE updates SET done = 0 WHERE id = 1001');
database()->query('UPDATE updates SET done = 17 WHERE id = 1002');
database()->insert('updates', ['id' => 9000, 'directory' => 'modules/removed_component', 'version' => '1.0', 'sql' => 1, 'script' => 1, 'done' => 1]);
$old = snapshot();
$registered = finishChild(childProcess(base_dir()));
check('registration_preserves_ids_checkpoints_and_removed_files', $registered[0] === 0 && historyRetained($old), ['exit' => $registered[0], 'old_rows' => count($old), 'current_rows' => count(snapshot()), 'error' => $registered[2]]);

// Stable IDs must not place an existing pending component before a new core update.
$old = createFixture('ordering', 10);
foreach ([['1.0.1', null], ['1.0.2', 0], ['1.0.3', 17]] as $index => [$version, $done]) {
    file_put_contents(base_dir().'/modules/lab_component/update/'.str_replace('.', '_', $version).'.sql', 'SELECT 1;'.PHP_EOL);
    database()->insert('updates', ['id' => 10 + $index, 'directory' => 'modules/lab_component', 'version' => $version, 'sql' => 1, 'script' => 0, 'done' => $done]);
}
file_put_contents(base_dir().'/update/2_5_0.sql', 'SELECT 1;'.PHP_EOL);
$registered = finishChild(childProcess(base_dir()));
$todo = Update::getTodoUpdates();
$sequence = array_column($todo, 'version');
check('started_updates_then_core_then_components', $registered[0] === 0 && $sequence === ['1.0.3', '1.0.2', '2.5.0', '1.0.1', '1.1.1'], ['exit' => $registered[0], 'versions' => $sequence, 'error' => $registered[2]]);

// Legacy module_version identity resolves to the same path without journal rewriting.
$old = createFixture('legacy', 10);
unlink(base_dir().'/update/2_4_1.sql');
file_put_contents(base_dir().'/modules/lab_component/update/1_0_1.sql', 'SELECT 1;'.PHP_EOL);
database()->query('UPDATE updates SET directory = NULL, version = ?, done = NULL WHERE id = 1001', ['lab_component_1.0.1']);
$old = snapshot();
$registered = finishChild(childProcess(base_dir()));
$todo = Update::getTodoUpdates();
$legacy = array_values(array_filter($todo, static fn ($row) => (int) $row['id'] === 1001))[0] ?? null;
check('legacy_identity_and_filename_remain_usable', $registered[0] === 0 && historyRetained($old) && count(snapshot()) === 11 && $legacy && $legacy['filename'] === '1_0_1' && $legacy['directory'] === 'modules/lab_component/update/', [
    'exit' => $registered[0], 'rows' => count(snapshot()), 'legacy_filename' => $legacy['filename'] ?? null, 'legacy_directory' => $legacy['directory'] ?? null, 'error' => $registered[2],
]);

// Parallel requests at discovery; deliberately slow insertion increases overlap.
$old = createFixture('concurrent', 354, 6);
$first = childProcess(base_dir(), ['OSM_LAB_INSERT_DELAY_US' => '10000']);
$second = childProcess(base_dir(), ['OSM_LAB_INSERT_DELAY_US' => '10000']);
$a = finishChild($first);
$b = finishChild($second);
$duplicates = database()->fetchArray('SELECT directory, version, COUNT(*) AS n FROM updates GROUP BY directory, version HAVING COUNT(*) > 1');
check('concurrent_registration_is_idempotent', $a[0] === 0 && $b[0] === 0 && !$duplicates && count(snapshot()) === 360 && historyRetained($old), [
    'exits' => [$a[0], $b[0]], 'duplicates' => count($duplicates), 'rows' => count(snapshot()), 'errors' => $a[2].$b[2],
]);

// A concurrent executor advances progress after the discovery snapshot was read.
$old = createFixture('concurrent_progress', 10);
database()->query('UPDATE updates SET done = 17 WHERE id = 1001');
$registered = finishChild(childProcess(base_dir(), ['OSM_LAB_ADVANCE_AFTER_SNAPSHOT' => '1']));
$row = database()->fetchOne('SELECT * FROM updates WHERE directory = ? AND version = ?', ['', '2.4.1']);
check('registration_does_not_overwrite_concurrent_progress', $registered[0] === 0 && $row && (int) $row['done'] === 1, [
    'exit' => $registered[0], 'done' => $row['done'] ?? null, 'error' => $registered[2],
]);

// The application connection stays alive while another connection probes the lock.
$old = createFixture('exception_lock_release', 10);
$registered = finishChild(childProcess(base_dir(), ['OSM_LAB_THROW_AFTER_INSERT' => '1']));
check('registration_releases_lock_after_failure', $registered[0] === 0 && str_contains($registered[1], '"lock_released_after_failure":true') && historyRetained($old), [
    'exit' => $registered[0], 'probe' => trim($registered[1]), 'error' => $registered[2],
]);

// Registration can initialize an absent journal even with automatic install disabled.
createFixture('missing_journal', 0);
database()->query('DROP TABLE updates');
$registered = finishChild(childProcess(base_dir()));
$rows = snapshot();
check('registration_initializes_missing_journal', $registered[0] === 0 && count($rows) === 1 && $rows[0]['done'] === null && (int) $rows[0]['sql'] === 1 && (int) $rows[0]['script'] === 1, [
    'exit' => $registered[0], 'rows' => count($rows), 'error' => $registered[2],
]);

// A repeated completed scan is a read-only operation on the journal.
$before = snapshot();
$registered = finishChild(childProcess(base_dir()));
check('repeated_scan_preserves_journal', $registered[0] === 0 && snapshot() === $before, ['exit' => $registered[0], 'error' => $registered[2]]);

$failures = count(array_filter($results, static fn ($row) => !$row['pass']));
echo json_encode(['summary' => ['tests' => count($results), 'failures' => $failures, 'php' => PHP_VERSION, 'database' => database()->getMySQLVersion(), 'source_sha256' => hash_file('sha256', $source)]], JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($failures && getenv('OSM_EXPECT') !== 'baseline' ? 1 : 0);
