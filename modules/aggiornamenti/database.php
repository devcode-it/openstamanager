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

$query_conflitti = [];

// ========================================================================
// FUNZIONI HELPER PER CONTROLLO DATABASE
// ========================================================================

function saveQueriesToSession($queries)
{
    $_SESSION['query_conflitti'] = $queries;
}

/*
 * Determina il file di riferimento database in base al tipo di DBMS
 * (Definita anche in edit.php, ma qui con if !function_exists per evitare conflitti)
 */
if (!function_exists('getDatabaseReferenceFile')) {
    function getDatabaseReferenceFile($database)
    {
        switch ($database->getType()) {
            case 'MariaDB':
                return 'mariadb_10_x.json';
            case 'MySQL':
                $mysql_min_version = '8.0.0';
                $mysql_max_version = '8.3.99';
                $version = $database->getMySQLVersion();

                return (version_compare($version, $mysql_min_version, '>=') && version_compare($version, $mysql_max_version, '<='))
                    ? 'mysql.json'
                    : 'mysql_8_3.json';
            default:
                return 'mysql.json';
        }
    }
}

/**
 * Carica il file di riferimento database principale.
 */
function loadMainDatabaseReference($file_to_check_database)
{
    if (!file_exists(base_dir().'/'.$file_to_check_database)) {
        return null;
    }

    $contents = file_get_contents(base_dir().'/'.$file_to_check_database);
    $data = json_decode($contents, true);

    return is_array($data) ? $data : [];
}

/**
 * Rimuove i campi premium dai dati di riferimento.
 */
function removePremiumFieldsFromData(&$data, $premium_fields, $premium_foreign_keys)
{
    foreach ($premium_fields as $table => $fields) {
        if (isset($data[$table])) {
            foreach ($fields as $field_name => $premium_info) {
                if ($field_name !== 'foreign_keys') {
                    unset($data[$table][$field_name]);
                }
            }
        }
    }

    foreach ($premium_foreign_keys as $table => $fks) {
        if (isset($data[$table]['foreign_keys'])) {
            foreach ($fks as $fk_name => $premium_info) {
                unset($data[$table]['foreign_keys'][$fk_name]);
            }
        }
    }
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

// ========================================================================
// LOGICA PRINCIPALE
// ========================================================================

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

$file_to_check_database = getDatabaseReferenceFile($database);

// Carica il file di riferimento principale per il database
$data = loadMainDatabaseReference($file_to_check_database);

if ($data === null) {
    echo '<div class="alert alert-danger alert-database"><i class="fa fa-times"></i> '.tr('File di riferimento del database non trovato: _FILE_', ['_FILE_' => '<b>'.$file_to_check_database.'</b>']).'.'.
    '</div>';

    return;
}

// Carica e accoda le definizioni del database dai file JSON presenti nelle sottocartelle di moduli e plugin
$database_reference_data = aggiornamentiMergeDatabaseReferenceData($data, $file_to_check_database);
$data = $database_reference_data['data'];
$premium_fields = $database_reference_data['premium_fields'];
$premium_foreign_keys = $database_reference_data['premium_foreign_keys'];

// Rimuovi i campi premium dai dati di riferimento
removePremiumFieldsFromData($data, $premium_fields, $premium_foreign_keys);

if (empty($data)) {
    echo '<div class="alert alert-warning alert-database"><i class="fa fa-warning"></i> '.tr('Impossibile effettuare controlli di integrità in assenza del file _FILE_', ['_FILE_' => '<b>'.$file_to_check_database.'</b>']).'.'.
    '</div>';

    return;
}

try {
    $info = Update::getDatabaseStructure();
    $results = integrity_diff($data, $info);
    $results_added = integrity_diff($info, $data);
} catch (Exception $e) {
    echo '
<div class="alert alert-danger alert-database">
    <i class="fa fa-times"></i> '.tr('Errore durante il recupero della struttura del database: _ERROR_', [
        '_ERROR_' => htmlspecialchars($e->getMessage()),
    ]).'
</div>';

    return;
}

// Funzione helper per raggruppare gli errori per tabella
function groupErrorsByTable($results, $results_added, $premium_fields, $premium_foreign_keys, $data)
{
    $grouped = [];

    // Processa i risultati principali (campi mancanti/modificati)
    if ($results) {
        foreach ($results as $table => $errors) {
            if (!isset($grouped[$table])) {
                $grouped[$table] = [
                    'campi_mancanti' => [],
                    'campi_modificati' => [],
                    'campi_non_previsti' => [],
                    'chiavi_mancanti' => [],
                    'chiavi_non_previste' => [],
                    'chiavi_esterne_mancanti' => [],
                    'chiavi_esterne_non_previste' => [],
                    'chiavi_esterne_modificate' => [],
                    'tabella_assente' => false,
                ];
            }

            // Verifica se la tabella è assente
            if (array_key_exists('current', $errors) && $errors['current'] == null) {
                $grouped[$table]['tabella_assente'] = true;
                continue;
            }

            $foreign_keys = $errors['foreign_keys'] ?? [];
            unset($errors['foreign_keys']);

            // Processa i campi
            foreach ($errors as $name => $diff) {
                // Salta i campi premium
                if (isset($premium_fields[$table][$name])) {
                    continue;
                }

                if (array_key_exists('key', $diff)) {
                    if ($diff['key']['expected'] == '') {
                        $grouped[$table]['chiavi_non_previste'][$name] = $diff;
                    } else {
                        $grouped[$table]['chiavi_mancanti'][$name] = $diff;
                    }
                } elseif (array_key_exists('current', $diff) && is_null($diff['current'])) {
                    $grouped[$table]['campi_mancanti'][$name] = $diff;
                } else {
                    $grouped[$table]['campi_modificati'][$name] = $diff;
                }
            }

            // Processa le chiavi esterne
            $expected_fks = $data[$table]['foreign_keys'] ?? [];
            foreach ($foreign_keys as $name => $diff) {
                // Salta le chiavi esterne premium
                if (isset($premium_foreign_keys[$table][$name])) {
                    continue;
                }

                if (is_array($diff) && isset($diff['expected'])) {
                    $grouped[$table]['chiavi_esterne_mancanti'][$name] = $diff;
                } elseif (is_array($diff) && isset($diff['current'])) {
                    if (!IntegrityChecker::foreignKeyExistsByContent($diff['current'], $expected_fks)) {
                        $grouped[$table]['chiavi_esterne_non_previste'][$name] = $diff;
                    }
                } else {
                    $grouped[$table]['chiavi_esterne_modificate'][$name] = $diff;
                }
            }
        }
    }

    // Processa i risultati aggiunti (campi non previsti)
    if ($results_added) {
        foreach ($results_added as $table => $errors) {
            if (!isset($grouped[$table])) {
                $grouped[$table] = [
                    'campi_mancanti' => [],
                    'campi_modificati' => [],
                    'campi_non_previsti' => [],
                    'chiavi_mancanti' => [],
                    'chiavi_non_previste' => [],
                    'chiavi_esterne_mancanti' => [],
                    'chiavi_esterne_non_previste' => [],
                    'chiavi_esterne_modificate' => [],
                    'tabella_assente' => false,
                ];
            }

            $foreign_keys = $errors['foreign_keys'] ?? [];
            unset($errors['foreign_keys']);

            // Processa i campi non previsti
            foreach ($errors as $name => $diff) {
                if (!isset($results[$table][$name])) {
                    if (isset($diff['key'])) {
                        // Chiave non prevista
                        if (!isset($premium_fields[$table][$name])) {
                            $grouped[$table]['chiavi_non_previste'][$name] = $diff;
                        }
                    } elseif ($name != 'foreign_keys') {
                        // Campo non previsto
                        if (!isset($premium_fields[$table][$name])) {
                            $grouped[$table]['campi_non_previsti'][$name] = $diff;
                        }
                    }
                }
            }

            // Processa le chiavi esterne non previste
            $expected_fks = $data[$table]['foreign_keys'] ?? [];
            foreach ($foreign_keys as $name => $diff) {
                // Salta le chiavi esterne premium
                if (isset($premium_foreign_keys[$table][$name])) {
                    continue;
                }

                if (is_array($diff) && isset($diff['current'])) {
                    if (!IntegrityChecker::foreignKeyExistsByContent($diff['current'], $expected_fks)) {
                        $grouped[$table]['chiavi_esterne_non_previste'][$name] = $diff;
                    }
                }
            }
        }
    }

    return $grouped;
}

// Funzione helper per generare la query SQL per un campo mancante
function generateAddFieldQuery($table, $name, $data)
{
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

    return $query.';';
}

// Funzione helper per generare la query SQL per un campo modificato
function generateModifyFieldQuery($table, $name, $data)
{
    $query = 'ALTER TABLE `'.$table.'` CHANGE `'.$name.'` `'.$name.'` '.$data[$table][$name]['type'];

    if ($data[$table][$name]['null'] == 'NO') {
        $null = ' NOT NULL';
    } else {
        $null = ' NULL';
    }
    $query .= str_replace('DEFAULT_GENERATED', ' ', ' '.$data[$table][$name]['extra']).' '.$null;
    if ($data[$table][$name]['default']) {
        $query .= ' DEFAULT '.$data[$table][$name]['default'];
    }

    return $query.';';
}

// Funzione helper per generare la query SQL per una chiave esterna mancante
function generateAddForeignKeyQuery($table, $name, $diff)
{
    return 'ALTER TABLE '.$table.' ADD CONSTRAINT '.$name.' FOREIGN KEY ('.$diff['expected']['column'].') REFERENCES '.$diff['expected']['referenced_table'].'(`'.$diff['expected']['referenced_column'].'`) ON DELETE '.$diff['expected']['delete_rule'].' ON UPDATE '.$diff['expected']['update_rule'].';';
}

// Funzione helper per generare la query SQL per una chiave esterna non prevista
function generateDropForeignKeyQuery($table, $name)
{
    return 'ALTER TABLE '.$table.' DROP FOREIGN KEY '.$name.';';
}

// Funzione helper per renderizzare una riga della tabella
function renderTableRow($name, $badge_text, $badge_color, $query, $is_premium = false, $module_name = '')
{
    if ($is_premium) {
        $premium_type = is_array($module_name) ? ($module_name['type'] ?? 'module') : 'module';
        $premium_name = is_array($module_name) ? ($module_name['name'] ?? '') : $module_name;
        $premium_label = ($premium_type === 'plugin') ? 'Campo plugin ' : 'Campo modulo ';
        $badge_html = '<span class="badge badge-primary">'.$premium_label.$premium_name.'</span>';
    } else {
        $badge_html = '<span class="badge badge-'.$badge_color.'">'.$badge_text.'</span>';
    }

    return '
        <tr>
            <td class="column-name">'.$name.'</td>
            <td class="text-center">'.$badge_html.'</td>
            <td class="column-conflict">'.$query.'</td>
        </tr>';
}

// Funzione helper per renderizzare una sezione di errori
function renderErrorSection($title, $items, $table, $data, &$query_conflitti, $error_type, $premium_fields = [])
{
    if (empty($items)) {
        return '';
    }

    $html = '
        <div class="error-subsection mt-3">
            <h6 class="text-'.$error_type['color'].'"><i class="fa fa-'.$error_type['icon'].'"></i> '.$title.' ('.count($items).')</h6>
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>'.tr('Campo').'</th>
                            <th class="module-aggiornamenti table-col-type">'.tr('Tipo').'</th>
                            <th>'.tr('Soluzione').'</th>
                        </tr>
                    </thead>
                    <tbody>';

    foreach ($items as $name => $diff) {
        $query = '';
        $badge_text = '';
        $badge_color = $error_type['color'];

        switch ($error_type['type']) {
            case 'campo_mancante':
                $query = generateAddFieldQuery($table, $name, $data);
                $query_conflitti[] = $query;
                $badge_text = 'Campo mancante';
                break;
            case 'campo_modificato':
                $query = generateModifyFieldQuery($table, $name, $data);
                $query_conflitti[] = $query;
                $badge_text = 'Campo modificato';
                break;
            case 'campo_non_previsto':
                $query = '';
                $badge_text = 'Campo non previsto';
                break;
            case 'chiave_mancante':
                $query = 'Chiave mancante';
                $badge_text = 'Chiave mancante';
                break;
            case 'chiave_non_prevista':
                $query = 'Chiave non prevista';
                $badge_text = 'Chiave non prevista';
                break;
            case 'chiave_esterna_mancante':
                $query = generateAddForeignKeyQuery($table, $name, $diff);
                $query_conflitti[] = $query;
                $badge_text = 'Chiave esterna mancante';
                break;
            case 'chiave_esterna_non_prevista':
                if (is_array($diff['current'])) {
                    $query = generateDropForeignKeyQuery($table, $name);
                    $query_conflitti[] = $query;
                } else {
                    $query = 'Chiave esterna non prevista';
                }
                $badge_text = 'Chiave esterna non prevista';
                break;
            case 'chiave_esterna_modificata':
                $query = 'Chiave esterna modificata';
                $badge_text = 'Chiave esterna modificata';
                break;
        }

        $html .= renderTableRow($name, $badge_text, $badge_color, $query);
    }

    $html .= '
                    </tbody>
                </table>
            </div>
        </div>';

    return $html;
}

// Funzione helper per renderizzare una tabella unificata per tutti gli errori di una tabella
function renderUnifiedTable($errors, $table, $data, &$query_conflitti)
{
    $html = '
        <div class="table-responsive">
            <table class="table table-hover table-striped table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>'.tr('Campo').'</th>
                        <th class="module-aggiornamenti table-col-type">'.tr('Tipo').'</th>
                        <th>'.tr('Soluzione').'</th>
                    </tr>
                </thead>
                <tbody>';

    // Combina tutti gli errori in un unico array con il tipo
    $all_errors = [];

    // Campi mancanti
    foreach ($errors['campi_mancanti'] ?? [] as $name => $diff) {
        $query = generateAddFieldQuery($table, $name, $data);
        $query_conflitti[] = $query;
        $all_errors[$name] = [
            'type' => 'danger',
            'text' => 'Campo mancante',
            'query' => $query,
        ];
    }

    // Campi modificati
    foreach ($errors['campi_modificati'] ?? [] as $name => $diff) {
        $query = generateModifyFieldQuery($table, $name, $data);
        $query_conflitti[] = $query;
        $all_errors[$name] = [
            'type' => 'warning',
            'text' => 'Campo modificato',
            'query' => $query,
        ];
    }

    // Campi non previsti
    foreach ($errors['campi_non_previsti'] ?? [] as $name => $diff) {
        $all_errors[$name] = [
            'type' => 'info',
            'text' => 'Campo non previsto',
            'query' => '',
        ];
    }

    // Chiavi mancanti
    foreach ($errors['chiavi_mancanti'] ?? [] as $name => $diff) {
        $all_errors[$name] = [
            'type' => 'danger',
            'text' => 'Chiave mancante',
            'query' => 'Chiave mancante',
        ];
    }

    // Chiavi non previste
    foreach ($errors['chiavi_non_previste'] ?? [] as $name => $diff) {
        $all_errors[$name] = [
            'type' => 'info',
            'text' => 'Chiave non prevista',
            'query' => 'Chiave non prevista',
        ];
    }

    // Chiavi esterne mancanti
    foreach ($errors['chiavi_esterne_mancanti'] ?? [] as $name => $diff) {
        $query = generateAddForeignKeyQuery($table, $name, $diff);
        $query_conflitti[] = $query;
        $all_errors[$name] = [
            'type' => 'danger',
            'text' => 'Chiave esterna mancante',
            'query' => $query,
        ];
    }

    // Chiavi esterne non previste
    foreach ($errors['chiavi_esterne_non_previste'] ?? [] as $name => $diff) {
        if (is_array($diff['current'])) {
            $query = generateDropForeignKeyQuery($table, $name);
            $query_conflitti[] = $query;
        } else {
            $query = 'Chiave esterna non prevista';
        }
        $all_errors[$name] = [
            'type' => 'info',
            'text' => 'Chiave esterna non prevista',
            'query' => $query,
        ];
    }

    // Chiavi esterne modificate
    foreach ($errors['chiavi_esterne_modificate'] ?? [] as $name => $diff) {
        $all_errors[$name] = [
            'type' => 'warning',
            'text' => 'Chiave esterna modificata',
            'query' => 'Chiave esterna modificata',
        ];
    }

    // Renderizza tutte le righe
    foreach ($all_errors as $name => $error) {
        $html .= renderTableRow($name, $error['text'], $error['type'], $error['query']);
    }

    $html .= '
                </tbody>
            </table>
        </div>';

    return $html;
}

// Raggruppa gli errori per tabella
$grouped_errors = groupErrorsByTable($results, $results_added, $premium_fields, $premium_foreign_keys, $data);

if (!empty($grouped_errors)) {
    echo '
<div>
    <div class="alert alert-warning">
        <i class="fa fa-exclamation-triangle"></i> '.tr('Attenzione: questa funzionalità può presentare dei risultati falsamente positivi, sulla base del contenuto del file _FILE_ e la versione _MYSQL_VERSION_ di _DBMS_TYPE_ rilevata a sistema', [
        '_FILE_' => '<b>'.$file_to_check_database.'</b>',
        '_MYSQL_VERSION_' => '<b>'.$database->getMySQLVersion().'</b>',
        '_DBMS_TYPE_' => '<b>'.$database->getType().'</b>',
    ]).'.
    </div>
</div>';

    // Prepara un array per tracciare quali tabelle hanno già una card
    $tables_with_card = [];

    foreach ($grouped_errors as $table => $errors) {
        // Calcola i conteggi
        $danger_count = count($errors['campi_mancanti'] ?? []) + count($errors['chiavi_mancanti'] ?? []) + count($errors['chiavi_esterne_mancanti'] ?? []);
        $warning_count = count($errors['campi_modificati'] ?? []) + count($errors['chiavi_esterne_modificate'] ?? []);
        $info_count = count($errors['campi_non_previsti'] ?? []) + count($errors['chiavi_non_previste'] ?? []) + count($errors['chiavi_esterne_non_previste'] ?? []);
        $error_count = $danger_count + $warning_count + $info_count;

        // Salta se non ci sono errori
        if ($error_count == 0) {
            continue;
        }

        // Aggiungi i campi premium e le chiavi esterne premium ai conteggi
        $premium_fields_count = isset($premium_fields[$table]) ? count(array_filter(array_keys($premium_fields[$table]), fn ($k) => $k !== 'foreign_keys')) : 0;
        $premium_fks_count = isset($premium_foreign_keys[$table]) ? count($premium_foreign_keys[$table]) : 0;

        // Aggiorna i conteggi se ci sono elementi premium
        if ($premium_fields_count > 0 || $premium_fks_count > 0) {
            $info_count += $premium_fields_count + $premium_fks_count;
            $error_count = $danger_count + $warning_count + $info_count;
        }

        $badge_html = Utils::generateBadgeHtml($danger_count, $warning_count, $info_count);
        $border_color = Utils::determineBorderColor($danger_count, $warning_count);

        echo '
<div class="mb-3">
    <div class="d-flex align-items-center justify-content-between p-2 module-aggiornamenti db-section-header" style="border-left-color: '.$border_color.';" onclick="$(this).next().slideToggle(); return false;">
        <div>
            <strong>'.$table.'</strong>
            '.$badge_html.'
        </div>
        <i class="fa fa-chevron-down"></i>
    </div>
    <div class="module-aggiornamenti db-section-content" style="display: none;">';

        // Se la tabella è assente
        if ($errors['tabella_assente']) {
            echo '
        <div class="alert alert-danger alert-database mb-2"><i class="fa fa-times"></i> '.tr('Tabella assente').'
        </div>';
        } else {
            // Renderizza una tabella unificata per tutti gli errori
            echo renderUnifiedTable($errors, $table, $data, $query_conflitti);
        }

        // Aggiungi i campi premium e le chiavi esterne premium alla fine della tabella se esistono
        $premium_fields_count = isset($premium_fields[$table]) ? count(array_filter(array_keys($premium_fields[$table]), fn ($k) => $k !== 'foreign_keys')) : 0;
        $premium_fks_count = isset($premium_foreign_keys[$table]) ? count($premium_foreign_keys[$table]) : 0;

        if ($premium_fields_count > 0 || $premium_fks_count > 0) {
            echo '
        <div class="table-responsive">
            <table class="table table-hover table-striped table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>'.tr('Campo').'</th>
                        <th>'.tr('Componente').'</th>
                    </tr>
                </thead>
                    <tbody>';

            // Aggiungi i campi premium
            if (isset($premium_fields[$table]) && !empty($premium_fields[$table])) {
                foreach ($premium_fields[$table] as $field_name => $premium_info) {
                    // Salta le chiavi esterne (vengono gestite qui sotto)
                    if ($field_name === 'foreign_keys') {
                        continue;
                    }
                    $origine_type = is_array($premium_info) ? ($premium_info['type'] ?? 'module') : 'module';
                    $origine_name = is_array($premium_info) ? ($premium_info['name'] ?? '') : $premium_info;
                    $origine_label = ($origine_type === 'plugin') ? 'Campo plugin ' : 'Campo modulo ';
                    echo '
                        <tr>
                            <td class="column-name">'.$field_name.'</td>
                            <td><span class="badge badge-primary">'.$origine_label.$origine_name.'</span></td>
                        </tr>';
                }
            }

            // Aggiungi le chiavi esterne premium
            if (isset($premium_foreign_keys[$table]) && !empty($premium_foreign_keys[$table])) {
                foreach ($premium_foreign_keys[$table] as $fk_name => $premium_info) {
                    $origine_type = is_array($premium_info) ? ($premium_info['type'] ?? 'module') : 'module';
                    $origine_name = is_array($premium_info) ? ($premium_info['name'] ?? '') : $premium_info;
                    $origine_label = ($origine_type === 'plugin') ? 'Chiave esterna plugin ' : 'Chiave esterna modulo ';
                    echo '
                        <tr>
                            <td class="column-name">'.$fk_name.'</td>
                            <td><span class="badge badge-primary">'.$origine_label.$origine_name.'</span></td>
                        </tr>';
                }
            }

            echo '
                    </tbody>
                </table>
            </div>';
        }

        echo '
    </div>
</div>';

        // Segna questa tabella come già processata
        $tables_with_card[$table] = true;
    }
} else {
    echo '
<div class="alert alert-info alert-database">
    <i class="fa fa-info-circle"></i> '.tr('Il database non presenta problemi di integrità').'
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

// Visualizza le tabelle che hanno solo elementi premium (senza altri errori)
foreach ($premium_fields as $table => $fields) {
    // Salta se questa tabella è già stata processata
    if (isset($tables_with_card[$table])) {
        continue;
    }

    // Controlla se ci sono campi premium (escludendo le chiavi esterne)
    $premium_fields_count = count(array_filter(array_keys($fields), fn ($k) => $k !== 'foreign_keys'));
    $premium_fks_count = isset($premium_foreign_keys[$table]) ? count($premium_foreign_keys[$table]) : 0;

    if ($premium_fields_count == 0 && $premium_fks_count == 0) {
        continue;
    }

    echo '
<div class="mb-3">
<div class="d-flex align-items-center justify-content-between p-2 module-aggiornamenti db-section-header-dynamic" style="border-left-color: #007bff;" onclick="$(this).next().slideToggle(); return false;">
    <div>
        <strong>'.$table.'</strong>
        <span class="badge badge-primary ml-2">'.($premium_fields_count + $premium_fks_count).'</span>
    </div>
    <i class="fa fa-chevron-down"></i>
</div>
<div class="module-aggiornamenti db-section-content" style="display: none;">';

    // Aggiungi i campi premium e le chiavi esterne premium in una sola tabella
    if ($premium_fields_count > 0 || $premium_fks_count > 0) {
        echo '
    <div class="table-responsive">
        <table class="table table-hover table-striped table-sm">
            <thead class="thead-light">
                <tr>
                    <th>'.tr('Campo').'</th>
                    <th>'.tr('Componente').'</th>
                </tr>
            </thead>
                <tbody>';

        // Aggiungi i campi premium
        if ($premium_fields_count > 0) {
            foreach ($fields as $field_name => $premium_info) {
                // Salta le chiavi esterne (vengono gestite qui sotto)
                if ($field_name === 'foreign_keys') {
                    continue;
                }
                $origine_type = is_array($premium_info) ? ($premium_info['type'] ?? 'module') : 'module';
                $origine_name = is_array($premium_info) ? ($premium_info['name'] ?? '') : $premium_info;
                $origine_label = ($origine_type === 'plugin') ? 'Campo plugin ' : 'Campo modulo ';
                echo '
                    <tr>
                        <td class="column-name">'.$field_name.'</td>
                        <td><span class="badge badge-primary">'.$origine_label.$origine_name.'</span></td>
                    </tr>';
            }
        }

        // Aggiungi le chiavi esterne premium
        if ($premium_fks_count > 0 && isset($premium_foreign_keys[$table])) {
            foreach ($premium_foreign_keys[$table] as $fk_name => $premium_info) {
                $origine_type = is_array($premium_info) ? ($premium_info['type'] ?? 'module') : 'module';
                $origine_name = is_array($premium_info) ? ($premium_info['name'] ?? '') : $premium_info;
                $origine_label = ($origine_type === 'plugin') ? 'Chiave esterna plugin ' : 'Chiave esterna modulo ';
                echo '
                    <tr>
                        <td class="column-name">'.$fk_name.'</td>
                        <td><span class="badge badge-primary">'.$origine_label.$origine_name.'</span></td>
                    </tr>';
            }
        }

        echo '
                </tbody>
            </table>
    </div>';
    }

    echo '
</div>
</div>';
}

// Log dell'esecuzione del controllo database
OperationLog::setInfo('id_module', $id_module);
OperationLog::setInfo('options', json_encode(['controllo_name' => 'Controllo database'], JSON_UNESCAPED_UNICODE));
OperationLog::build('effettua_controllo');
