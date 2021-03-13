<?php

namespace Modules\Aggiornamenti\Http\Controllers;

use App\Http\Controllers\RequirementsController;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\Aggiornamenti\Http\Aggiornamento;
use Modules\Aggiornamenti\Http\DowngradeException;

class AggiornamentiController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $update = new Aggiornamento();

            $args = [
                'update' => $update,
                'update_version' => $update->getVersion(),
                'update_requirements' => $update->getRequirements(),
            ];

            return view('aggiornamenti::update', $args);
        } catch (InvalidArgumentException $e) {
        }

        $custom = $this->customComponents();
        $tables = $this->customTables();

        // Aggiornamenti
        $alerts = [];

        if (!extension_loaded('zip')) {
            $alerts[tr('Estensione ZIP')] = tr('da abilitare');
        }

        $upload_max_filesize = ini_get('upload_max_filesize');
        $upload_max_filesize = str_replace(['k', 'M'], ['000', '000000'], $upload_max_filesize);
        // Dimensione minima: 32MB
        if ($upload_max_filesize < 32000000) {
            $alerts['upload_max_filesize'] = '32MB';
        }

        $post_max_size = ini_get('post_max_size');
        $post_max_size = str_replace(['k', 'M'], ['000', '000000'], $post_max_size);
        // Dimensione minima: 32MB
        if ($post_max_size < 32000000) {
            $alerts['post_max_size'] = '32MB';
        }

        $args = [
            'module' => module('Aggiornamenti'),
            'custom' => $custom,
            'tables' => $tables,
            'alerts' => $alerts,
            'enable_updates' => setting('Attiva aggiornamenti'),
            'requirements' => RequirementsController::getRequirementsList(),
        ];

        return view('aggiornamenti::index', $args);
    }

    /**
     * Controlla se il database presenta alcune sezioni personalizzate.
     *
     * @return array
     */
    public function customStructure()
    {
        $results = [];

        $dirs = [
            'modules',
            'templates',
            'plugins',
        ];

        // Controlli di personalizzazione fisica
        foreach ($dirs as $dir) {
            $files = glob(base_dir().'/'.$dir.'/*/custom/*.{php,html}', GLOB_BRACE);
            $recursive_files = glob(base_dir().'/'.$dir.'/*/custom/**/*.{php,html}', GLOB_BRACE);

            $files = array_merge($files, $recursive_files);

            foreach ($files as $file) {
                $file = str_replace(base_dir().'/', '', $file);
                $result = explode('/custom/', $file)[0];

                if (!in_array($result, $results)) {
                    $results[] = $result;
                }
            }
        }

        // Gestione cartella include
        $files = glob(base_dir().'/include/custom/*.{php,html}', GLOB_BRACE);
        $recursive_files = glob(base_dir().'/include/custom/**/*.{php,html}', GLOB_BRACE);

        $files = array_merge($files, $recursive_files);

        foreach ($files as $file) {
            $file = str_replace(base_dir().'/', '', $file);
            $result = explode('/custom/', $file)[0];

            if (!in_array($result, $results)) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Controlla se il database presenta alcune sezioni personalizzate.
     *
     * @return array
     */
    protected function customTables()
    {
        $tables = include base_dir().'/update/tables.php';

        $names = [];
        foreach ($tables as $table) {
            $names[] = prepare($table);
        }

        $database = database();

        $results = $database->fetchArray('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '.prepare($database->getDatabaseName()).' AND TABLE_NAME NOT IN ('.implode(',', $names).") AND TABLE_NAME != 'updates'");

        return array_column($results, 'TABLE_NAME');
    }

    /**
     * Controlla se il database presenta alcune sezioni personalizzate.
     *
     * @return array
     */
    protected function customDatabase()
    {
        $database = database();
        $modules = $database->fetchArray("SELECT name, CONCAT('modules/', directory) AS directory FROM zz_modules WHERE options2 != ''");
        $plugins = $database->fetchArray("SELECT name, CONCAT('plugins/', directory) AS directory FROM zz_plugins WHERE options2 != ''");

        $results = array_merge($modules, $plugins);

        return $results;
    }

    protected function customComponents()
    {
        $database_check = $this->customDatabase();
        $structure_check = $this->customStructure();

        $list = [];
        foreach ($database_check as $element) {
            $pos = array_search($element['directory'], $structure_check);

            $list[] = [
                'path' => $element['directory'],
                'database' => true,
                'directory' => $pos !== false,
            ];

            if ($pos !== false) {
                unset($structure_check[$pos]);
            }
        }

        foreach ($structure_check as $element) {
            $list[] = [
                'path' => $element,
                'database' => false,
                'directory' => true,
            ];
        }

        return $list;
    }

    public function create(Request $request)
    {
        if (!setting('Attiva aggiornamenti')) {
            die(tr('Accesso negato'));
        }

        try {
            $update = Aggiornamento::make($_FILES['blob']['tmp_name']);
        } catch (DowngradeException $e) {
            flash()->error(tr('Il pacchetto contiene una versione precedente del gestionale'));
        } catch (InvalidArgumentException $e) {
            flash()->error(tr('Il pacchetto contiene solo componenti già installate e aggiornate'));
        }
    }

    public function check(Request $request)
    {
        $result = Aggiornamento::isAvailable();
        $result = $result === false ? 'none' : $result;

        return $result;
    }

    public function download(Request $request)
    {
        Aggiornamento::download();
    }

    public function execute(Request $request)
    {
        try {
            $update = new Aggiornamento();

            $update->execute();
        } catch (InvalidArgumentException $e) {
        }

        $route = $this->router->urlFor('module', [
            'module_id' => $args['module_id'],
        ]);
        $response = $response->withRedirect($route);

        return $response;
    }

    public function cancel(Request $request)
    {
        try {
            $update = new Aggiornamento();

            $update->delete();
        } catch (InvalidArgumentException $e) {
        }

        $route = $this->router->urlFor('module', [
            'module_id' => $args['module_id'],
        ]);
        $response = $response->withRedirect($route);

        return $response;
    }
}
