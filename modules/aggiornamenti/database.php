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

// Aggiunta della classe per il modulo
echo '<div class="module-aggiornamenti">';

$query_conflitti = [];

function saveQueriesToSession($queries)
{
    $_SESSION['query_conflitti'] = $queries;
}

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
        } elseif (!array_key_exists($key, $current) || $current[$key] != $value && !empty($value)) {
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
<div class="row align-items-center">
    <div class="col-md-9">
        <p class="mb-0">'.tr("Segue l'elenco delle tabelle del database che presentano una struttura diversa rispetto a quella prevista nella versione ufficiale del gestionale").'.</p>
    </div>
    <div class="col-md-3 text-right">
        <button type="button" class="btn btn-warning" id="risolvi_conflitti">
            <i class="fa fa-database"></i> '.tr('Risolvi tutti i conflitti').'
        </button>
    </div>
</div>
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
            echo '
<h5 class="table-name">'.$table.'</h5>';

            if (array_key_exists('current', $errors) && $errors['current'] == null) {
                echo '
<div class="alert alert-danger alert-database"><i class="fa fa-times"></i> '.tr('Tabella assente').'
</div>';
                continue;
            }

            $foreign_keys = $errors['foreign_keys'] ?: [];
            unset($errors['foreign_keys']);

            if (!empty($errors)) {
                echo '
<table class="table table-bordered table-striped table-database">
    <thead>
        <tr>
            <th>'.tr('Colonna').'</th>
            <th>'.tr('Soluzione').'</th>
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
                    } else {
                        $query .= 'ALTER TABLE `'.$table;

                        if (array_key_exists('current', $diff) && is_null($diff['current'])) {
                            $query .= '` ADD `'.$name.'`';
                        } else {
                            $query .= '` CHANGE `'.$name.'` `'.$name.'` ';
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
        <tr class="row-warning">
            <td class="column-name">
                '.$name.'
            </td>
            <td class="column-conflict">
                '.$query.'
            </td>
        </tr>';
                }
                echo '
    </tbody>
</table>';
            }

            if (!empty($foreign_keys)) {
                echo '
<table class="table table-bordered table-striped table-database">
    <thead>
        <tr>
            <th>'.tr('Foreign keys').'</th>
            <th>'.tr('Soluzione').'</th>
        </tr>
    </thead>

    <tbody>';

                foreach ($foreign_keys as $name => $diff) {
                    $query = '';

                    $query = 'ALTER TABLE '.$table.' ADD CONSTRAINT '.$name.' FOREIGN KEY ('.$diff['expected']['column'].') REFERENCES '.$diff['expected']['referenced_table'].'(`'.$diff['expected']['referenced_column'].'`) ON DELETE '.$diff['expected']['delete_rule'].' ON UPDATE '.$diff['expected']['update_rule'].';';
                    $query_conflitti[] = $query;

                    echo '
        <tr class="row-warning">
            <td class="column-name">
                '.($name ?: ($diff['expected']['title'] ?? $name)).'
            </td>
            <td class="column-conflict">
                '.$query.'
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

                if ($table_not_expected || $has_keys || !empty($foreign_keys)) {
                    echo '
<h5 class="table-name">'.$table.'</h5>';

                    if ($table_not_expected) {
                        echo '
<div class="alert alert-danger alert-database"><i class="fa fa-times"></i> '.tr('Tabella non prevista').'
</div>';
                        continue;
                    }

                    unset($errors['foreign_keys']);

                    if ($has_keys) {
                        echo '
<table class="table table-bordered table-striped table-database">
    <thead>
        <tr>
            <th>'.tr('Colonna').'</th>
            <th>'.tr('Soluzione').'</th>
        </tr>
    </thead>

    <tbody>';

                        foreach ($errors as $name => $diff) {
                            $query = '';
                            if (!isset($results[$table][$name])) {
                                if (isset($diff['key'])) {
                                    $query = 'Chiave non prevista';

                                    echo '
        <tr class="row-info">
            <td class="column-name">
                '.$name.'
            </td>
            <td class="column-conflict">
                '.$query.'
            </td>
        </tr>';
                                }
                            }
                        }
                        echo '
    </tbody>
</table>';
                    }
                }

                if (!empty($foreign_keys)) {
                    echo '
<table class="table table-bordered table-striped table-database">
    <thead>
        <tr>
            <th>'.tr('Foreign keys').'</th>
            <th>'.tr('Soluzione').'</th>
        </tr>
    </thead>

    <tbody>';

                    foreach ($foreign_keys as $name => $diff) {
                        $query = '';
                        $query = 'Chiave esterna non prevista';

                        echo '
        <tr class="row-warning">
            <td class="column-name">
                '.$name.'
            </td>
            <td class="column-conflict">
                '.$query.($query !== 'Chiave esterna non prevista' ? ';' : '').'
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

    if ($results_settings) {
        echo '
<h4 class="table-title">Problemi impostazioni</h4>
<table class="table table-bordered table-striped table-database">
    <thead>
        <tr>
            <th>'.tr('Nome').'</th>
            <th>'.tr('Soluzione').'</th>
        </tr>
    </thead>

    <tbody>';
        foreach ($results_settings as $key => $setting) {
            if (!$setting['current']) {
                $class = 'danger';

                $query = "INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('".$key."', '".$setting['expected']."', 'string', 1, 'Generali')";
                $query_conflitti[] = $query.';';
            } else {
                $class = 'warning';

                $query = 'UPDATE `zz_settings` SET `tipo` = '.prepare($setting['expected']).' WHERE `nome` = '.prepare($key);
                $query_conflitti[] = $query.';';
            }

            echo '
        <tr class="row-warning">
            <td class="column-name">
                '.$key.'
            </td>
            <td class="column-conflict">
                '.$query.';
            </td>
        </tr>';
        }
        echo '
    </tbody>
</table>';
    }

    if ($results_settings_added) {
        echo '
<h4 class="table-title">Impostazioni non previste</h4>
<table class="table table-bordered table-striped table-database">
    <thead>
        <tr>
            <th>'.tr('Nome').'</th>
            <th>'.tr('Valore attuale').'</th>
        </tr>
    </thead>
    <tbody>';
        foreach ($results_settings_added as $key => $setting) {
            if ($setting['current'] == null) {
                echo '
        <tr class="row-info">
            <td class="column-name">
                '.$key.'
            </td>
            <td class="column-conflict">
                '.$setting['expected'].'
            </td>
        </tr>';
            }
        }
        echo '
    </tbody>
</table>';
    }

    if (!empty($campi_non_previsti)) {
        echo '
<h4 class="table-title">Campi non previsti</h4>
<table class="table table-bordered table-striped table-database">
    <thead>
        <tr>
            <th>'.tr('Tabella').'</th>
            <th>'.tr('Campo').'</th>
        </tr>
    </thead>
    <tbody>';
        foreach ($campi_non_previsti as $campo) {
            echo '
        <tr class="row-info">
            <td class="column-name">
                '.$campo['tabella'].'
            </td>
            <td class="column-conflict">
                '.$campo['campo'].'
            </td>
        </tr>';
        }
        echo '
    </tbody>
</table>';
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

$(document).ready(function() {
    $("#risolvi_conflitti").on("click", function() {
        var button = $(this);

        swal({
            title: "'.tr('Sei sicuro?').'",
            html: "'.tr('Verranno eseguite tutte le query per risolvere i conflitti del database. Questa operazione potrebbe modificare la struttura del database. Si consiglia di effettuare un backup prima di procedere.').'",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "'.tr('Sì, procedi').'",
            cancelButtonText: "'.tr('Annulla').'",
            confirmButtonClass: "btn btn-lg btn-warning",
            cancelButtonClass: "btn btn-lg btn-default",
            buttonsStyling: false,
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve) {

                    var loadingResult = buttonLoading(button);


                    var queries = [];


                    $(".row-warning .column-conflict").each(function() {
                        var query = $(this).text().trim();
                        if (query &&
                            query !== "Chiave non prevista" &&
                            query !== "Chiave esterna non prevista" &&
                            query !== "Chiave mancante" &&
                            !query.startsWith("query=")) {

                            if (!query.endsWith(";")) {
                                query += ";";
                            }
                            queries.push(query);
                        }
                    });

                    $.ajax({
                        url: globals.rootdir + "/actions.php",
                        type: "POST",
                        dataType: "JSON",
                        data: {
                            id_module: globals.id_module,
                            op: "risolvi-conflitti-database",
                            queries: JSON.stringify(queries)
                        },
                        success: function(response) {
                            buttonRestore(button, loadingResult);
                            resolve(response);
                        },
                        error: function(xhr, status, error) {
                            buttonRestore(button, loadingResult);
                            swal.showValidationError(
                                "'.tr('Si è verificato un errore durante l\'esecuzione delle query').':<br>" + error
                            );
                            resolve();
                        }
                    });
                });
            },
            allowOutsideClick: false
        }).then(function () {
            location.reload(true);
        });
    });
});
</script>';
}

// Chiusura del div module-aggiornamenti
echo '</div>';
