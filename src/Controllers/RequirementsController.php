<?php

namespace Controllers;

use Util\FileSystem;

class RequirementsController extends Controller
{
    protected static $php = [
        'zip' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'mbstring' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'pdo_mysql' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'dom' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'xsl' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'openssl' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'intl' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'curl' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'soap' => [
            'type' => 'ext',
            'required' => 1,
        ],

        'upload_max_filesize' => [
            'type' => 'value',
            'suggested' => '>16M',
        ],
        'post_max_size' => [
            'type' => 'value',
            'suggested' => '>16M',
        ],
    ];

    protected static $apache = [
        'mod_rewrite' => [
            'server' => 'HTTP_MOD_REWRITE',
        ],
    ];

    protected static $directories = [
        'backup',
        'files',
        'logs',
    ];

    protected static $requirements;

    public function requirements($request, $response, $args)
    {
        $requirements = self::getRequirements();

        $list = [
            tr('Apache') => $requirements['apache'],
            tr('PHP (_VERSION_)', [
                '_VERSION_' => phpversion(),
            ]) => $requirements['php'],
            tr('Percorsi di servizio') => $requirements['paths'],
        ];

        $args['requirements'] = $list;
        $response = $this->twig->render($response, 'config\requirements.twig', $args);

        return $response;
    }

    public static function getRequirements()
    {
        if (!isset(self::$requirements)) {
            // Apache
            if (function_exists('apache_get_modules')) {
                $available_modules = apache_get_modules();
            }

            $apache = self::$apache;
            foreach ($apache as $name => $values) {
                $status = isset($available_modules) ? in_array($name, $available_modules) : false;
                $status = isset($values['server']) ? $_SERVER[$values['server']] == 'On' : $status;

                $apache[$name]['description'] = tr('Il modulo Apache _MODULE_ deve essere abilitato', [
                    '_MODULE_' => '<i>'.$name.'</i>',
                ]);
                $apache[$name]['status'] = $status;
            }

            // PHP
            $php = self::$php;
            foreach ($php as $name => $values) {
                if ($values['type'] == 'ext') {
                    $description = !empty($values['required']) ? tr("L'estensione PHP _EXT_ deve essere abilitata", [
                        '_EXT_' => '<i>'.$name.'</i>',
                    ]) : tr("E' consigliata l'abilitazione dell'estensione PHP _EXT_", [
                        '_EXT_' => '<i>'.$name.'</i>',
                    ]);
                } else {
                }

                if ($values['type'] == 'ext') {
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
            foreach (self::$directories as $name) {
                $status = is_writable(DOCROOT.DIRECTORY_SEPARATOR.$name);
                $description = tr('Il percorso _PATH_ deve risultare accessibile da parte del gestionale (permessi di lettura e scrittura)', [
                    '_PATH_' => '<i>'.$name.'</i>',
                ]);

                $paths[$name]['description'] = $description;
                $paths[$name]['status'] = $status;
            }

            self::$requirements = [
                'apache' => $apache,
                'php' => $php,
                'paths' => $paths,
            ];
        }

        return self::$requirements;
    }

    public static function requirementsSatisfied()
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
