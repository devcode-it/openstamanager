<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Util\FileSystem;

class RequirementsController extends Controller
{
    protected static $requirements;

    public function index(Request $request)
    {
        $args = [
            'requirements' => self::getRequirementsList()
        ];

        return view('config.requirements', $args);
    }

    public static function getRequirementsList($file = null)
    {
        $requirements = self::getRequirements($file);

        $list = [
            tr('Apache') => $requirements['apache'],
            tr('PHP (_VERSION_)', [
                '_VERSION_' => phpversion(),
            ]) => $requirements['php'],
            tr('Percorsi di servizio') => $requirements['paths'],
        ];

        return $list;
    }

    public static function getRequirements($file = null)
    {
        if (empty($file) && isset(self::$requirements)) {
            return self::$requirements;
        }

        $list = config('requirements');
        if (!empty($file)) {
            $file = realpath($file);
            if (string_starts_with($file)) {
                $list = include $file;
            }
        }

        // Apache
        if (function_exists('apache_get_modules')) {
            $available_modules = apache_get_modules();
        }

        $apache = $list['apache'];
        foreach ($apache as $name => $values) {
            $status = isset($available_modules) ? in_array($name, $available_modules) : false;
            $status = isset($values['server']) && isset($_SERVER[$values['server']]) ? $_SERVER[$values['server']] == 'On' : $status;

            $apache[$name]['description'] = tr('Il modulo Apache _MODULE_ deve essere abilitato', [
                '_MODULE_' => '<i>'.$name.'</i>',
            ]);
            $apache[$name]['status'] = $status;
        }

        // PHP
        $php = $list['php'];
        foreach ($php as $name => $values) {
            if ($values['type'] == 'ext') {
                $description = !empty($values['required']) ? tr("L'estensione PHP _EXT_ deve essere abilitata", [
                    '_EXT_' => '<i>'.$name.'</i>',
                ]) : tr("E' consigliata l'abilitazione dell'estensione PHP _EXT_", [
                    '_EXT_' => '<i>'.$name.'</i>',
                ]);

                $status = extension_loaded($name);
            } else {
                $suggested = str_replace(['>', '<'], '', $values['suggested']);
                $value = ini_get($name);

                $description = tr("Valore consigliato per l'impostazione PHP: _VALUE_ (Valore attuale: _INI_)", [
                    '_VALUE_' => $suggested,
                    '_INI_' => ini_get($name),
                ]);

                $suggested = strpos($suggested, 'B') !== false ? $suggested : $suggested.'B';
                $value = strpos($value, 'B') !== false ? $value : $value.'B';

                $ini = FileSystem::convertBytes($value);
                $real = FileSystem::convertBytes($suggested);

                if (starts_with($values['suggested'], '>')) {
                    $status = $ini >= substr($real, 1);
                } elseif (starts_with($values['suggested'], '<')) {
                    $status = $ini <= substr($real, 1);
                } else {
                    $status = ($real == $ini);
                }

                $php[$name]['value'] = $value;

                if (is_bool($suggested)) {
                    $suggested = !empty($suggested) ? 'On' : 'Off';
                }
            }

            $php[$name]['description'] = $description;
            $php[$name]['status'] = $status;
        }

        // Percorsi di servizio
        $paths = [];
        foreach ($list['directories'] as $name) {
            $status = is_writable(base_path().DIRECTORY_SEPARATOR.$name);
            $description = tr('Il percorso _PATH_ deve risultare accessibile da parte del gestionale (permessi di lettura e scrittura)', [
                '_PATH_' => '<i>'.$name.'</i>',
            ]);

            $paths[$name]['description'] = $description;
            $paths[$name]['status'] = $status;
        }

        $result = [
            'apache' => $apache,
            'php' => $php,
            'paths' => $paths,
        ];

        if (empty($file)) {
            self::$requirements = $result;
        }

        return $result;
    }

    public static function isSatisfied()
    {
        $general_status = true;

        $requirements = self::getRequirements();
        foreach ($requirements as $key => $values) {
            foreach ($values as $value) {
                $general_status &= !empty($value['required']) ? $value['status'] : true;
            }
        }

        return $general_status;
    }
}
