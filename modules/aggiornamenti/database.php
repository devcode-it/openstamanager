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

include_once __DIR__.'/../../core.php';

function integrity_diff($expected, $current)
{
    foreach ($expected as $key => $value) {
        if (array_key_exists($key, $current) && is_array($value)) {
            if (!is_array($current[$key])) {
                $difference[$key] = $value;
            } else {
                $new_diff = integrity_diff($value, $current[$key]);
                if (!empty($new_diff)) {
                    $difference[$key] = $new_diff;
                }
            }
        } elseif (!array_key_exists($key, $current) || $current[$key] != $value) {
            $difference[$key] = [
                'current' => $current[$key],
                'expected' => $value,
            ];
        }
    }

    return !isset($difference) ? [] : $difference;
}

function settings_diff($expected, $current)
{
    foreach ($expected as $key => $value) {
        if (array_key_exists($key, $current)) {
            if (!is_array($current[$key])) {
                if ($current[$key] !== $value) {
                    $difference[$key] = [
                        'current' => $current[$key],
                        'expected' => $value,
                    ];
                }
            } else {
                $new_diff = integrity_diff($value, $current[$key]);
                if (!empty($new_diff)) {
                    $difference[$key] = $new_diff;
                }
            }
        } else {
            $difference[$key] = [
                'current' => null,
                'expected' => $value,
            ];
        }
    }

    return $difference;
}

$file = basename(__FILE__);
$effettua_controllo = filter('effettua_controllo');

// Schermata di caricamento delle informazioni
if (empty($effettua_controllo)) {
    echo '
<div id="righe_controlli">
</div>

<div class="alert alert-info" id="card-loading">
    <i class="fa fa-spinner fa-spin"></i> '.tr('Caricamento in corso').'...
</div>

<script>
var content = $("#righe_controlli");
var loader = $("#card-loading");
$(document).ready(function () {
    loader.show();

    content.html("");
    content.load("'.$structure->fileurl($file).'?effettua_controllo=1", function() {
        loader.hide();
    });
})
</script>';

    return;
}

switch ($database->getType()) {
    case 'MariaDB':
        $file_to_check_database = 'mariadb_10_x.json';
        break;
    case 'MySQL':
        $mysql_min_version = '8.0.0';
        $mysql_max_version = '8.3.99';
        $file_to_check_database = ((version_compare($database->getMySQLVersion(), $mysql_min_version, '>=') && version_compare($database->getMySQLVersion(), $mysql_max_version, '<=')) ? 'mysql.json' : 'mysql_8_3.json');
        break;
    default:
        $file_to_check_database = 'mysql.json';
        break;
}

$contents = file_get_contents(base_dir().'/'.$file_to_check_database);
$data = json_decode($contents, true);

if (empty($data)) {
    echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr('Impossibile effettuare controlli di integrità in assenza del file _FILE_', [
        '_FILE_' => '<b>'.$file_to_check_database.'</b>',
    ]).'.
</div>';

    return;
}

// Controllo degli errori
$info = Update::getDatabaseStructure();
$results = integrity_diff($data, $info);
$results_added = integrity_diff($info, $data);

$contents = file_get_contents(base_dir().'/settings.json');
$data_settings = json_decode($contents, true);

$settings = Update::getSettings();
$results_settings = settings_diff($data_settings, $settings);
$results_settings_added = settings_diff($settings, $data_settings);

// Schermata di visualizzazione degli errori
if (!empty($results) || !empty($results_added) || !empty($results_settings) || !empty($results_settings_added)) {
    if ($results) {
        echo '
<p>'.tr("Segue l'elenco delle tabelle del database che presentano una struttura diversa rispetto a quella prevista nella versione ufficiale del gestionale").'.</p>
<div class="alert alert-warning">
    <i class="fa fa-warning"></i>
    '.tr('Attenzione: questa funzionalità può presentare dei risultati falsamente positivi, sulla base del contenuto del file _FILE_ e la versione di _MYSQL_VERSION_ di _DBMS_TYPE_ rilevata a sistema', [
            '_FILE_' => '<b>'.$file_to_check_database.'</b>',
            '_MYSQL_VERSION_' => '<b>'.$database->getMySQLVersion().'</b>',
            '_DBMS_TYPE_' => '<b>'.$database->getType().'</b>',
        ]).'.
</div>';

        foreach ($results as $table => $errors) {
            echo '
<h3>'.$table.'</h3>';

            if (array_key_exists('current', $errors) && $errors['current'] == null) {
                echo '
<div class="alert alert-danger" ><i class="fa fa-times"></i> '.tr('Tabella assente').'
</div>';
                continue;
            }

            $foreign_keys = $errors['foreign_keys'] ?: [];
            unset($errors['foreign_keys']);

            if (!empty($errors)) {
                echo '
<table class="table table-bordered">
    <thead>
        <tr>
            <th>'.tr('Colonna').'</th>
            <th>'.tr('Conflitto').'</th>
        </tr>
    </thead>

    <tbody>';
                foreach ($errors as $name => $diff) {
                    $query = '';
                    $null = '';
                    if (array_key_exists('key', $diff)) {
                        if ($diff['key']['expected'] == '') {
                            $query = 'Chiave non prevista';
                        } else {
                            $query = 'Chiave mancante';
                        }
                    } else {
                        $query .= 'ALTER TABLE `'.$table.'` CHANGE `'.$name.'` `'.$name.'` '.$data[$table][$name]['type'];
                        if ($data[$table][$name]['null'] == 'NO') {
                            $null = 'NOT NULL';
                        } else {
                            $null = 'NULL';
                        }
                        $query .= str_replace('DEFAULT_GENERATED', ' ', $data[$table][$name]['extra']).' '.$null;
                        if ($data[$table][$name]['default']) {
                            $query .= ' DEFAULT '.$data[$table][$name]['default'];
                        }
                    }

                    echo '
        <tr class="bg-warning" >
            <td>
                '.$name.'
            </td>
            <td>
                '.$query.';
            </td>
        </tr>';
                }

                echo '
    </tbody>
</table>';
            }

            if (!empty($foreign_keys)) {
                echo '
<table class="table table-bordered">
    <thead>
        <tr>
            <th>'.tr('Foreign keys').'</th>
            <th>'.tr('Conflitto').'</th>
        </tr>
    </thead>

    <tbody>';

                foreach ($foreign_keys as $name => $diff) {
                    echo '
        <tr class="bg-warning" >
            <td>
                '.($name ?: $diff['expected']['title']).'
            </td>
            <td>
                ALTER TABLE '.$table.' ADD  CONSTRAINT '.$name.' FOREIGN KEY ('.$diff['expected']['column'].') REFERENCES '.$diff['expected']['referenced_table'].'(`'.$diff['expected']['referenced_column'].'`) ON DELETE '.$diff['expected']['delete_rule'].' ON UPDATE '.$diff['expected']['update_rule'].';
            </td>
        </tr>';
                }

                echo '
    </tbody>
</table>';
            }
        }
    }

    if ($results_added) {
        foreach ($results_added as $table => $errors) {
            if (($results[$table] && array_keys($results[$table]) != array_keys($errors)) || (empty($results[$table]) && !empty($errors))) {
                echo '
<h3>'.$table.'</h3>';

                if (array_key_exists('current', $errors) && $errors['current'] == null) {
                    echo '
<div class="alert alert-danger" ><i class="fa fa-times"></i> '.tr('Tabella assente').'
</div>';
                    continue;
                }

                $foreign_keys = $errors['foreign_keys'] ?: [];
                unset($errors['foreign_keys']);

                if (!empty($errors)) {
                    echo '
<table class="table table-bordered">
    <thead>
        <tr>
            <th>'.tr('Colonna').'</th>
            <th>'.tr('Conflitto').'</th>
        </tr>
    </thead>

    <tbody>';

                    foreach ($errors as $name => $diff) {
                        $query = '';
                        if (!isset($results[$table][$name])) {
                            if (isset($diff['key'])) {
                                if ($diff['key']['expected'] == '') {
                                    $query = 'Chiave non prevista';
                                } else {
                                    $query = 'Chiave mancante';
                                }
                            } else {
                                $query = 'Campo non previsto';
                            }

                            echo '
        <tr class="bg-info" >
            <td>
                '.$name.'
            </td>
            <td>
                '.$query.'
            </td>
        </tr>';
                        }
                    }
                    echo '
    </tbody>
</table>';
                }

                if (!empty($foreign_keys)) {
                    echo '
<table class="table table-bordered">
    <thead>
        <tr>
            <th>'.tr('Foreign keys').'</th>
            <th>'.tr('Conflitto').'</th>
        </tr>
    </thead>

    <tbody>';

                    foreach ($foreign_keys as $name => $diff) {
                        echo '
        <tr class="bg-info" >
            <td>
                '.$name.'
            </td>
            <td>
                Chiave esterna non prevista
            </td>
        </tr>';
                    }

                    echo '
    </tbody>
</table>';
                }
            }
        }
    }

    if ($results_settings) {
        echo '
<table class="table table-bordered">
    <thead>
        <h3>Problemi impostazioni</h3>
        <tr>
            <th>'.tr('Nome').'</th>
            <th>'.tr('Valore attuale').'</th>
            <th>'.tr('Valore atteso').'</th>
        </tr>
    </thead>

    <tbody>';
        foreach ($results_settings as $key => $setting) {
            if (!$setting['current']) {
                $class = 'danger';
            } else {
                $class = 'warning';
            }
            echo '
        <tr class="bg-'.$class.'" >
            <td>
                '.$key.'
            </td>
            <td>
                '.($setting['current'] ?: '⚠️ Impostazione mancante').'
            </td>
            <td>
                '.$setting['expected'].'
            </td>
        </tr>';
        }
        echo '
    </tbody>
</table>';
    }

    if ($results_settings_added) {
        echo '
<table class="table table-bordered">
    <thead>
        <h3>Impostazioni non previste</h3>
        <tr>
            <th>'.tr('Nome').'</th>
            <th>'.tr('Valore attuale').'</th>
        </tr>
    </thead>
    <tbody>';
        foreach ($results_settings_added as $key => $setting) {
            if ($setting['current'] == null) {
                echo '
        <tr class="bg-info" >
            <td>
                '.$key.'
            </td>
            <td>
                '.$setting['expected'].'
            </td>
        </tr>';
            }
        }
        echo '
    </tbody>
</table>';
    }
} else {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr('Il database non presenta problemi di integrità').'.
</div>';
}
