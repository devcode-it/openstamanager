<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Apache
$modules = [
    'mod_rewrite' => [
        'server' => 'HTTP_MOD_REWRITE',
        'description' => tr('Fornisce un sistema di riscrittura URL basato su regole predefinite'),
    ],
];

if (function_exists('apache_get_modules')) {
    $available_modules = apache_get_modules();
}

$apache = [];
foreach ($modules as $name => $values) {
    $description = $values['description'];

    $status = isset($available_modules) ? in_array($name, $available_modules) : $_SERVER[$values['server']] == 'On';

    $apache[] = [
        'name' => $name,
        'description' => $description,
        'status' => $status,
        'type' => tr('Modulo'),
    ];
}

//PHP
$settings = [
    'php_version' => [
        'type' => 'version',
        'description' => '7.3.x - 8.0.x, consigliato almeno 7.4.x',
        'minimum' => '7.3.0',
        'maximum' => '8.0.99',
    ],

    'zip' => [
        'type' => 'ext',
        'description' => tr('Permette di leggere e scrivere gli archivi compressi ZIP e i file al loro interno'),
    ],
    'mbstring' => [
        'type' => 'ext',
        'description' => tr('Permette di gestire i caratteri dello standard UTF-8'),
    ],
    'pdo_mysql' => [
        'type' => 'ext',
        'description' => tr('Permette di effettuare la connessione al database MySQL'),
    ],
    'dom' => [
        'type' => 'ext',
        'description' => tr('Permette la gestione dei file standard per la Fatturazione Elettronica'),
    ],
    'xsl' => [
        'type' => 'ext',
        'description' => tr('Permette di visualizzazione grafica della Fattura Elettronica'),
    ],
    'openssl' => [
        'type' => 'ext',
        'description' => tr("Permette l'utilizzo di funzioni crittografiche simmetriche e asimmetriche"),
    ],
    'intl' => [
        'type' => 'ext',
        'description' => tr("Permette l'automazione della conversione dei numeri"),
    ],
    'curl' => [
        'type' => 'ext',
        'description' => tr('Permette la comunicazione con servizi esterni'),
    ],
    'soap' => [
        'type' => 'ext',
        'description' => tr('Permette la comunicazione con servizi esterni, quali il database europeo delle Partite IVA (facoltativo)'),
    ],
    'gd' => [
        'type' => 'ext',
        'description' => tr('Permette la creazione dell\'immagine della firma per il rapportino d\'intervento (facoltativo)'),
    ],
    'fileinfo' => [
        'type' => 'ext',
        'description' => tr('Permette la creazione dell\'immagine della firma per il rapportino d\'intervento (facoltativo)'),
    ],

    //'display_errors' => [
    //    'type' => 'value',
    //    'description' => true,
    //],

    'allow_url_fopen' => [
        'type' => 'value',
        'description' => 1,
    ],

    'upload_max_filesize' => [
        'type' => 'value',
        'description' => '>32M',
    ],

    'post_max_size' => [
        'type' => 'value',
        'description' => '>32M',
    ],

    'max_input_vars' => [
        'type' => 'value',
        'description' => '>5000',
    ],
];

$php = [];
foreach ($settings as $name => $values) {
    $description = $values['description'];

    if ($values['type'] == 'version') {
        $description = tr('Valore consigliato: _VALUE_ (Valore attuale: _PHP_VERSION_)', [
            '_VALUE_' => $description,
            '_PHP_VERSION_' => phpversion(),
        ]);

        $status = ((version_compare(phpversion(), $values['minimum'], '>=') && version_compare(phpversion(), $values['maximum'], '<=')) ? 1 : 0);
    } elseif ($values['type'] == 'ext') {
        $status = extension_loaded($name);
    } else {
        $ini = str_replace(['k', 'M'], ['000', '000000'], ini_get($name));
        $real = str_replace(['k', 'M'], ['000', '000000'], $description);

        if (string_starts_with($real, '>')) {
            $status = $ini >= substr($real, 1);
        } elseif (string_starts_with($real, '<')) {
            $status = $ini <= substr($real, 1);
        } else {
            $status = ($real == $ini);
        }

        if (is_bool($description)) {
            $description = !empty($description) ? 'On' : 'Off';
        } else {
            $description = str_replace(['>', '<'], '', $description);
        }

        $description = tr('Valore consigliato: _VALUE_ (Valore attuale: _INI_)', [
          '_VALUE_' => $description,
          '_INI_' => ini_get($name),
        ]);
    }

    $type = ($values['type'] == 'ext') ? tr('Estensione') : tr('Impostazione');

    if ($values['type'] == 'ext') {
        $type = tr('Estensione');
    } elseif ($values['type'] == 'version') {
        $type = tr('Versione');
    } else {
        $type = tr('Impostazione');
    }

    $php[] = [
        'name' => $name,
        'description' => $description,
        'status' => $status,
        'type' => $type,
    ];
}

// MySQL
if ($database->isInstalled()) {
    $db = [
        'mysql_version' => [
            'type' => 'version',
            'description' => '5.7.x - 8.0.x',
            'minimum' => '5.7.0',
            'maximum' => '8.0.99',
        ],

        'sort_buffer_size' => [
            'type' => 'value',
            'description' => '>2M',
        ],
    ];

    /*foreach (App::getConfig()['db_options'] as $n => $v){

        switch ($n){
            case 'sort_buffer_size':
                $db[$n] = [
                    'type' => 'value',
                    'description' => '>2M',
                ];
            break;
        }

    }*/
}

foreach ($db as $name => $values) {
    $description = $values['description'];

    if ($values['type'] == 'version') {
        $type = tr('Versione');
        $description = tr('Valore consigliato: _VALUE_ (Valore attuale: _MYSQL_VERSION_)', [
            '_VALUE_' => $description,
            '_MYSQL_VERSION_' => $database->getMySQLVersion(),
        ]);

        $status = ((version_compare($database->getMySQLVersion(), $values['minimum'], '>=') && version_compare($database->getMySQLVersion(), $values['maximum'], '<=')) ? 1 : 0);
    } else {
        $type = tr('Impostazione');

        //Vedo se riesco a recuperare l'impostazione dalle variabili di sessione o globali di mysql
        $rs_session_variabile = $dbo->fetchArray('SHOW SESSION VARIABLES LIKE '.prepare($name));
        $rs_global_variabile = $dbo->fetchArray('SHOW GLOBAL VARIABLES LIKE '.prepare($name));

        if (!empty($rs_session_variabile[0]['Value'])) {
            $inc = $rs_session_variabile[0]['Value'];
        } elseif (!empty($rs_global_variabile[0]['Value'])) {
            $inc = $rs_global_variabile[0]['Value'];
        } else {
            $inc = str_replace(['k', 'M'], ['000', '000000'], App::getConfig()['db_options'][$name]);
        }

        $real = str_replace(['k', 'M'], ['000', '000000'], $description);

        if (string_starts_with($real, '>')) {
            $status = $inc >= substr($real, 1);
        } elseif (string_starts_with($real, '<')) {
            $status = $inc <= substr($real, 1);
        } else {
            $status = ($real == $inc);
        }

        if (is_bool($description)) {
            $description = !empty($description) ? 'On' : 'Off';
        } else {
            $description = str_replace(['>', '<'], '', $description);
        }

        $description = tr('Valore consigliato: _VALUE_ (Valore attuale: _INC_)', [
          '_VALUE_' => $description,
          '_INC_' => \Util\FileSystem::formatBytes($inc),
        ]);
    }

    $mysql[] = [
        'name' => $name,
        'description' => $description,
        'status' => $status,
        'type' => $type,
    ];
}

// Percorsi di servizio
$dirs_to_check = [
    'backup' => tr('Necessario per il salvataggio dei backup'),
    'files' => tr('Necessario per il salvataggio di file inseriti dagli utenti'),
    'files/temp' => tr('Necessario per la generazione delle stampe'),
    'logs' => tr('Necessario per la gestione dei file di log'),
];

$directories = [];
foreach ($dirs_to_check as $name => $description) {
    $status = is_writable(base_dir().DIRECTORY_SEPARATOR.$name);

    $directories[] = [
        'name' => $name,
        'description' => $description,
        'status' => $status,
        'type' => tr('Cartella'),
    ];
}

// File di servizio
$files_to_check = [
    'manifest.json' => tr('Necessario per l\'aggiunta a schermata home da terminale (creato al termine della configurazione)'),
    'database_5_7.json' => tr('Necessario per il controllo integrità con database MySQL 5.7.x'),
    'database.json' => tr('Necessario per il controllo integrità con database MySQL 8.0.x'),
    'checksum.json' => tr('Necessario per il controllo integrità dei files del gestionale'),
];

$files = [];
foreach ($files_to_check as $name => $description) {
    $status = is_writable(base_dir().DIRECTORY_SEPARATOR.$name);

    $files[] = [
        'name' => $name,
        'description' => $description,
        'status' => $status,
        'type' => tr('File'),
    ];
}

// Configurazioni OSM
$config_to_check = [
    'lang' => [
        'type' => 'value',
        'operator' => 'strcmp',
        'value_to_check' => '|lang|',
        'suggested_value' => 'it_IT',
        'section' => '',
    ],
    'timestamp' => [
        'type' => 'value',
        'operator' => 'strcmp',
        'value_to_check' => '|timestamp|',
        'suggested_value' => 'd/m/Y H:i',
        'section' => 'formatter',
    ],
    'date' => [
        'type' => 'value',
        'operator' => 'strcmp',
        'value_to_check' => '|date|',
        'suggested_value' => 'd/m/Y',
        'section' => 'formatter',
    ],
    'time' => [
        'type' => 'value',
        'operator' => 'strcmp',
        'value_to_check' => '|time|',
        'suggested_value' => 'H:i',
        'section' => 'formatter',
    ],
];

$config = [];

foreach ($config_to_check as $name => $values) {
    $type = $values['type'];

    if ($type == 'value') {
        $description = tr('Valore consigliato: _SUGGESTED_ (Valore attuale: _ACTUAL_)', [
            '_SUGGESTED_' => $values['suggested_value'],
            '_ACTUAL_' => (!empty($values['section']) ? ${$values['section']}[$name] : $$name),
        ]);
    }

    $status = ($values['operator']((!empty($values['section']) ? ${$values['section']}[$name] : $$name), $values['value_to_check']) ? 1 : 0);

    $config[] = [
        'name' => $name,
        'description' => $description,
        'status' => $status,
        'type' => tr('Configurazione'),
    ];
}

$requirements = [
    tr('Apache') => $apache,
    tr('PHP (_VERSION_ _SUPPORTED_)', [
        '_VERSION_' => phpversion(),
        '_SUPPORTED_' => ((version_compare(phpversion(), $settings['php_version']['minimum'], '>=') && version_compare(phpversion(), $settings['php_version']['maximum'], '<=')) ? '' : '<small><small class="label label-danger" ><i class="fa fa-warning"></i> '.tr('versioni supportate:').' '.$settings['php_version']['description'].'</small></small>'),
    ]) => $php,
    tr('MySQL') => $mysql,
    tr('Percorsi di servizio') => $directories,
    tr('File di servizio') => $files,
    tr('Configurazioni') => $config,
];

if (!$database->isInstalled() || empty($mysql)) {
    unset($requirements['MySQL']);
}

// Tabelle di riepilogo
foreach ($requirements as $key => $values) {
    $statuses = array_column($values, 'status');
    $general_status = true;
    foreach ($statuses as $status) {
        $general_status &= $status;
    }

    echo '
<div class="box box-'.($general_status ? 'success collapsed-box' : 'danger').'">
    <div class="box-header with-border">
        <h3 class="box-title">'.$key.'</h3>';

    if ($general_status) {
        echo '
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-plus"></i>
            </button>
        </div>';
    }

    echo '
    </div>
    <div class="box-body no-padding">
        <table class="table">';

    foreach ($values as $value) {
        echo '
            <tr class="'.($value['status'] ? 'success' : 'danger').'">
                <td style="width: 10px"><i class="fa fa-'.($value['status'] ? 'check' : 'times').'"></i></td>
                <td style="width: 120px" >'.$value['type'].'</td>
                <td style="width: 200px" >'.$value['name'].'</td>
                <td>'.$value['description'].'</td>
            </tr>';
    }

    echo '
        </table>
    </div>
</div>';
}
