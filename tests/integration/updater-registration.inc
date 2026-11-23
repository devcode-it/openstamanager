<?php

/**
 * Standalone regression tests for Update, using PHP 8.3 and pdo_mysql.
 * Requires a disposable MySQL/MariaDB database named osm_updater_lab*.
 *
 * OSM_TEST_DSN=mysql:host=db;dbname=osm_updater_lab;charset=utf8mb4
 * OSM_TEST_USER=root OSM_TEST_PASSWORD=... php tests/integration/updater-registration.inc
 * Pass an optional source-file argument to test an earlier Update.php.
 * The schema comes from this checkout unless OSM_TEST_SCHEMA is set.
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
        if (!preg_match('/^osm_updater_lab[a-zA-Z0-9_]*$/D', $this->getDatabaseName())) {
            throw new RuntimeException('Connected database must be a disposable osm_updater_lab* database.');
        }
    }

    public function isConnected(): bool
    {
        return true;
    }

    public function isInstalled(): bool
    {
        return true;
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function getDatabaseName(): string
    {
        return (string) $this->pdo->query('SELECT DATABASE()')->fetchColumn();
    }

    public function getMySQLVersion(): string
    {
        return (string) $this->pdo->query('SELECT VERSION()')->fetchColumn();
    }

    public function tableExists(string $table): bool
    {
        return (bool) $this->fetchOne(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );
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
            // Another connection advances progress after registration reads its snapshot.
            $executor = new self();
            $executor->query('UPDATE updates SET done = 1 WHERE id = 1001');
            putenv('OSM_LAB_ADVANCE_AFTER_SNAPSHOT=0');
        }

        return $rows;
    }

    public function fetchOne(string $query, array $params = []): array|false
    {
        return $this->query($query.' LIMIT 1', $params)->fetch();
    }

    public function multiQuery(string $file): void
    {
        $this->pdo->exec(file_get_contents($file));
    }

    public function insert(string $table, array $data): void
    {
        $fields = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $this->query(
            'INSERT INTO `'.$table.'` (`'.implode('`, `', $fields).'`) VALUES ('.$placeholders.')',
            array_values($data)
        );
        ++$this->inserts;

        $insertDelay = (int) (getenv('OSM_LAB_INSERT_DELAY_US') ?: 0);
        if ($insertDelay > 0) {
            usleep($insertDelay);
        }

        $exitAfterInsert = (int) (getenv('OSM_FAIL_AFTER') ?: 0);
        if ($exitAfterInsert && $this->inserts === $exitAfterInsert) {
            // Stop the process without running catch/finally.
            exit(75);
        }

        if (getenv('OSM_LAB_THROW_AFTER_INSERT') === '1') {
            throw new RuntimeException('Injected registration failure');
        }
    }
}

function database(): LabDatabase
{
    return $GLOBALS['labDatabase'] ??= new LabDatabase();
}

function base_dir(): string
{
    return $GLOBALS['labFixture'];
}

function prepare(mixed $value): string
{
    return $value === null ? 'NULL' : database()->getPDO()->quote((string) $value);
}

function string_contains(string $string, string $needle): bool
{
    return str_contains($string, $needle);
}

function tr(string $message): string
{
    return $message;
}

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
        removeFixtureDirectory($fixture);
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
        $row = [
            'id' => 1000 + $i,
            'directory' => $directory,
            'version' => $version,
            'sql' => 1,
            'script' => 0,
            'done' => 1,
        ];
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

function removeFixtureDirectory(string $directory): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $path) {
        if ($path->isDir()) {
            rmdir($path->getPathname());
        } else {
            unlink($path->getPathname());
        }
    }
    rmdir($directory);
}

function readJournal(): array
{
    return database()->fetchArray('SELECT `id`, `directory`, `version`, `sql`, `script`, `done` FROM `updates` ORDER BY `id`');
}

function startRegistration(string $fixture, array $extraEnv = [], string $cwd = ''): array
{
    $env = array_merge(getenv(), $extraEnv);
    $command = [PHP_BINARY, __FILE__, $GLOBALS['labSource'], '--register', $fixture];
    $pipes = [];
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open($command, $descriptors, $pipes, $cwd ?: $fixture, $env);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start PHP subprocess.');
    }

    fclose($pipes[0]);

    return [$process, $pipes];
}

function finishRegistration(array $child): array
{
    [$process, $pipes] = $child;
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return ['exit' => proc_close($process), 'stdout' => $output, 'stderr' => $error];
}

function historyIsUnchanged(array $history): bool
{
    $current = [];
    foreach (readJournal() as $row) {
        $current[$row['id']] = $row;
    }

    foreach ($history as $row) {
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
    removeFixtureDirectory($GLOBALS['labRuntime']);
});

$results = [];
function check(string $name, bool $ok, array $details = []): void
{
    $GLOBALS['results'][] = ['test' => $name, 'pass' => $ok, 'details' => $details];
    echo json_encode(end($GLOBALS['results']), JSON_UNESCAPED_SLASHES).PHP_EOL;
}

// Interrupt registration after five inserts.
$history = createFixture('interrupted');
$interrupted = finishRegistration(startRegistration(base_dir(), ['OSM_FAIL_AFTER' => '5']));
$afterInterrupt = readJournal();
$retry = finishRegistration(startRegistration(base_dir()));
$pendingUpdates = Update::getTodoUpdates();
$historicalPending = count(array_filter($pendingUpdates, static fn ($row) => $row['version'] !== '1.1.1'));
check('interruption_preserves_completed_history', historyIsUnchanged($history) && $historicalPending === 0, [
    'old_completed' => 354,
    'interrupted_exit' => $interrupted['exit'],
    'rows_after_interruption' => count($afterInterrupt),
    'retry_exit' => $retry['exit'],
    'historical_requeued' => $historicalPending,
    'pending' => count($pendingUpdates),
    'errors' => $interrupted['stderr'].$retry['stderr'],
]);

// Interrupt after the first insert, including with incremental registration.
$history = createFixture('first_insert_interrupted');
$interrupted = finishRegistration(startRegistration(base_dir(), ['OSM_FAIL_AFTER' => '1']));
$retry = finishRegistration(startRegistration(base_dir()));
$historicalPending = count(array_filter(Update::getTodoUpdates(), static fn ($row) => $row['version'] !== '1.1.1'));
check(
    'first_insert_interruption_preserves_completed_history',
    $interrupted['exit'] === 75
        && $retry['exit'] === 0
        && historyIsUnchanged($history)
        && $historicalPending === 0,
    [
        'interrupted_exit' => $interrupted['exit'],
        'retry_exit' => $retry['exit'],
        'historical_requeued' => $historicalPending,
        'errors' => $interrupted['stderr'].$retry['stderr'],
    ]
);

// Change CWD while source discovery remains anchored to fixture base_dir().
createFixture('outside_cwd', 10);
$registration = finishRegistration(startRegistration(base_dir(), [], __DIR__));
$newUpdate = database()->fetchOne('SELECT * FROM updates WHERE version = ?', ['1.1.1']);
check(
    'registration_uses_absolute_file_paths',
    $registration['exit'] === 0 && $newUpdate && (int) $newUpdate['sql'] === 1 && (int) $newUpdate['script'] === 1,
    [
        'exit' => $registration['exit'],
        'sql' => $newUpdate['sql'] ?? null,
        'script' => $newUpdate['script'] ?? null,
        'error' => $registration['stderr'],
    ]
);

// Preserve missing-file audit records and SQL/PHP checkpoints exactly, including IDs.
createFixture('checkpoints', 10);
database()->query('UPDATE updates SET done = 0 WHERE id = 1001');
database()->query('UPDATE updates SET done = 17 WHERE id = 1002');
database()->insert('updates', [
    'id' => 9000,
    'directory' => 'modules/removed_component',
    'version' => '1.0',
    'sql' => 1,
    'script' => 1,
    'done' => 1,
]);
$history = readJournal();
$registration = finishRegistration(startRegistration(base_dir()));
check(
    'registration_preserves_ids_checkpoints_and_removed_files',
    $registration['exit'] === 0 && historyIsUnchanged($history),
    [
        'exit' => $registration['exit'],
        'old_rows' => count($history),
        'current_rows' => count(readJournal()),
        'error' => $registration['stderr'],
    ]
);

// Stable IDs must not place an existing pending component before a new core update.
createFixture('ordering', 10);
foreach ([['1.0.1', null], ['1.0.2', 0], ['1.0.3', 17]] as $index => [$version, $done]) {
    file_put_contents(base_dir().'/modules/lab_component/update/'.str_replace('.', '_', $version).'.sql', 'SELECT 1;'.PHP_EOL);
    database()->insert('updates', [
        'id' => 10 + $index,
        'directory' => 'modules/lab_component',
        'version' => $version,
        'sql' => 1,
        'script' => 0,
        'done' => $done,
    ]);
}
file_put_contents(base_dir().'/update/2_5_0.sql', 'SELECT 1;'.PHP_EOL);
$registration = finishRegistration(startRegistration(base_dir()));
$pendingUpdates = Update::getTodoUpdates();
$versions = array_column($pendingUpdates, 'version');
check(
    'started_updates_then_core_then_components',
    $registration['exit'] === 0 && $versions === ['1.0.3', '1.0.2', '2.5.0', '1.0.1', '1.1.1'],
    ['exit' => $registration['exit'], 'versions' => $versions, 'error' => $registration['stderr']]
);

// Legacy module_version identity resolves to the same path without journal rewriting.
createFixture('legacy', 10);
unlink(base_dir().'/update/2_4_1.sql');
file_put_contents(base_dir().'/modules/lab_component/update/1_0_1.sql', 'SELECT 1;'.PHP_EOL);
database()->query('UPDATE updates SET directory = NULL, version = ?, done = NULL WHERE id = 1001', ['lab_component_1.0.1']);
$history = readJournal();
$registration = finishRegistration(startRegistration(base_dir()));
$pendingUpdates = Update::getTodoUpdates();
$legacy = array_values(array_filter($pendingUpdates, static fn ($row) => (int) $row['id'] === 1001))[0] ?? null;
check(
    'legacy_identity_and_filename_remain_usable',
    $registration['exit'] === 0
        && historyIsUnchanged($history)
        && count(readJournal()) === 11
        && $legacy
        && $legacy['filename'] === '1_0_1'
        && $legacy['directory'] === 'modules/lab_component/update/',
    [
        'exit' => $registration['exit'],
        'rows' => count(readJournal()),
        'legacy_filename' => $legacy['filename'] ?? null,
        'legacy_directory' => $legacy['directory'] ?? null,
        'error' => $registration['stderr'],
    ]
);

// Parallel requests at discovery; deliberately slow insertion increases overlap.
$history = createFixture('concurrent', 354, 6);
$first = startRegistration(base_dir(), ['OSM_LAB_INSERT_DELAY_US' => '10000']);
$second = startRegistration(base_dir(), ['OSM_LAB_INSERT_DELAY_US' => '10000']);
$firstResult = finishRegistration($first);
$secondResult = finishRegistration($second);
$duplicates = database()->fetchArray('SELECT directory, version, COUNT(*) AS n FROM updates GROUP BY directory, version HAVING COUNT(*) > 1');
check(
    'concurrent_registration_is_idempotent',
    $firstResult['exit'] === 0
        && $secondResult['exit'] === 0
        && !$duplicates
        && count(readJournal()) === 360
        && historyIsUnchanged($history),
    [
        'exits' => [$firstResult['exit'], $secondResult['exit']],
        'duplicates' => count($duplicates),
        'rows' => count(readJournal()),
        'errors' => $firstResult['stderr'].$secondResult['stderr'],
    ]
);

// A concurrent executor advances progress after the discovery snapshot was read.
createFixture('concurrent_progress', 10);
database()->query('UPDATE updates SET done = 17 WHERE id = 1001');
$registration = finishRegistration(startRegistration(base_dir(), ['OSM_LAB_ADVANCE_AFTER_SNAPSHOT' => '1']));
$row = database()->fetchOne('SELECT * FROM updates WHERE directory = ? AND version = ?', ['', '2.4.1']);
check('registration_does_not_overwrite_concurrent_progress', $registration['exit'] === 0 && $row && (int) $row['done'] === 1, [
    'exit' => $registration['exit'], 'done' => $row['done'] ?? null, 'error' => $registration['stderr'],
]);

// The application connection stays alive while another connection probes the lock.
$history = createFixture('exception_lock_release', 10);
$registration = finishRegistration(startRegistration(base_dir(), ['OSM_LAB_THROW_AFTER_INSERT' => '1']));
check(
    'registration_releases_lock_after_failure',
    $registration['exit'] === 0
        && str_contains($registration['stdout'], '"lock_released_after_failure":true')
        && historyIsUnchanged($history),
    [
        'exit' => $registration['exit'],
        'probe' => trim($registration['stdout']),
        'error' => $registration['stderr'],
    ]
);

// Registration can initialize an absent journal even with automatic install disabled.
createFixture('missing_journal', 0);
database()->query('DROP TABLE updates');
$registration = finishRegistration(startRegistration(base_dir()));
$rows = readJournal();
check(
    'registration_initializes_missing_journal',
    $registration['exit'] === 0
        && count($rows) === 1
        && $rows[0]['done'] === null
        && (int) $rows[0]['sql'] === 1
        && (int) $rows[0]['script'] === 1,
    ['exit' => $registration['exit'], 'rows' => count($rows), 'error' => $registration['stderr']]
);

// A repeated completed scan is a read-only operation on the journal.
$before = readJournal();
$registration = finishRegistration(startRegistration(base_dir()));
check(
    'repeated_scan_preserves_journal',
    $registration['exit'] === 0 && readJournal() === $before,
    ['exit' => $registration['exit'], 'error' => $registration['stderr']]
);

// The directory name may itself end in "update".
createFixture('component_name_ending_in_update', 0, 0);
mkdir(base_dir().'/modules/lab_update/update', 0700, true);
file_put_contents(base_dir().'/modules/lab_update/update/1_0.sql', 'SELECT 1;'.PHP_EOL);
Update::getTodoUpdates();
$firstScan = readJournal();
$registration = finishRegistration(startRegistration(base_dir()));
$secondScan = readJournal();
check(
    'component_name_ending_in_update_is_registered_once',
    $registration['exit'] === 0
        && count($firstScan) === 1
        && $firstScan[0]['directory'] === 'modules/lab_update'
        && $secondScan === $firstScan,
    ['first_count' => count($firstScan), 'second_count' => count($secondScan), 'directory' => $firstScan[0]['directory'] ?? null]
);

// A completed legacy entry and a pending modern entry can refer to the same migration.
createFixture('completed_legacy_duplicate', 0, 0);
file_put_contents(base_dir().'/modules/lab_component/update/1_0.sql', 'SELECT 1;'.PHP_EOL);
file_put_contents(base_dir().'/plugins/lab_plugin/update/1_0.sql', 'SELECT 1;'.PHP_EOL);
database()->insert('updates', ['id' => 1001, 'directory' => '', 'version' => 'lab_component_1.0', 'sql' => 1, 'script' => 0, 'done' => 1]);
database()->insert('updates', ['id' => 1002, 'directory' => 'modules/lab_component', 'version' => '1.0', 'sql' => 1, 'script' => 0, 'done' => null]);
database()->insert('updates', ['id' => 1003, 'directory' => 'plugins/lab_plugin', 'version' => '1.0', 'sql' => 1, 'script' => 0, 'done' => null]);
$before = readJournal();
$pendingIds = array_map('intval', array_column(Update::getTodoUpdates(), 'id'));
check(
    'completed_legacy_migration_is_not_queued_twice',
    $pendingIds === [1003] && readJournal() === $before,
    ['pending_ids' => $pendingIds, 'journal_unchanged' => readJournal() === $before]
);

$failures = count(array_filter($results, static fn ($row) => !$row['pass']));
$summary = [
    'tests' => count($results),
    'failures' => $failures,
    'php' => PHP_VERSION,
    'database' => database()->getMySQLVersion(),
    'source_sha256' => hash_file('sha256', $source),
];
echo json_encode(['summary' => $summary], JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($failures && getenv('OSM_EXPECT') !== 'baseline' ? 1 : 0);
