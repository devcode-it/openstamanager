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

use Models\OperationLog;
use Modules\Aggiornamenti\IntegrityChecker;
use Modules\Aggiornamenti\Utils;

// Aggiunta della classe per il modulo
echo '<div class="module-aggiornamenti">';

$query_conflitti = [];

function saveQueriesToSession($queries)
{
    $_SESSION['query_conflitti'] = $queries;
}

// Funzioni per il controllo database (wrapper per compatibilità)
if (!function_exists('integrity_diff')) {
    function integrity_diff($expected, $current)
    {
        return IntegrityChecker::diff($expected, $current);
    }
}

if (!function_exists('settings_diff')) {
    function settings_diff($expected, $current)
    {
        return IntegrityChecker::settingsDiff($expected, $current);
    }
}

$file = basename(__FILE__);
$effettua_controllo = filter('effettua_controllo');

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
<div class="alert alert-warning alert-database">
    <i class="fa fa-warning"></i> '.tr('Impossibile effettuare controlli di integrità in assenza del file _FILE_', [
        '_FILE_' => '<b>'.$file_to_check_database.'</b>',
    ]).'.
</div>';

    return;
}

$info = Update::getDatabaseStructure();
$results = integrity_diff($data, $info);
$results_added = integrity_diff($info, $data);

$contents = file_get_contents(base_dir().'/settings.json');
$data_settings = json_decode($contents, true);

$settings = Update::getSettings();
$results_settings = settings_diff($data_settings, $settings);
$results_settings_added = settings_diff($settings, $data_settings);

if (!empty($results) || !empty($results_added) || !empty($results_settings) || !empty($results_settings_added)) {
    if ($results) {
        echo '
<div>
    <div class="alert alert-warning">
        <i class="fa fa-warning"></i> '.tr('Attenzione: questa funzionalità può presentare dei risultati falsamente positivi, sulla base del contenuto del file _FILE_ e la versione _MYSQL_VERSION_ di _DBMS_TYPE_ rilevata a sistema', [
            '_FILE_' => '<b>'.$file_to_check_database.'</b>',
            '_MYSQL_VERSION_' => '<b>'.$database->getMySQLVersion().'</b>',
            '_DBMS_TYPE_' => '<b>'.$database->getType().'</b>',
        ]).'.
    </div>
</div>';

        foreach ($results as $table => $errors) {
            $error_count = 0;
            $danger_count = 0;
            $warning_count = 0;
            $info_count = 0;
            $foreign_keys = $errors['foreign_keys'] ?: [];
            unset($errors['foreign_keys']);

            if (array_key_exists('current', $errors) && $errors['current'] == null) {
                $error_count = 1;
                $danger_count = 1;
            } else {
                // Conta i tipi di errori
                foreach ($errors as $name => $diff) {
                    if ($name === 'foreign_keys') {
                        continue;
                    }
                    if (array_key_exists('key', $diff)) {
                        if ($diff['key']['expected'] == '') {
                            $info_count++;
                        } else {
                            $danger_count++;
                        }
                    } elseif (array_key_exists('current', $diff) && is_null($diff['current'])) {
                        $danger_count++;
                    } else {
                        $warning_count++;
                    }
                }

                // Conta le chiavi esterne
                foreach ($foreign_keys as $name => $diff) {
                    if (is_array($diff) && isset($diff['expected'])) {
                        $danger_count++;
                    } elseif (is_array($diff) && isset($diff['current'])) {
                        $info_count++;
                    } else {
                        $warning_count++;
                    }
                }

                $error_count = $danger_count + $warning_count + $info_count;
            }

            $badge_html = Utils::generateBadgeHtml($danger_count, $warning_count, $info_count);
            $border_color = Utils::determineBorderColor($danger_count, $warning_count);

            echo '
<div class="mb-3">
    <div class="d-flex align-items-center justify-content-between p-2" style="background-color: #f8f9fa; border-left: 3px solid '.$border_color.'; cursor: pointer;" onclick="$(this).next().slideToggle();">
        <div>
            <strong>'.$table.'</strong>
            '.$badge_html.'
        </div>
        <i class="fa fa-chevron-down"></i>
    </div>
    <div style="display: none;">';

            if (array_key_exists('current', $errors) && $errors['current'] == null) {
                echo '
        <div class="alert alert-danger alert-database mb-2"><i class="fa fa-times"></i> '.tr('Tabella assente').'
        </div>';
            } else {
                if (!empty($errors) || !empty($foreign_keys)) {
                    echo '
        <div class="table-responsive">
            <table class="table table-hover table-striped table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>'.tr('Campo').'</th>
                        <th style="width: 150px; text-align: center;">'.tr('Tipo').'</th>
                        <th>'.tr('Soluzione').'</th>
                    </tr>
                </thead>

                <tbody>';
                    foreach ($errors as $name => $diff) {
                    if ($name === 'foreign_keys') {
                        continue;
                    }
                    $query = '';
                    $null = '';
                    $badge_text = '';
                    $badge_color = '';

                    if (array_key_exists('key', $diff)) {
                        if ($diff['key']['expected'] == '') {
                            $query = 'Chiave non prevista';
                            $badge_text = 'Chiave non prevista';
                            $badge_color = 'info';
                        } else {
                            $query = 'Chiave mancante';
                            $badge_text = 'Chiave mancante';
                            $badge_color = 'danger';
                        }
                    } elseif ($diff['current'] && array_key_exists('current', $diff['default']) && is_null($diff['default']['current'])) {
                        $query = 'ALTER TABLE `'.$table.'` ADD `'.$name.'` '.$data[$table][$name]['type'];

                        if ($data[$table][$name]['null'] == 'NO') {
                            $query .= ' NOT NULL';
                        } else {
                            $query .= ' NULL';
                        }

                        if ($data[$table][$name]['default']) {
                            $query .= ' DEFAULT '.$data[$table][$name]['default'];
                        }

                        if ($data[$table][$name]['extra']) {
                            $query .= ' '.str_replace('DEFAULT_GENERATED', '', $data[$table][$name]['extra']);
                        }

                        $query_conflitti[] = $query.';';
                        $badge_text = 'Campo mancante';
                        $badge_color = 'danger';
                    } else {
                        $query .= 'ALTER TABLE `'.$table;

                        if (array_key_exists('current', $diff) && is_null($diff['current'])) {
                            $query .= '` ADD `'.$name.'`';
                            $badge_text = 'Campo mancante';
                            $badge_color = 'danger';
                        } else {
                            $query .= '` CHANGE `'.$name.'` `'.$name.'` ';
                            $badge_text = 'Campo modificato';
                            $badge_color = 'warning';
                        }

                        $query .= $data[$table][$name]['type'];

                        if ($data[$table][$name]['null'] == 'NO') {
                            $null = 'NOT NULL';
                        } else {
                            $null = 'NULL';
                        }
                        $query .= str_replace('DEFAULT_GENERATED', ' ', $data[$table][$name]['extra']).' '.$null;
                        if ($data[$table][$name]['default']) {
                            $query .= ' DEFAULT '.$data[$table][$name]['default'];
                        }

                        $query_conflitti[] = $query.';';
                    }

                    echo '
        <tr>
            <td class="column-name">
                '.$name.'
            </td>
            <td style="text-align: center;">
                <span class="badge badge-'.$badge_color.'">'.$badge_text.'</span>
            </td>
            <td class="column-conflict">
                '.$query.'
            </td>
        </tr>';
                }

                    foreach ($foreign_keys as $name => $diff) {
                        $query = '';
                        $fk_name = $name;
                        $badge_text = '';
                        $badge_color = '';

                        // Gestione delle chiavi esterne
                        if (is_array($diff) && isset($diff['expected'])) {
                            // Chiave esterna mancante (presente in expected ma non in current)
                            if (is_array($diff['expected'])) {
                                $query = 'ALTER TABLE '.$table.' ADD CONSTRAINT '.$name.' FOREIGN KEY ('.$diff['expected']['column'].') REFERENCES '.$diff['expected']['referenced_table'].'(`'.$diff['expected']['referenced_column'].'`) ON DELETE '.$diff['expected']['delete_rule'].' ON UPDATE '.$diff['expected']['update_rule'].';';
                                $query_conflitti[] = $query;
                                $badge_text = 'Chiave esterna mancante';
                                $badge_color = 'danger';
                            }
                        } elseif (is_array($diff) && isset($diff['current'])) {
                            // Chiave esterna in più (presente in current ma non in expected)
                            $query = 'Chiave esterna non prevista';
                            $badge_text = 'Chiave esterna non prevista';
                            $badge_color = 'info';
                        } else {
                            // Chiave esterna modificata
                            $query = 'Chiave esterna modificata';
                            $badge_text = 'Chiave esterna modificata';
                            $badge_color = 'warning';
                        }

                        echo '
        <tr>
            <td class="column-name">
                '.$fk_name.'
            </td>
            <td style="text-align: center;">
                <span class="badge badge-'.$badge_color.'">'.$badge_text.'</span>
            </td>
            <td class="column-conflict">
                '.$query.'
            </td>
        </tr>';
                    }

                    echo '
                </tbody>
            </table>
        </div>';
                }
            }

            echo '
    </div>
</div>';
        }
    }

    if ($results_added) {
        foreach ($results_added as $table => $errors) {
            if (($results[$table] && array_keys($results[$table]) != array_keys($errors)) || (empty($results[$table]) && !empty($errors))) {
                $has_content = false;

                $table_not_expected = array_key_exists('current', $errors) && $errors['current'] == null;

                $has_keys = false;
                foreach ($errors as $name => $diff) {
                    if ($name != 'foreign_keys' && !isset($results[$table][$name]) && isset($diff['key'])) {
                        $has_keys = true;
                        break;
                    }
                }

                $foreign_keys = $errors['foreign_keys'] ?: [];
                $error_count = ($has_keys ? count(array_filter($errors, fn($e) => isset($e['key']))) : 0) + count($foreign_keys);

                if ($table_not_expected || $has_keys || !empty($foreign_keys)) {
                    echo '
<div class="mb-3">
    <div class="d-flex align-items-center justify-content-between p-2" style="background-color: #f8f9fa; border-left: 3px solid #17a2b8; cursor: pointer;" onclick="$(this).next().slideToggle();">
        <div>
            <strong>'.$table.'</strong>
            <span class="badge badge-info ml-2">'.$error_count.'</span>
        </div>
        <i class="fa fa-chevron-down"></i>
    </div>
    <div style="display: none;">';

                    if ($table_not_expected) {
                        echo '
        <div class="alert alert-danger alert-database mb-2"><i class="fa fa-times"></i> '.tr('Tabella non prevista').'
        </div>';
                    } else {
                        unset($errors['foreign_keys']);

                        if ($has_keys || !empty($foreign_keys)) {
                            echo '
        <div class="table-responsive">
            <table class="table table-hover table-striped table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>'.tr('Campo').'</th>
                        <th style="width: 150px; text-align: center;">'.tr('Tipo').'</th>
                        <th>'.tr('Soluzione').'</th>
                    </tr>
                </thead>

                <tbody>';

                            foreach ($errors as $name => $diff) {
                                $query = '';
                                $badge_text = '';
                                $badge_color = '';
                                if (!isset($results[$table][$name])) {
                                    if (isset($diff['key'])) {
                                        $query = 'Chiave non prevista';
                                        $badge_text = 'Chiave non prevista';
                                        $badge_color = 'info';

                                        echo '
                <tr>
                    <td class="column-name">
                        '.$name.'
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-'.$badge_color.'">'.$badge_text.'</span>
                    </td>
                    <td class="column-conflict">
                        '.$query.'
                    </td>
                </tr>';
                                    } else {
                                        // Campi non previsti
                                        $badge_text = 'Campo non previsto';
                                        $badge_color = 'info';
                                        echo '
                <tr>
                    <td class="column-name">
                        '.$name.'
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-'.$badge_color.'">'.$badge_text.'</span>
                    </td>
                    <td class="column-conflict">
                        Campo non previsto
                    </td>
                </tr>';
                                    }
                                }
                            }

                            foreach ($foreign_keys as $name => $diff) {
                                $query = '';
                                $fk_name = $name;
                                $badge_text = '';
                                $badge_color = '';

                                // Gestione delle chiavi esterne in più
                                if (is_array($diff) && isset($diff['current'])) {
                                    // Chiave esterna in più (presente in current ma non in expected)
                                    if (is_array($diff['current'])) {
                                        $query = 'ALTER TABLE '.$table.' DROP FOREIGN KEY '.$name.';';
                                        $query_conflitti[] = $query;
                                        $badge_text = 'Chiave esterna non prevista';
                                        $badge_color = 'info';
                                    } else {
                                        $query = 'Chiave esterna non prevista';
                                        $badge_text = 'Chiave esterna non prevista';
                                        $badge_color = 'info';
                                    }
                                } else {
                                    $query = 'Chiave esterna non prevista';
                                    $badge_text = 'Chiave esterna non prevista';
                                    $badge_color = 'info';
                                }

                                echo '
                <tr>
                    <td class="column-name">
                        '.$fk_name.'
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-'.$badge_color.'">'.$badge_text.'</span>
                    </td>
                    <td class="column-conflict">
                        '.$query.'
                    </td>
                </tr>';
                            }

                            echo '
                </tbody>
            </table>
        </div>';
                        }
                    }

                    echo '
    </div>
</div>';
                }
            }
        }
    }

    $campi_non_previsti = [];

    if ($results_added) {
        foreach ($results_added as $table => $errors) {
            if (!empty($errors) && (($results[$table] && array_keys($results[$table]) != array_keys($errors)) || (empty($results[$table]) && !empty($errors)))) {
                foreach ($errors as $name => $diff) {
                    if (!isset($results[$table][$name]) && !isset($diff['key']) && $name != 'foreign_keys') {
                        $campi_non_previsti[] = [
                            'tabella' => $table,
                            'campo' => $name,
                            'valore' => $diff['expected'] ?? '',
                        ];
                    }
                }
            }
        }
    }

    if ($results_settings || $results_settings_added) {
        $settings_danger_count = 0;
        $settings_warning_count = 0;
        $settings_info_count = 0;

        foreach ($results_settings as $key => $setting) {
            if (!$setting['current']) {
                $settings_danger_count++;
            } else {
                $settings_warning_count++;
            }
        }

        foreach ($results_settings_added as $key => $setting) {
            if ($setting['current'] == null) {
                $settings_info_count++;
            }
        }

        $settings_badge_html = Utils::generateBadgeHtml($settings_danger_count, $settings_warning_count, $settings_info_count);
        $settings_border_color = Utils::determineBorderColor($settings_danger_count, $settings_warning_count);

        echo '
<div class="mb-3">
    <div class="d-flex align-items-center justify-content-between p-2" style="background-color: #f8f9fa; border-left: 3px solid '.$settings_border_color.'; cursor: pointer;" onclick="$(this).next().slideToggle();">
        <div>
            <strong>zz_settings</strong>
            '.$settings_badge_html.'
        </div>
        <i class="fa fa-chevron-down"></i>
    </div>
    <div style="display: none;">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>'.tr('Nome').'</th>
                        <th style="width: 150px; text-align: center;">'.tr('Tipo').'</th>
                        <th>'.tr('Soluzione').'</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($results_settings as $key => $setting) {
            $badge_text = '';
            $badge_color = '';
            if (!$setting['current']) {
                $query = "INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('".$key."', '".$setting['expected']."', 'string', 1, 'Generali')";
                $query_conflitti[] = $query.';';
                $badge_text = 'Impostazione mancante';
                $badge_color = 'danger';
            } else {
                $query = 'UPDATE `zz_settings` SET `tipo` = '.prepare($setting['expected']).' WHERE `nome` = '.prepare($key);
                $query_conflitti[] = $query.';';
                $badge_text = 'Impostazione modificata';
                $badge_color = 'warning';
            }

            echo '
                    <tr>
                        <td class="column-name">
                            '.$key.'
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-'.$badge_color.'">'.$badge_text.'</span>
                        </td>
                        <td class="column-conflict">
                            '.$query.';
                        </td>
                    </tr>';
        }

        foreach ($results_settings_added as $key => $setting) {
            if ($setting['current'] == null) {
                $badge_text = 'Impostazione non prevista';
                $badge_color = 'info';
                echo '
                    <tr>
                        <td class="column-name">
                            '.$key.'
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-'.$badge_color.'">'.$badge_text.'</span>
                        </td>
                        <td class="column-conflict">
                            '.$setting['expected'].'
                        </td>
                    </tr>';
            }
        }

        echo '
                </tbody>
            </table>
        </div>
    </div>
</div>';
    }



    // Visualizza i campi non previsti raggruppati per tabella
    if (!empty($campi_non_previsti)) {
        // Raggruppa per tabella
        $campi_per_tabella = [];
        foreach ($campi_non_previsti as $campo) {
            $campi_per_tabella[$campo['tabella']][] = $campo['campo'];
        }

        foreach ($campi_per_tabella as $tabella => $campi) {
            echo '
<div class="mb-3">
    <div class="d-flex align-items-center justify-content-between p-2" style="background-color: #f8f9fa; border-left: 3px solid #17a2b8; cursor: pointer;" onclick="$(this).next().slideToggle();">
        <div>
            <strong>'.$tabella.'</strong>
            <span class="badge badge-info ml-2">'.count($campi).'</span>
        </div>
        <i class="fa fa-chevron-down"></i>
    </div>
    <div style="display: none;">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-sm mb-2">
                <thead class="thead-light">
                    <tr>
                        <th>'.tr('Campo').'</th>
                    </tr>
                </thead>
                <tbody>';
            foreach ($campi as $campo) {
                echo '
                    <tr>
                        <td class="column-name">'.$campo.'</td>
                    </tr>';
            }
            echo '
                </tbody>
            </table>
        </div>
    </div>
</div>';
        }
    }

} else {
    echo '
<div class="alert alert-info alert-database">
    <i class="fa fa-info-circle"></i> '.tr('Il database non presenta problemi di integrità').'.
</div>';
}

if (!empty($query_conflitti)) {
    echo '
<script>

function buttonLoading(button) {
    let $this = $(button);

    let result = [
        $this.html(),
        $this.attr("class")
    ];

    $this.html(\'<i class="fa fa-spinner fa-pulse fa-fw"></i>\');
    $this.addClass("btn-warning");
    $this.prop("disabled", true);

    return result;
}


function buttonRestore(button, loadingResult) {
    let $this = $(button);

    $this.html(loadingResult[0]);

    $this.attr("class", "");
    $this.addClass(loadingResult[1]);
    $this.prop("disabled", false);
}
</script>';
}

// Log dell'esecuzione del controllo database
OperationLog::setInfo('id_module', $id_module);
OperationLog::setInfo('options', json_encode(['controllo_name' => 'Controllo database'], JSON_UNESCAPED_UNICODE));
OperationLog::build('effettua_controllo');

// Chiusura del div module-aggiornamenti
echo '</div>';
