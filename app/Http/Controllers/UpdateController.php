<?php

namespace App\Http\Controllers;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Update;

class UpdateController extends Controller
{
    protected static $legacyUpdateRate = 20;
    protected static $legacyScriptValue = 60;

    public function __construct()
    {
        $this->initMigrationsTable();
    }

    public function index(Request $request)
    {
        $migrations = self::computeMigrations();
        $legacy = self::computeLegacyUpdates();

        return view('config.update', [
            'installing' => database()->isInstalled(),
            'legacy_number' => $legacy['number'],
            'updates_available' => $legacy['number'] + $migrations['number'],
            'total_weight' => $legacy['weight'] + $migrations['weight'],
        ]);
    }

    public static function getMigrator(): Migrator
    {
        $app = app();

        return $app['migrator'];
    }

    public static function isCompleted()
    {
        $migrations = self::computeMigrations();
        $legacy = self::computeLegacyUpdates();

        return $legacy['number'] + $migrations['number'] == 0;
    }

    public function execute(Request $request)
    {
        if ($request->input('legacy') == 'true') {
            $response = $this->executeLegacyUpdate();
        } else {
            $response = $this->executeMigration();
        }

        return response()->json($response);
    }

    protected function initMigrationsTable()
    {
        $migrator = self::getMigrator();
        if ($migrator->repositoryExists()) {
            return;
        }

        $migrator->getRepository()->createRepository();
    }

    protected static function computeMigrations()
    {
        $migrator = self::getMigrator();

        $paths = array_merge(
            $migrator->paths(),
            [app()->databasePath().DIRECTORY_SEPARATOR.'migrations']
        );

        // Elenco di tutte le migrazioni disponibili
        $files = $migrator->getMigrationFiles($paths);
        $ran = $migrator->getRepository()->getRan();

        // Filtro per migrazioni da eseguire
        $migrations = Collection::make($files)
            ->reject(function ($file) use ($migrator, $ran) {
                return in_array($migrator->getMigrationName($file), $ran);
            })->values()->all();
        $count = count($migrations);

        return [
            'list' => $migrations,
            'number' => $count,
            'weight' => $count,
        ];
    }

    protected static function computeLegacyUpdates()
    {
        $updates = Update::getTodoUpdates();

        $total = 0;
        foreach ($updates as $update) {
            if ($update['sql'] && (!empty($update['done']) || is_null($update['done']))) {
                $queries = readSQLFile(base_dir().$update['directory'].$update['filename'].'.sql', ';');
                $total += count($queries);

                if (intval($update['done']) > 1) {
                    $total -= intval($update['done']) - 2;
                }
            }

            if ($update['script']) {
                $total += self::$legacyScriptValue;
            }
        }

        return [
            'list' => $updates,
            'number' => count($updates),
            'weight' => $total,
        ];
    }

    protected function executeMigration()
    {
        $migrations = $this->computeMigrations();

        // Ricerca della migrazione indicata
        $migration = $migrations['list'][0];

        // Esecuzione migrazione
        $migrator = self::getMigrator();
        $migrator->runPending([$migration]);

        return [
            'progress' => 1,
            'migration' => $migrator->getMigrationName($migration),
            'completed' => count($migrations['list']) <= 1,
        ];
    }

    protected function executeLegacyUpdate()
    {
        $update = Update::getCurrentUpdate();
        $result = Update::doUpdate(self::$legacyUpdateRate);

        $rate = 0;
        if (is_array($result)) {
            $rate = $result[1] - $result[0];
        } elseif (!empty($update['script'])) {
            $rate = self::$legacyScriptValue;
        }

        return [
            'progress' => $rate,
            'legacyVersion' => $update['version'],
        ];
    }
}
