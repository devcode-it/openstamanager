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
include_once __DIR__.'/modutil.php';

// Definizioni di fallback per le funzioni base
if (!function_exists('base_path_osm')) {
    function base_path_osm()
    {
        return ROOTDIR;
    }
}

if (!function_exists('base_dir')) {
    function base_dir()
    {
        return DOCROOT;
    }
}

use Models\Module;
use Modules\Aggiornamenti\IntegrityChecker;
use Modules\Aggiornamenti\Utils;
use Update;

// Funzioni per il controllo database (wrapper per compatibilità)
function integrity_diff($expected, $current)
{
    return IntegrityChecker::diff($expected, $current);
}

function settings_diff($expected, $current)
{
    return IntegrityChecker::settingsDiff($expected, $current);
}

function widgets_diff($expected, $current)
{
    return IntegrityChecker::widgetsDiff($expected, $current);
}

function widgets_added($current, $expected)
{
    return IntegrityChecker::widgetsAdded($current, $expected);
}

// Inizializzazione del modulo corrente
$module = Module::find($id_module);

// Aggiunta della classe per il modulo
echo '<div class="module-aggiornamenti">';

function createCollapsibleQuery($query_content, $row_id, $column_type)
{
    if (empty($query_content) || $query_content === '<span class="text-muted">-</span>') {
        return $query_content;
    }

    // Decodifica le entità HTML e rimuovi i tag HTML per calcolare la lunghezza del testo puro
    $text_content = html_entity_decode(strip_tags((string) $query_content), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Se il contenuto è breve (meno di 300 caratteri), mostra tutto
    if (strlen($text_content) <= 300) {
        // Decodifica anche il contenuto completo per la visualizzazione
        $decoded_content = html_entity_decode((string) $query_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return '<code class="text-break" style="white-space: pre-wrap;">'.$decoded_content.'</code>';
    }

    // Tronca il contenuto a 300 caratteri per l'anteprima
    $preview_content = substr($text_content, 0, 300);

    // Trova l'ultimo spazio per evitare di tagliare a metà parola
    $last_space = strrpos($preview_content, ' ');
    if ($last_space !== false && $last_space > 250) {
        $preview_content = substr($preview_content, 0, $last_space);
    }

    $preview_content = htmlspecialchars($preview_content).'...';

    // Decodifica il contenuto completo per la visualizzazione
    $decoded_full_content = html_entity_decode((string) $query_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return '
        <div class="query-container">
            <code class="query-preview text-break" style="white-space: pre-wrap;" id="preview_'.$row_id.'_'.$column_type.'">'.
                $preview_content.'
            </code>
            <code class="query-full text-break" style="white-space: pre-wrap; display: none;" id="full_'.$row_id.'_'.$column_type.'">'.
                $decoded_full_content.'
            </code>
        </div>';
}

function highlightDifferences($current, $expected)
{
    if (empty($expected)) {
        return [
            'current' => htmlspecialchars((string) $current),
            'expected' => '<span class="text-muted">-</span>',
        ];
    }

    $current_normalized = normalizeModuleOptions($current);
    $expected_normalized = normalizeModuleOptions($expected);

    $current_words = preg_split('/(\s+|[(),\'"`]|<[^>]*>)/', (string) $current_normalized, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $expected_words = preg_split('/(\s+|[(),\'"`]|<[^>]*>)/', (string) $expected_normalized, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    if ($current_normalized === $expected_normalized) {
        return [
            'current' => htmlspecialchars((string) $current),
            'expected' => htmlspecialchars((string) $expected),
        ];
    }

    $current_highlighted = '';
    $expected_highlighted = '';

    $current_count = count($current_words);
    $expected_count = count($expected_words);

    $lcs = array_fill(0, $current_count + 1, array_fill(0, $expected_count + 1, 0));

    for ($i = $current_count - 1; $i >= 0; --$i) {
        for ($j = $expected_count - 1; $j >= 0; --$j) {
            // Confronto case-insensitive per le parole (ignora spazi e punteggiatura)
            $current_word_lower = strtolower(trim($current_words[$i]));
            $expected_word_lower = strtolower(trim($expected_words[$j]));
            $is_match = ($current_word_lower === $expected_word_lower) && !empty($current_word_lower);

            if ($is_match) {
                $lcs[$i][$j] = $lcs[$i + 1][$j + 1] + 1;
            } else {
                $lcs[$i][$j] = max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }
    }

    $i = 0;
    $j = 0;
    while ($i < $current_count || $j < $expected_count) {
        // Confronto case-insensitive per le parole (ignora spazi e punteggiatura)
        $current_word_lower = strtolower(trim($current_words[$i] ?? ''));
        $expected_word_lower = strtolower(trim($expected_words[$j] ?? ''));
        $is_match = ($current_word_lower === $expected_word_lower) && !empty($current_word_lower);

        // Verifica se la parola è solo spazi/punteggiatura
        $current_is_whitespace = empty($current_word_lower);
        $expected_is_whitespace = empty($expected_word_lower);

        if ($i < $current_count && $j < $expected_count && $is_match) {
            // Parti uguali: mostra senza evidenziazione
            $word = htmlspecialchars($current_words[$i]);
            $current_highlighted .= $word;
            $expected_highlighted .= htmlspecialchars($expected_words[$j]);
            ++$i;
            ++$j;
        } elseif ($i < $current_count && ($j >= $expected_count || $lcs[$i + 1][$j] >= $lcs[$i][$j + 1])) {
            // Mostra la parola rimossa solo se non è spazio/punteggiatura
            if (!$current_is_whitespace) {
                $current_highlighted .= '<span class="diff-removed">'.htmlspecialchars($current_words[$i]).'</span>';
            } else {
                $current_highlighted .= htmlspecialchars($current_words[$i]);
            }
            ++$i;
        } elseif ($j < $expected_count) {
            // Mostra la parola aggiunta solo se non è spazio/punteggiatura
            if (!$expected_is_whitespace) {
                $expected_highlighted .= '<span class="diff-added">'.htmlspecialchars($expected_words[$j]).'</span>';
            } else {
                $expected_highlighted .= htmlspecialchars($expected_words[$j]);
            }
            ++$j;
        } else {
            break;
        }
    }

    return [
        'current' => $current_highlighted,
        'expected' => $expected_highlighted,
    ];
}

// ============================================================================
// FUNZIONI HELPER PER CONTROLLI PERSONALIZZAZIONI
// ============================================================================

/**
 * Carica i dati di personalizzazione dal sistema.
 */
function loadCustomizationData()
{
    return [
        'custom' => function_exists('customComponents') ? customComponents() : [],
        'custom_files' => function_exists('customStructureWithFiles') ? customStructureWithFiles() : [],
        'tables' => function_exists('customTables') ? customTables() : [],
        'custom_fields' => function_exists('customFields') ? customFields() : [],
        'custom_views_not_standard' => function_exists('customViewsNotStandard') ? customViewsNotStandard() : [],
        'custom_modules_not_standard' => function_exists('customModulesNotStandard') ? customModulesNotStandard() : [],
    ];
}

/**
 * Verifica i checksum dei file.
 */
function verifyFileChecksums()
{
    $checksum_errors = [];
    $checksum_errors_grouped = [];
    $checksum_file = base_dir().'/checksum.json';

    if (!file_exists($checksum_file)) {
        return compact('checksum_errors', 'checksum_errors_grouped');
    }

    $contents = file_get_contents($checksum_file);
    $checksum = json_decode($contents, true);

    if (empty($checksum)) {
        return compact('checksum_errors', 'checksum_errors_grouped');
    }

    foreach ($checksum as $file => $md5) {
        $verifica = md5_file(base_dir().'/'.$file);
        if ($verifica != $md5) {
            $checksum_errors[] = $file;

            // Raggruppa per cartella
            $path_parts = explode('/', (string) $file);
            $file_name = array_pop($path_parts);
            $folder_path = implode('/', $path_parts);

            if (!isset($checksum_errors_grouped[$folder_path])) {
                $checksum_errors_grouped[$folder_path] = [];
            }
            $checksum_errors_grouped[$folder_path][] = $file_name;
        }
    }

    return compact('checksum_errors', 'checksum_errors_grouped');
}

/**
 * Determina il file di riferimento del database in base al tipo e versione.
 */
function getDatabaseReferenceFile($database)
{
    if ($database->getType() === 'MariaDB') {
        return 'mariadb_10_x.json';
    }

    if ($database->getType() === 'MySQL') {
        $mysql_min_version = '8.0.0';
        $mysql_max_version = '8.3.99';
        $version = $database->getMySQLVersion();

        if (version_compare($version, $mysql_min_version, '>=') && version_compare($version, $mysql_max_version, '<=')) {
            return 'mysql.json';
        }

        return 'mysql_8_3.json';
    }

    return 'mysql.json';
}

/**
 * Verifica i file di riferimento mancanti.
 */
function checkMissingReferenceFiles($database)
{
    $file_to_check_database = getDatabaseReferenceFile($database);

    return [
        'views_file_missing' => !file_exists(base_dir().'/views.json'),
        'modules_file_missing' => !file_exists(base_dir().'/modules.json'),
        'settings_file_missing' => !file_exists(base_dir().'/settings.json'),
        'widgets_file_missing' => !file_exists(base_dir().'/widgets.json'),
        'database_file_missing' => !file_exists(base_dir().'/'.$file_to_check_database),
        'file_to_check_database' => $file_to_check_database,
    ];
}

/**
 * Determina gli errori per ogni sezione.
 */
function determineErrorStatus($customization_data, $checksum_data, $reference_files)
{
    $has_file_errors = !empty($customization_data['custom_files']) || !empty($checksum_data['checksum_errors']);
    $has_table_errors = !empty($customization_data['tables']);
    $has_view_errors = !empty($customization_data['custom_views_not_standard']) || $reference_files['views_file_missing'];
    $has_module_errors = !empty($customization_data['custom_modules_not_standard']) || $reference_files['modules_file_missing'];
    $has_field_errors = !empty($customization_data['custom_fields']) || $reference_files['database_file_missing'];
    $has_any_errors = !empty($customization_data['custom']) || $has_file_errors || $has_table_errors || $has_view_errors || $has_module_errors || $has_field_errors;

    return compact(
        'has_file_errors',
        'has_table_errors',
        'has_view_errors',
        'has_module_errors',
        'has_field_errors',
        'has_any_errors'
    );
}

// ============================================================================
// CARICAMENTO E ELABORAZIONE DATI
// ============================================================================

if (function_exists('customComponents')) {
    // Carica dati di personalizzazione
    $customization_data = loadCustomizationData();
    extract($customization_data);

    // Verifica checksum file
    $checksum_data = verifyFileChecksums();
    extract($checksum_data);

    // Verifica file di riferimento mancanti
    $reference_files = checkMissingReferenceFiles($database);
    extract($reference_files);

    // Determina lo stato degli errori
    $error_status = determineErrorStatus($customization_data, $checksum_data, $reference_files);
    extract($error_status);

    // Determina il colore in base all'avviso piu grave
    $customizations_colors = Utils::determineCardColor(0, $has_any_errors ? 1 : 0, 0);
    $customizations_card_class = 'card-'.$customizations_colors['color'];
    $customizations_icon = $customizations_colors['icon'];
    $customizations_title = $has_any_errors ? tr('Personalizzazioni Rilevate') : tr('Personalizzazioni');

    echo '
        <div class="row mb-4">
            <div class="col-12">
                <div class="card '.$customizations_card_class.' card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa '.$customizations_icon.'"></i> '.$customizations_title.'
                            <span class="tip" title="'.tr('Elenco delle personalizzazioni rilevabili dal gestionale').'">
                                <i class="fa fa-question-circle-o"></i>
                            </span>
                        </h3>
                    </div>
                    <div class="card-body">';

    // ========================================================================
    // CARD FILE PERSONALIZZATI
    // ========================================================================

    /**
     * Renderizza la card per i file personalizzati.
     */
    function renderCustomFilesCard($checksum_errors_grouped, $custom_files, $has_file_errors)
    {
        $modified_files_count = count($checksum_errors_grouped);
        $custom_files_count = count($custom_files);

        $file_warning = ($modified_files_count > 0 || $custom_files_count > 0) ? 1 : 0;
        $file_colors = Utils::determineCardColor(0, $file_warning, 0);
        $file_card_color = $file_colors['color'];
        $file_icon = $file_colors['icon'];

        $badges = '';
        if ($modified_files_count > 0) {
            $badges .= '<span class="badge badge-warning ml-2">'.$modified_files_count.'</span>';
        }
        if ($custom_files_count > 0) {
            $badges .= '<span class="badge badge-warning ml-2">'.$custom_files_count.'</span>';
        }

        $body_content = '';
        if ($has_file_errors) {
            $body_content .= '<div class="table-responsive"><table class="table table-hover table-striped table-sm"><thead class="thead-light"><tr><th width="30%">'.tr('Percorso').'</th><th width="15%">'.tr('Tipo').'</th><th width="55%">'.tr('File').'</th></tr></thead><tbody>';

            // File con checksum diverso
            foreach ($checksum_errors_grouped as $folder => $files) {
                $files_list = implode(', ', array_map(fn ($file) => '<code>'.$file.'</code>', $files));
                $body_content .= '<tr><td><strong>'.$folder.'</strong></td><td><span class="badge badge-warning badge-lg">'.tr('File modificato').'</span></td><td>'.$files_list.'</td></tr>';
            }

            // File personalizzati
            foreach ($custom_files as $element) {
                $files_list = implode(', ', array_map(fn ($file) => '<code>'.$file.'</code>', $element['files']));
                $body_content .= '<tr><td><strong>'.$element['path'].'/custom</strong></td><td><span class="badge badge-warning badge-lg">'.tr('Cartella custom').'</span></td><td>'.$files_list.'</td></tr>';
            }

            $body_content .= '</tbody></table></div>';
        } else {
            $body_content = '<p class="text-success mb-0"><i class="fa fa-check-circle"></i> '.tr('Nessun file personalizzato rilevato').'</p>';
        }

        return '
        <div class="card card-outline card-'.$file_card_color.' requirements-card mb-2 collapsable collapsed-card">
            <div class="card-header with-border requirements-card-header requirements-card-header-'.$file_card_color.'">
                <h3 class="card-title requirements-card-title requirements-card-title-'.$file_card_color.'">
                    <i class="fa '.$file_icon.' mr-2 requirements-icon"></i>
                    '.tr('File personalizzati').'
                    '.$badges.'
                </h3>
                <div class="card-tools pull-right">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                '.$body_content.'
            </div>
        </div>';
    }

    echo renderCustomFilesCard($checksum_errors_grouped, $custom_files, $has_file_errors);

    // ========================================================================
    // CARD TABELLE NON PREVISTE
    // ========================================================================

    /**
     * Renderizza la card per le tabelle non previste.
     */
    function renderUnexpectedTablesCard($tables, $has_table_errors)
    {
        $table_count = count($tables);
        $table_colors = Utils::determineCardColor(0, 0, $table_count > 0 ? 1 : 0);
        $table_card_color = $table_colors['color'];
        $table_icon = $table_colors['icon'];

        $badge_html = $table_count > 0 ? '<span class="badge badge-info ml-2">'.$table_count.'</span>' : '';

        $body_content = $has_table_errors
            ? implode(', ', array_map(fn ($table) => '<code class="module-aggiornamenti table-code">'.$table.'</code>', $tables))
            : '<p class="text-success mb-0"><i class="fa fa-check-circle"></i> '.tr('Nessuna tabella non prevista rilevata').'</p>';

        return '
        <div class="card card-outline card-'.$table_card_color.' requirements-card mb-2 collapsable collapsed-card">
            <div class="card-header with-border requirements-card-header requirements-card-header-'.$table_card_color.'">
                <h3 class="card-title requirements-card-title requirements-card-title-'.$table_card_color.'">
                    <i class="fa '.$table_icon.' mr-2 requirements-icon"></i>
                    '.tr('Tabelle non previste').'
                    '.$badge_html.'
                </h3>
                <div class="card-tools pull-right">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p class="mb-0">
                    '.$body_content.'
                </p>
            </div>
        </div>';
    }

    echo renderUnexpectedTablesCard($tables, $has_table_errors);

    // Card Viste
    $has_view_data_issues = !empty($custom_views_not_standard) && !$views_file_missing;

    // Conta gli avvisi per tipo
    $view_warning_count = 0;
    $view_info_count = 0;
    $view_premium_count = 0;

    if ($has_view_data_issues) {
        foreach ($custom_views_not_standard as $view) {
            // Verifica se la reason identifica una vista proveniente da modulo/plugin premium
            if (str_starts_with((string) $view['reason'], 'Vista modulo ') || str_starts_with((string) $view['reason'], 'Vista plugin ')) {
                ++$view_premium_count;
            } else {
                match ($view['reason']) {
                    'Vista aggiuntiva' => $view_info_count++,
                    'Vista mancante' => $view_info_count++,
                    'Query modificata' => $view_warning_count++,
                    'Modulo non previsto' => $view_info_count++,
                    default => null,
                };
            }
        }
    }

    // Determina il colore della card in base all'avviso piu grave
    $view_danger = ($views_file_missing && $view_warning_count > 0) ? 1 : 0;
    $view_warning = ($view_warning_count > 0 || $views_file_missing) ? 1 : 0;
    $view_colors = Utils::determineCardColor($view_danger, $view_warning, $view_info_count > 0 ? 1 : 0);
    $view_card_color = $view_colors['color'];
    $view_icon = $view_colors['icon'];

    echo '
        <div class="card card-outline card-'.$view_card_color.' requirements-card mb-2 collapsable collapsed-card">
            <div class="card-header with-border requirements-card-header requirements-card-header-'.$view_card_color.'">
                <h3 class="card-title requirements-card-title requirements-card-title-'.$view_card_color.'">
                    <i class="fa '.$view_icon.' mr-2 requirements-icon"></i>
                    '.tr('Viste personalizzate').'
                    '.($view_warning_count > 0 ? '<span class="badge badge-warning ml-2">'.$view_warning_count.'</span>' : '').'
                    '.($view_info_count > 0 ? '<span class="badge badge-info ml-2">'.$view_info_count.'</span>' : '').'
                    '.($view_premium_count > 0 ? '<span class="badge badge-primary ml-2">'.$view_premium_count.'</span>' : '').'
                </h3>
                <div class="card-tools pull-right">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">';

    if ($has_view_data_issues) {
        echo '
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th width="12%">'.tr('Nome colonna').'</th>
                                    <th width="15%">'.tr('Modulo').'</th>
                                    <th width="10%">'.tr('Tipo modifica').'</th>
                                    <th width="31.5%">'.tr('Query attuale').'</th>
                                    <th width="31.5%">'.tr('Query prevista').'</th>
                                </tr>
                            </thead>
                            <tbody>';

        foreach ($custom_views_not_standard as $index => $view) {
            // Verifica se la reason identifica una vista proveniente da modulo/plugin premium per assegnare il badge blu
            if (str_starts_with((string) $view['reason'], 'Vista modulo ') || str_starts_with((string) $view['reason'], 'Vista plugin ')) {
                $badge_class = 'badge-primary';
            } else {
                $badge_class = match ($view['reason']) {
                    'Vista aggiuntiva' => 'badge-info',
                    'Vista mancante' => 'badge-info',
                    'Query modificata' => 'badge-warning',
                    'Modulo non previsto' => 'badge-info',
                    default => 'badge-secondary',
                };
            }

            $row_id = 'view_'.$index;
            $has_long_content = false;

            if (empty($view['current_query'])) {
                $current_query_display = '<span class="text-muted">-</span>';
                if (!empty($view['expected_query'])) {
                    $expected_query_display = createCollapsibleQuery(htmlspecialchars((string) $view['expected_query']), $row_id, 'expected');
                    $has_long_content = strlen(strip_tags(htmlspecialchars((string) $view['expected_query']))) > 300;
                } else {
                    $expected_query_display = '<span class="text-muted">-</span>';
                }
            } else {
                $diff_result = highlightDifferences($view['current_query'], $view['expected_query']);
                $current_query_display = createCollapsibleQuery($diff_result['current'], $row_id, 'current');
                $expected_query_display = createCollapsibleQuery($diff_result['expected'], $row_id, 'expected');
                $has_long_content = strlen(strip_tags((string) $view['current_query'])) > 300 || strlen(strip_tags((string) $view['expected_query'])) > 300;
            }

            $module_id_display = $view['module_id'] ? 'ID: '.$view['module_id'] : 'Mancante';
            $module_display = $view['module_name'].' <small class="text-muted">('.$module_id_display.')</small>';

            $view_name_display = !empty($view['name']) ?
                $view['name'] :
                '(Assente)';

            // Crea il pulsante espandi solo se c'è contenuto lungo
            $expand_button = '';
            if ($has_long_content) {
                $expand_button = '<br><button type="button" class="btn btn-xs btn-outline-secondary mt-1" onclick="toggleModuleRow(\''.$row_id.'\')">
                        <i class="fa fa-expand" id="icon_'.$row_id.'"></i> <span id="text_'.$row_id.'">Espandi</span>
                    </button>';
            }

            echo '
                                <tr id="row_'.$row_id.'">
                                    <td><code>'.$view_name_display.'</code>'.$expand_button.'</td>
                                    <td>'.$module_display.'</td>
                                    <td><span class="badge '.$badge_class.'">'.$view['reason'].'</span></td>
                                    <td class="query-cell">'.$current_query_display.'</td>
                                    <td class="query-cell">'.$expected_query_display.'</td>
                                </tr>';
        }

        echo '
                            </tbody>
                        </table>
                    </div>';
    } elseif ($views_file_missing) {
        echo '
                    <div class="alert alert-warning alert-database">
                        <i class="fa fa-exclamation-triangle"></i> '.tr('Impossibile effettuare il controllo delle viste in assenza del file _FILE_', [
            '_FILE_' => '<b>views.json</b>',
        ]).'.
                    </div>';
    } else {
        echo '
                    <p class="text-success mb-0">
                        <i class="fa fa-check-circle"></i> '.tr('Nessuna vista personalizzata rilevata').'
                    </p>';
    }

    echo '
                </div>
        </div>';

    // Card Moduli
    $has_module_data_issues = !empty($custom_modules_not_standard) && !$modules_file_missing;

    // Conta gli avvisi per tipo
    $module_warning_count = 0;
    $module_info_count = 0;
    $module_premium_count = 0;

    if ($has_module_data_issues) {
        foreach ($custom_modules_not_standard as $modulo) {
            // Verifica se la reason è "Modulo Premium"
            if ($modulo['reason'] === 'Modulo Premium') {
                ++$module_premium_count;
            } else {
                match ($modulo['reason']) {
                    'Options modificato' => $module_warning_count++,
                    'Modulo non previsto' => $module_warning_count++,
                    'Options2 valorizzato' => $module_info_count++,
                    default => null,
                };
            }
        }
    }

    // Determina il colore della card in base all'avviso piu grave
    $module_danger = ($modules_file_missing && $module_warning_count > 0) ? 1 : 0;
    $module_warning = ($module_warning_count > 0 || $modules_file_missing) ? 1 : 0;
    $module_colors = Utils::determineCardColor($module_danger, $module_warning, $module_info_count > 0 ? 1 : 0);
    $module_card_color = $module_colors['color'];
    $module_icon = $module_colors['icon'];

    echo '
        <div class="card card-outline card-'.$module_card_color.' requirements-card mb-2 collapsable collapsed-card">
            <div class="card-header with-border requirements-card-header requirements-card-header-'.$module_card_color.'">
                <h3 class="card-title requirements-card-title requirements-card-title-'.$module_card_color.'">
                    <i class="fa '.$module_icon.' mr-2 requirements-icon"></i>
                    '.tr('Moduli personalizzati').'
                    '.($module_warning_count > 0 ? '<span class="badge badge-warning ml-2">'.$module_warning_count.'</span>' : '').'
                    '.($module_info_count > 0 ? '<span class="badge badge-info ml-2">'.$module_info_count.'</span>' : '').'
                    '.($module_premium_count > 0 ? '<span class="badge badge-primary ml-2">'.$module_premium_count.'</span>' : '').'
                </h3>
                <div class="card-tools pull-right">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">';

    if ($has_module_data_issues) {
        echo '
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th width="15%">'.tr('Nome modulo').'</th>
                                    <th width="10%">'.tr('Tipo modifica').'</th>
                                    <th width="37.5%">'.tr('Options attuale').'</th>
                                    <th width="37.5%">'.tr('Options previsto').'</th>
                                </tr>
                            </thead>
                            <tbody>';

        foreach ($custom_modules_not_standard as $index => $modulo) {
            // Verifica se la reason è "Modulo Premium" per assegnare il badge blu
            if ($modulo['reason'] === 'Modulo Premium') {
                $badge_class = 'badge-primary';
            } else {
                $badge_class = match ($modulo['reason']) {
                    'Options2 valorizzato' => 'badge-info',
                    'Options modificato' => 'badge-warning',
                    'Modulo non previsto' => 'badge-warning',
                    default => 'badge-secondary',
                };
            }

            // Determina quale options mostrare: se options2 è valorizzato, mostra quello, altrimenti options
            $current_to_show = !empty($modulo['current_options2']) ? $modulo['current_options2'] : $modulo['current_options'];
            $expected_to_show = $modulo['expected_options'];

            $row_id = 'module_'.$index;
            $has_long_content = false;

            // Applica l'evidenziazione delle differenze come per le viste
            if (empty($current_to_show)) {
                $current_options_display = '<span class="text-muted">-</span>';
                if (!empty($expected_to_show)) {
                    $expected_options_display = createCollapsibleQuery(htmlspecialchars((string) $expected_to_show), $row_id, 'expected');
                    $has_long_content = strlen(strip_tags(htmlspecialchars((string) $expected_to_show))) > 300;
                } else {
                    $expected_options_display = '<span class="text-muted">-</span>';
                }
            } else {
                $diff_result = highlightDifferences($current_to_show, $expected_to_show);
                $current_options_display = createCollapsibleQuery($diff_result['current'], $row_id, 'current');
                $expected_options_display = createCollapsibleQuery($diff_result['expected'], $row_id, 'expected');
                $has_long_content = strlen(strip_tags((string) $current_to_show)) > 300 || strlen(strip_tags((string) $expected_to_show)) > 300;
            }

            // Crea il pulsante espandi solo se c'è contenuto lungo
            $expand_button = '';
            if ($has_long_content) {
                $expand_button = '<br><button type="button" class="btn btn-xs btn-outline-secondary mt-1" onclick="toggleModuleRow(\''.$row_id.'\')">
                        <i class="fa fa-expand" id="icon_'.$row_id.'"></i> <span id="text_'.$row_id.'">Espandi</span>
                    </button>';
            }

            echo '
                                <tr id="row_'.$row_id.'">
                                    <td><strong>'.$modulo['module_display_name'].'</strong><br><small class="text-muted">ID: '.$modulo['id'].'</small>'.$expand_button.'</td>
                                    <td><span class="badge '.$badge_class.'">'.$modulo['reason'].'</span></td>
                                    <td class="query-cell">'.$current_options_display.'</td>
                                    <td class="query-cell">'.$expected_options_display.'</td>
                                </tr>';
        }

        echo '
                            </tbody>
                        </table>
                    </div>';
    } elseif ($modules_file_missing) {
        echo '
                    <div class="alert alert-warning alert-database">
                        <i class="fa fa-exclamation-triangle"></i> '.tr('Impossibile effettuare il controllo dei moduli in assenza del file _FILE_', [
            '_FILE_' => '<b>modules.json</b>',
        ]).'.
                    </div>';
    } else {
        echo '
                    <p class="text-success mb-0">
                        <i class="fa fa-check-circle"></i> '.tr('Nessun modulo personalizzato rilevato').'
                    </p>';
    }

    echo '
                </div>
            </div>';

    // Card Campi personalizzati (Controllo Database)
    // Conta gli errori del database sommando i conteggi delle badge mostrate in database.php
    $database_danger_count = 0;
    $database_warning_count = 0;
    $database_info_count = 0;
    $database_premium_count = 0;

    // Includi i campi personalizzati nel conteggio info
    $database_info_count += count($custom_fields);

    // Traccia da quale modulo proviene ogni campo (per identificare i campi premium)
    $premium_fields = [];
    $premium_foreign_keys = [];

    // Funzione helper per raggruppare gli errori per tabella (stessa logica di database.php)
    function editGroupErrorsByTable($results, $results_added, $premium_fields, $premium_foreign_keys, $data)
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

    try {
        if (!$database_file_missing) {
            // Carica il file di riferimento principale per il database
            $data = [];
            if (file_exists(base_dir().'/'.$file_to_check_database)) {
                $contents = file_get_contents(base_dir().'/'.$file_to_check_database);
                $data = json_decode($contents, true);
            }

            $database_reference_data = aggiornamentiMergeDatabaseReferenceData($data, $file_to_check_database);
            $data = $database_reference_data['data'];
            $premium_fields = $database_reference_data['premium_fields'];
            $premium_foreign_keys = $database_reference_data['premium_foreign_keys'];

            if (!empty($data)) {
                $info = Update::getDatabaseStructure();
                $results = integrity_diff($data, $info);
                $results_added = integrity_diff($info, $data);

                $contents_settings = file_get_contents(base_dir().'/settings.json');
                $data_settings = json_decode($contents_settings, true);
                $settings_reference_data = aggiornamentiMergeSettingsReferenceData($data_settings);
                $data_settings = $settings_reference_data['data'];
                $premium_settings = $settings_reference_data['premium_settings'];

                $settings = Update::getSettings();
                $current_premium_settings = aggiornamentiGetCurrentPremiumSettings($settings, $premium_settings, $data_settings);
                $results_settings = settings_diff($data_settings, $settings);
                $results_settings_added = settings_diff($settings, $data_settings);

                $contents_widgets = file_get_contents(base_dir().'/widgets.json');
                $data_widgets = json_decode($contents_widgets, true);
                $widgets_reference_data = aggiornamentiMergeWidgetsReferenceData($data_widgets);
                $data_widgets = $widgets_reference_data['data'];
                $premium_widgets = $widgets_reference_data['premium_widgets'];

                $widgets = Update::getWidgets();
                $current_premium_widgets = aggiornamentiGetCurrentPremiumWidgets($widgets, $premium_widgets, $data_widgets);
                $results_widgets = widgets_diff($data_widgets, $widgets);
                $results_widgets_added = widgets_added($widgets, $data_widgets);

                // Raggruppa gli errori per tabella (stessa logica di database.php)
                $grouped_errors = editGroupErrorsByTable($results, $results_added, $premium_fields, $premium_foreign_keys, $data);

                // Somma i conteggi per tutte le tabelle (stesso calcolo delle badge in database.php)
                foreach ($grouped_errors as $table => $errors) {
                    // Calcola i conteggi per questa tabella
                    $danger_count = count($errors['campi_mancanti'] ?? []) + count($errors['chiavi_mancanti'] ?? []) + count($errors['chiavi_esterne_mancanti'] ?? []);
                    $warning_count = count($errors['campi_modificati'] ?? []) + count($errors['chiavi_esterne_modificate'] ?? []);
                    $info_count = count($errors['campi_non_previsti'] ?? []) + count($errors['chiavi_non_previste'] ?? []) + count($errors['chiavi_esterne_non_previste'] ?? []);

                    // Somma ai conteggi globali
                    $database_danger_count += $danger_count;
                    $database_warning_count += $warning_count;
                    $database_info_count += $info_count;

                    // Aggiungi i campi premium e le chiavi esterne premium ai conteggi
                    $premium_fields_count = isset($premium_fields[$table]) ? count(array_filter(array_keys($premium_fields[$table]), fn ($k) => $k !== 'foreign_keys')) : 0;
                    $premium_fks_count = isset($premium_foreign_keys[$table]) ? count($premium_foreign_keys[$table]) : 0;

                    // Aggiorna i conteggi se ci sono elementi premium (solo in primary, non in info)
                    if ($premium_fields_count > 0 || $premium_fks_count > 0) {
                        $database_premium_count += $premium_fields_count + $premium_fks_count;
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Silenzio gli errori
    }

    // Determina il colore in base all'avviso più grave
    $database_colors = Utils::determineCardColor(
        $database_danger_count,
        $database_warning_count || $database_file_missing ? 1 : 0,
        ($database_info_count > 0) ? 1 : 0
    );
    $database_card_color = $database_colors['color'];
    $database_icon = $database_colors['icon'];

    $database_badge_html = Utils::generateBadgeHtml($database_danger_count, $database_warning_count, $database_info_count);

    // Aggiungi badge per i campi premium
    if ($database_premium_count > 0) {
        $database_badge_html .= '<span class="badge badge-primary ml-2">'.$database_premium_count.'</span>';
    }

    echo '
            <div class="card card-outline card-'.$database_card_color.' requirements-card mb-2 collapsable collapsed-card">
                <div class="card-header with-border requirements-card-header requirements-card-header-'.$database_card_color.'">
                    <h3 class="card-title requirements-card-title requirements-card-title-'.$database_card_color.'">
                        <i class="fa '.$database_icon.' mr-2 requirements-icon"></i>
                        '.tr('Campi personalizzati').'
                        '.$database_badge_html.'
                    </h3>
                    <div class="card-tools pull-right">
                        '.($database_danger_count > 0 || $database_warning_count > 0 ? '<button type="button" class="btn btn-primary btn-sm mr-2" id="risolvi_conflitti">
                            <i class="fa fa-database"></i> '.tr('Risolvi tutti i conflitti').'
                        </button>' : '').'
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">';

    include __DIR__.'/database.php';

    echo '
                </div>
            </div>';

    // Card Impostazioni personalizzate
    $has_settings_data_issues = !empty($results_settings) || !empty($results_settings_added) || !empty($current_premium_settings);

    // Conta gli avvisi per tipo
    $settings_danger_count = 0;
    $settings_warning_count = 0;
    $settings_info_count = 0;
    $settings_premium_count = 0;

    if ($has_settings_data_issues) {
        foreach ($results_settings as $key => $setting) {
            if (isset($premium_settings[$key])) {
                continue;
            }

            if (!$setting['current']) {
                ++$settings_danger_count;
            } else {
                ++$settings_warning_count;
            }
        }

        foreach ($results_settings_added as $key => $setting) {
            if (isset($premium_settings[$key])) {
                continue;
            }

            if ($setting['current'] == null) {
                ++$settings_info_count;
            }
        }

        $settings_premium_count = count($current_premium_settings);
    }

    // Determina il colore della card in base all'avviso più grave
    $settings_danger = ($settings_file_missing || $settings_danger_count > 0) ? 1 : 0;
    $settings_warning = ($settings_warning_count > 0) ? 1 : 0;
    $settings_colors = Utils::determineCardColor($settings_danger, $settings_warning, ($settings_info_count > 0) ? 1 : 0);
    $settings_card_color = $settings_colors['color'];
    $settings_icon = $settings_colors['icon'];

    $settings_badge_html = Utils::generateBadgeHtml($settings_danger_count, $settings_warning_count, $settings_info_count);

    // Aggiungi badge per le impostazioni premium
    if ($settings_premium_count > 0) {
        $settings_badge_html .= '<span class="badge badge-primary ml-2">'.$settings_premium_count.'</span>';
    }

    echo '
            <div class="card card-outline card-'.$settings_card_color.' requirements-card mb-2 collapsable collapsed-card">
                <div class="card-header with-border requirements-card-header requirements-card-header-'.$settings_card_color.'">
                    <h3 class="card-title requirements-card-title requirements-card-title-'.$settings_card_color.'">
                        <i class="fa '.$settings_icon.' mr-2 requirements-icon"></i>
                        '.tr('Impostazioni personalizzate').'
                        '.$settings_badge_html.'
                    </h3>
                    <div class="card-tools pull-right">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">';

    if ($has_settings_data_issues) {
        include __DIR__.'/settings.php';
    } elseif ($settings_file_missing) {
        echo '
                    <div class="alert alert-warning alert-database">
                        <i class="fa fa-exclamation-triangle"></i> '.tr('Impossibile effettuare il controllo delle impostazioni in assenza del file _FILE_', [
            '_FILE_' => '<b>settings.json</b>',
        ]).'.
                    </div>';
    } else {
        echo '
                    <p class="text-success mb-0">
                        <i class="fa fa-check-circle"></i> '.tr('Nessuna impostazione personalizzata rilevata').'
                    </p>';
    }

    echo '
                </div>
            </div>';

    // Card Widgets personalizzati
    $has_widgets_data_issues = !empty($results_widgets) || !empty($results_widgets_added) || !empty($current_premium_widgets);

    // Conta gli avvisi per tipo
    $widgets_danger_count = 0;
    $widgets_warning_count = 0;
    $widgets_info_count = 0;
    $widgets_premium_count = 0;

    if ($has_widgets_data_issues) {
        foreach ($results_widgets as $module_key => $module_widgets) {
            if (is_array($module_widgets)) {
                foreach ($module_widgets as $widget_name => $widget) {
                    if (aggiornamentiFindPremiumWidgetReference($module_key, $widget_name, $premium_widgets, $data_widgets) !== null) {
                        continue;
                    }

                    if (!$widget['current']) {
                        ++$widgets_danger_count;
                    } else {
                        ++$widgets_warning_count;
                    }
                }
            }
        }

        foreach ($results_widgets_added as $module_key => $module_widgets) {
            if (is_array($module_widgets)) {
                foreach ($module_widgets as $widget_name => $widget) {
                    if (aggiornamentiFindPremiumWidgetReference($module_key, $widget_name, $premium_widgets, $data_widgets) !== null) {
                        continue;
                    }

                    if ($widget['expected'] == null) {
                        ++$widgets_info_count;
                    }
                }
            }
        }

        foreach ($current_premium_widgets as $module_widgets) {
            $widgets_premium_count += count((array) $module_widgets);
        }
    }

    // Determina il colore della card in base all'avviso più grave
    $widgets_danger = ($widgets_file_missing || $widgets_danger_count > 0) ? 1 : 0;
    $widgets_warning = ($widgets_warning_count > 0) ? 1 : 0;
    $widgets_colors = Utils::determineCardColor($widgets_danger, $widgets_warning, $widgets_info_count > 0 ? 1 : 0);
    $widgets_card_color = $widgets_colors['color'];
    $widgets_icon = $widgets_colors['icon'];

    $widgets_badge_html = Utils::generateBadgeHtml($widgets_danger_count, $widgets_warning_count, $widgets_info_count);

    // Aggiungi badge per i widgets premium
    if ($widgets_premium_count > 0) {
        $widgets_badge_html .= '<span class="badge badge-primary ml-2">'.$widgets_premium_count.'</span>';
    }

    echo '
            <div class="card card-outline card-'.$widgets_card_color.' requirements-card mb-2 collapsable collapsed-card">
                <div class="card-header with-border requirements-card-header requirements-card-header-'.$widgets_card_color.'">
                    <h3 class="card-title requirements-card-title requirements-card-title-'.$widgets_card_color.'">
                        <i class="fa '.$widgets_icon.' mr-2 requirements-icon"></i>
                        '.tr('Widgets personalizzati').'
                        '.$widgets_badge_html.'
                    </h3>
                    <div class="card-tools pull-right">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">';

    if ($has_widgets_data_issues) {
        include __DIR__.'/widgets.php';
    } elseif ($widgets_file_missing) {
        echo '
                    <div class="alert alert-warning alert-database">
                        <i class="fa fa-exclamation-triangle"></i> '.tr('Impossibile effettuare il controllo dei widgets in assenza del file _FILE_', [
            '_FILE_' => '<b>widgets.json</b>',
        ]).'.
                    </div>';
    } else {
        echo '
                    <p class="text-success mb-0">
                        <i class="fa fa-check-circle"></i> '.tr('Nessun widget personalizzato rilevato').'
                    </p>';
    }

    echo '
                </div>
            </div>
        </div>
    </div>

<script>
$(document).ready(function() {
    $("#risolvi_conflitti").on("click", function() {
        var button = $(this);

        var operazioniHtml = `
            <div class="d-flex align-items-flex-start mb-2">
                <i class="fa fa-check text-success mr-2"></i>
                <span>'.tr('Aggiungerà i campi mancanti nel database').'</span>
            </div>
            <div class="d-flex align-items-flex-start mb-2">
                <i class="fa fa-check text-success mr-2"></i>
                <span>'.tr('Correggerà i campi con struttura diversa').'</span>
            </div>
            <div class="d-flex align-items-flex-start mb-2">
                <i class="fa fa-check text-success mr-2"></i>
                <span>'.tr('Aggiungerà le chiavi mancanti').'</span>
            </div>
            <div class="d-flex align-items-flex-start mb-2">
                <i class="fa fa-check text-success mr-2"></i>
                <span>'.tr('Correggerà le chiavi esterne modificate').'</span>
            </div>
            <div class="d-flex align-items-flex-start mb-2">
                <i class="fa fa-check text-success mr-2"></i>
                <span>'.tr('Aggiungerà le impostazioni mancanti').'</span>
            </div>
            <div class="d-flex align-items-flex-start mb-2">
                <i class="fa fa-check text-success mr-2"></i>
                <span>'.tr('Correggerà le impostazioni modificate').'</span>
            </div>
            <div class="d-flex align-items-flex-start mb-2">
                <i class="fa fa-times text-danger mr-2"></i>
                <span><strong>'.tr('NON rimuoverà i campi, le chiavi o le impostazioni in più').'</strong></span>
            </div>
            <div class="d-flex align-items-flex-start mb-2">
                <i class="fa fa-exclamation-triangle text-warning mr-2"></i>
                <span>'.tr('Si consiglia di effettuare un backup prima di procedere').'</span>
            </div>
            <div class="d-flex align-items-flex-start mb-2">
                <i class="fa fa-ban text-danger mr-2"></i>
                <span><strong>'.tr('Non può essere annullata').'</strong></span>
            </div>
        `;

        var htmlContent = `
            <p class="text-start mb-3">'.tr('Sei sicuro di voler risolvere tutti i conflitti del database?').'</p>
            <div class="alert alert-warning text-start mb-0">
                <div class="mb-2">
                    <i class="fa fa-info-circle"></i>
                    <strong>'.tr('Questa operazione:').'</strong>
                </div>
                <div class="ms-0">
                    ${operazioniHtml}
                </div>
            </div>
        `;

        swal({
            title: "'.tr('Conferma risoluzione conflitti database').'",
            html: htmlContent,
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

                    // Raccoglie le query dalle righe con badge danger e warning
                    $("tr").each(function() {
                        var badge = $(this).find("td:nth-child(2) .badge");
                        var badgeClass = badge.attr("class");

                        // Se la riga ha una badge danger o warning
                        if (badgeClass && (badgeClass.includes("badge-danger") || badgeClass.includes("badge-warning"))) {
                            var query = $(this).find(".column-conflict").text().trim();

                            // Esclude i testi che non sono query SQL
                            if (query &&
                                query !== "Chiave non prevista" &&
                                query !== "Chiave esterna non prevista" &&
                                query !== "Chiave mancante" &&
                                query !== "Impostazione non prevista" &&
                                !query.startsWith("query=")) {

                                if (!query.endsWith(";")) {
                                    query += ";";
                                }
                                queries.push(query);
                            }
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

$alerts = [];

if (!extension_loaded('zip')) {
    $alerts[tr('Estensione ZIP')] = tr('da abilitare');
}

$upload_max_filesize = ini_get('upload_max_filesize');
$upload_max_filesize = str_replace(['k', 'M'], ['000', '000000'], $upload_max_filesize);
if ($upload_max_filesize < 64000000) {
    $alerts['upload_max_filesize'] = '64MB';
}

$post_max_size = ini_get('post_max_size');
$post_max_size = str_replace(['k', 'M'], ['000', '000000'], $post_max_size);
if ($post_max_size < 64000000) {
    $alerts['post_max_size'] = '64MB';
}

if (!empty($alerts)) {
    echo '
<div class="alert alert-info">
    <p>'.tr('Devi modificare il seguenti parametri del file di configurazione PHP (_FILE_) per poter caricare gli aggiornamenti', [
        '_FILE_' => '<b>php.ini</b>',
    ]).':<ul>';
    foreach ($alerts as $key => $value) {
        echo '
        <li><b>'.$key.'</b> = '.$value.'</li>';
    }
    echo '
    </ul></p>
</div>';
}

echo '


<script>
function update() {
    if ($("#blob").val()) {
        swal({
            title: "'.tr('Avviare la procedura?').'",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "'.tr('Sì').'"
        }).then(function (result) {
            $("#update").submit();
        })
    } else {
        swal({
            title: "'.tr('Selezionare un file!').'",
            type: "error",
        })
    }
}

function database(button) {
    openModal("'.tr('Controllo del database').'", "'.$module->fileurl('database.php').'?id_module='.$id_module.'");
}

function controlli(button) {
    openModal("'.tr('Controlli del gestionale').'", "'.$module->fileurl('controlli.php').'?id_module='.$id_module.'");
}

function search(button) {
    let restore = buttonLoading(button);

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "post",
        data: {
            id_module: globals.id_module,
            op: "check",
        },
        success: function(data, textStatus, xhr){
            buttonRestore(button, restore);

            // Controlla se la risposta è un errore JSON
            try {
                let jsonData = JSON.parse(data);
                if (jsonData.error) {
                    $("#update-search").html("<div class=\"alert alert-danger mb-0\"><i class=\"fa fa-exclamation-circle\"></i> " + jsonData.message + "</div>");
                    return;
                }
            } catch (e) {
                // Non è JSON, continua con la logica normale
            }

            if (data === "none" || !data || data === "false") {
                $("#update-search").html("<div class=\"alert alert-success mb-0\"><i class=\"fa fa-check-circle\"></i> '.tr('Nessun aggiornamento disponibile').'</div>");
            } else {
                let beta_warning = data.includes("beta") ? "<div class=\"alert alert-warning mt-2 mb-0\"><i class=\"fa fa-exclamation-triangle\"></i> <strong>'.tr('Attenzione').':</strong> '.tr('La versione individuata è in fase sperimentale e potrebbe presentare malfunzionamenti. Se ne sconsiglia l\'aggiornamento in installazioni di produzione').'</div>" : "";
                $("#update-search").html("<div class=\"alert alert-info mb-0\"><i class=\"fa fa-download\"></i> <strong>'.tr('Nuovo aggiornamento disponibile').':</strong> " + data + "</div>" + beta_warning + "<div class=\"mt-2\"><a href=\"https://github.com/devcode-it/openstamanager/releases\" target=\"_blank\" class=\"btn btn-sm btn-primary\"><i class=\"fa fa-external-link\"></i> '.tr('Scarica da GitHub').'</a></div>");
            }
        },
        error: function(xhr, textStatus, errorThrown) {
            buttonRestore(button, restore);
            let errorMessage = "'.tr('Errore durante la ricerca degli aggiornamenti').': ";

            if (xhr.status === 0) {
                errorMessage += "'.tr('Impossibile connettersi al server').'";
            } else if (xhr.status === 500) {
                try {
                    let errorData = JSON.parse(xhr.responseText);
                    errorMessage += errorData.message || "'.tr('Errore interno del server').'";
                } catch (e) {
                    errorMessage += "'.tr('Errore interno del server').'";
                }
            } else {
                errorMessage += textStatus + " (" + xhr.status + ")";
            }

            $("#update-search").html("<div class=\"alert alert-danger mb-0\"><i class=\"fa fa-exclamation-circle\"></i> " + errorMessage + "</div>");
        },
        timeout: 30000 // 30 secondi di timeout
    });
}


</script>

<!-- Sezione principale aggiornamenti -->
<div class="row mb-4">
    <!-- Card Ricerca Aggiornamenti -->
    <div class="col-lg-4 mb-3">
        <div class="card card-info card-outline h-100 rounded">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-search"></i> '.tr('Ricerca Aggiornamenti').'
                </h3>
            </div>
            <div class="card-body text-center d-flex flex-column">
                <div class="mb-2">
                    <div class="d-flex align-items-center justify-content-center rounded-circle mx-auto mb-2" style="width: 50px; height: 50px; background-color: rgba(23, 162, 184, 0.1);">
                        <i class="fa fa-search" style="font-size: 1.5rem; color: #17a2b8;"></i>
                    </div>
                    <p class="text-muted mb-0">'.tr('Verifica la disponibilità di nuove versioni del gestionale').'</p>
                </div>
                <div id="update-search" class="mt-auto">';
if (extension_loaded('curl')) {
    // Recupera la data dell'ultimo upload nel modulo aggiornamenti
    $last_upload_query = 'SELECT created_at FROM zz_operations WHERE id_module = ? AND op = ? ORDER BY created_at DESC LIMIT 1';
    $last_upload = $database->fetchOne($last_upload_query, [$id_module, 'upload']);
    $last_upload_date = $last_upload ? date('d/m/Y H:i', strtotime((string) $last_upload['created_at'])) : tr('Mai');

    echo '                  <div class="mb-2">
                                <small class="text-muted">'.tr('Controlla automaticamente su GitHub. Data ultimo aggiornamento:').' '.$last_upload_date.'</small>
                            </div>
                            <button type="button" class="btn btn-info btn-block" onclick="search(this)">
                                <i class="fa fa-search mr-2"></i>'.tr('Verifica Aggiornamenti').'
                            </button>';
} else {
    echo '                  <div class="alert alert-warning mb-0 p-2 small">
                                <i class="fa fa-exclamation-triangle"></i>
                                <strong>'.tr('Funzione non disponibile').'</strong><br>
                                <small>'.tr('L\'estensione cURL di PHP non è installata').'</small>
                            </div>';
}

echo '              </div>
            </div>
        </div>
    </div>

    <!-- Card Caricamento Aggiornamenti -->
    <div class="col-lg-4 mb-3">
        <div class="card card-success card-outline h-100 rounded">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-upload"></i> '.tr('Installa Aggiornamenti').'
                </h3>
            </div>
            <div class="card-body text-center d-flex flex-column">
                <div class="mb-2">
                    <div class="d-flex align-items-center justify-content-center rounded-circle mx-auto mb-2" style="width: 50px; height: 50px; background-color: rgba(40, 167, 69, 0.1);">
                        <i class="fa fa-upload" style="font-size: 1.5rem; color: #28a745;"></i>
                    </div>
                    <p class="text-muted mb-0">'.tr('Carica e installa aggiornamenti o nuovi moduli').'</p>
                </div>';

// Avviso personalizzazioni nella card di caricamento
if ($has_any_errors) {
    echo '
                <div class="alert alert-warning mb-1 p-2 small" role="alert">
                    <i class="fa fa-exclamation-triangle mr-1"></i>
                    <strong>'.tr('Attenzione!').'</strong>
                    '.tr("Il gestionale presenta delle personalizzazioni:<br> si sconsiglia l'aggiornamento senza il supporto dell'assistenza ufficiale").'
                </div>';
}

echo '
                <div class="mt-auto">
                    <form action="'.base_path_osm().'/controller.php?id_module='.$id_module.'" method="post" enctype="multipart/form-data" id="update">
                        <input type="hidden" name="op" value="upload">
                        <div class="mb-2">
                            {[ "type": "file", "name": "blob", "required": 1, "accept": ".zip", "disabled": '.(setting('Attiva aggiornamenti') ? 0 : 1).' ]}
                        </div>
                        ';

if ($has_any_errors) {
    $disabled = 'disabled';
    echo '                          <div class="alert alert-warning mt-1 mb-2 p-2 small">
                                <div class="form-check mb-0 d-flex align-items-center">
                                    <input type="checkbox" id="aggiorna_custom" class="form-check-input mt-0" value="1">
                                    <label for="aggiorna_custom" class="form-check-label mb-0 ms-2">
                                        <i class="fa fa-exclamation-triangle mr-1 text-warning"></i>'.tr("Desidero comunque procedere all'aggiornamento").'
                                    </label>
                                </div>
                            </div>
                            <script>
                                $("#aggiorna_custom").change(function() {
                                    if(this.checked) {
                                        $("#aggiorna").removeClass("disabled");
                                    }else{
                                        $("#aggiorna").addClass("disabled");
                                    }
                                });
                            </script>';
}
echo '
                        <div class="mt-2">
                            <button type="button" class="btn btn-success btn-block '.$disabled.'" id="aggiorna" onclick="update()">
                                <i class="fa fa-upload mr-2"></i>'.tr('Carica aggiornamento').'
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Controlli di Integrità -->
    <div class="col-lg-4 mb-3">
        <div class="card card-primary card-outline h-100 rounded">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-shield"></i> '.tr('Controlli di Integrità').'
                </h3>
            </div>
            <div class="card-body text-center d-flex flex-column">
                <div class="mb-2">
                    <div class="d-flex align-items-center justify-content-center rounded-circle mx-auto mb-2" style="width: 50px; height: 50px; background-color: rgba(0, 123, 255, 0.1);">
                        <i class="fa fa-shield" style="font-size: 1.5rem; color: #007bff;"></i>
                    </div>
                    <p class="text-muted mb-0">'.tr('Verifica l\'integrità del gestionale').'</p>
                </div>
                <div class="mt-auto">
                    <button type="button" class="btn btn-primary btn-block" onclick="controlli(this)">
                        <i class="fa fa-stethoscope mr-2"></i>'.tr('Gestionale').'
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>';

// Sezione Verifica integrità
echo '
<div class="row">
    <div class="col-12">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-shield"></i> '.tr('Requisiti di sistema').'
                    <span class="tip" title="'.tr('Verifica dei requisiti minimi di sistema per il corretto funzionamento del gestionale').'">
                        <i class="fa fa-question-circle-o"></i>
                    </span>
                </h3>
            </div>
            <div class="card-body">';

include base_dir().'/include/init/requirements.php';

echo '
            </div>
        </div>
    </div>
</div>
</div>

<script>
function toggleModuleRow(rowId) {
    const previewCurrent = document.getElementById("preview_" + rowId + "_current");
    const fullCurrent = document.getElementById("full_" + rowId + "_current");
    const previewExpected = document.getElementById("preview_" + rowId + "_expected");
    const fullExpected = document.getElementById("full_" + rowId + "_expected");
    const icon = document.getElementById("icon_" + rowId);
    const text = document.getElementById("text_" + rowId);

    // Verifica se almeno uno degli elementi preview è visibile
    const isCollapsed = (previewCurrent && previewCurrent.style.display !== "none") ||
                       (previewExpected && previewExpected.style.display !== "none");

    if (isCollapsed) {
        // Espandi: nascondi preview, mostra full
        if (previewCurrent && fullCurrent) {
            previewCurrent.style.display = "none";
            fullCurrent.style.display = "block";
        }
        if (previewExpected && fullExpected) {
            previewExpected.style.display = "none";
            fullExpected.style.display = "block";
        }
        icon.className = "fa fa-compress";
        text.textContent = "Comprimi";
    } else {
        // Comprimi: mostra preview, nascondi full
        if (previewCurrent && fullCurrent) {
            previewCurrent.style.display = "block";
            fullCurrent.style.display = "none";
        }
        if (previewExpected && fullExpected) {
            previewExpected.style.display = "block";
            fullExpected.style.display = "none";
        }
        icon.className = "fa fa-expand";
        text.textContent = "Espandi";
    }
}
</script>';
