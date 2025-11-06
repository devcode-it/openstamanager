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
if (!function_exists('base_path')) {
    function base_path()
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

// Inizializzazione del modulo corrente
$module = Module::find($id_module);

// Aggiunta della classe per il modulo
echo '<div class="module-aggiornamenti">

<style>
.query-container {
    position: relative;
}

.query-toggle {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 3px;
    transition: all 0.2s ease;
}

.query-toggle:hover {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.query-toggle i {
    font-size: 10px;
    margin-right: 3px;
}

.query-toggle-container {
    text-align: left;
}

.query-cell {
    max-width: 300px;
    word-wrap: break-word;
}

.query-preview code,
.query-full code {
    font-size: 11px;
    line-height: 1.3;
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 3px;
    padding: 8px;
    display: block;
}

.btn-xs {
    padding: 4px 8px;
    font-size: 11px;
    line-height: 1.3;
    border-radius: 3px;
    font-weight: 600;
    border: 1px solid #007bff;
    color: #007bff;
    background-color: white;
    transition: all 0.2s ease;
}

.btn-xs i {
    font-size: 10px;
    margin-right: 3px;
}

.btn-xs:hover {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,123,255,0.2);
}

/* Padding per le celle della tabella */
.table td {
    padding: 12px 8px !important;
    vertical-align: top;
}

.table th {
    padding: 10px 8px !important;
}

/* Stili per i link ai moduli */
td a {
    text-decoration: none;
    color: inherit;
}

td a:hover {
    text-decoration: none;
    color: #007bff;
}

td a:hover strong {
    color: #007bff;
}

td a:hover code {
    color: #007bff;
    background-color: #e3f2fd;
}

.fa-external-link {
    opacity: 0.6;
    transition: opacity 0.2s ease;
}

td a:hover .fa-external-link {
    opacity: 1;
}
</style>';

if (!function_exists('normalizeForDiff')) {
    function normalizeForDiff($text)
    {
        $text = preg_replace('/<br\s*\/?>/i', '', (string) $text);
        $text = preg_replace('/\s+/', ' ', (string) $text);
        $text = str_replace(['"', "'"], "'", $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($text);
    }
}

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

        return '<code style="white-space: pre-wrap; word-break: break-all;">'.$decoded_content.'</code>';
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
            <code class="query-preview" style="white-space: pre-wrap; word-break: break-all;" id="preview_'.$row_id.'_'.$column_type.'">'.
                $preview_content.'
            </code>
            <code class="query-full" style="display: none; white-space: pre-wrap; word-break: break-all;" id="full_'.$row_id.'_'.$column_type.'">'.
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

    $current_normalized = normalizeForDiff($current);
    $expected_normalized = normalizeForDiff($expected);

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

    $i = 0;
    $j = 0;
    while ($i < count($current_words) || $j < count($expected_words)) {
        if ($i < count($current_words) && $j < count($expected_words) && $current_words[$i] === $expected_words[$j]) {
            // Parti uguali: mostra senza evidenziazione
            $word = htmlspecialchars($current_words[$i]);
            $current_highlighted .= $word;
            $expected_highlighted .= $word;
            ++$i;
            ++$j;
        } elseif ($i < count($current_words) && ($j >= count($expected_words) || $current_words[$i] !== $expected_words[$j])) {
            // Parti aggiunte nel current: evidenzia in verde
            $current_highlighted .= '<span class="diff-added" style="background-color: #d4edda; color: #155724;">'.htmlspecialchars($current_words[$i]).'</span>';
            ++$i;
        } elseif ($j < count($expected_words)) {
            // Parti rimosse (presenti nell'expected ma non nel current): evidenzia in rosso
            $expected_highlighted .= '<span class="diff-removed" style="background-color: #f8d7da; color: #721c24;">'.htmlspecialchars($expected_words[$j]).'</span>';
            ++$j;
        }
    }

    return [
        'current' => $current_highlighted,
        'expected' => $expected_highlighted,
    ];
}

if (function_exists('customComponents')) {
    $custom = customComponents();
    $custom_files = function_exists('customStructureWithFiles') ? customStructureWithFiles() : [];
    $tables = function_exists('customTables') ? customTables() : [];
    $custom_fields = function_exists('customFields') ? customFields() : [];

    $custom_views_not_standard = function_exists('customViewsNotStandard') ? customViewsNotStandard() : [];
    $custom_modules_not_standard = function_exists('customModulesNotStandard') ? customModulesNotStandard() : [];

    // Determina se ci sono errori per ogni sezione
    $has_file_errors = !empty($custom_files);
    $has_table_errors = !empty($tables);
    $has_view_errors = !empty($custom_views_not_standard);
    $has_module_errors = !empty($custom_modules_not_standard);
    $has_field_errors = !empty($custom_fields);
    $has_any_errors = !empty($custom) || $has_file_errors || $has_table_errors || $has_view_errors || $has_module_errors || $has_field_errors;

    if ($has_any_errors) {
        echo '
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa fa-exclamation-triangle"></i> '.tr('Personalizzazioni Rilevate').'
                            <span class="tip" title="'.tr('Elenco delle personalizzazioni rilevabili dal gestionale').'">
                                <i class="fa fa-question-circle-o"></i>
                            </span>
                        </h3>
                    </div>
                    <div class="card-body">';

        // Card File
        $file_icon = $has_file_errors ? 'fa-exclamation-circle' : 'fa-check-circle';
        $file_count = $has_file_errors ? count($custom_files) : 0;
        $file_expand_icon = $has_file_errors ? 'fa-minus' : 'fa-plus';

        echo '
        <div class="card card-outline card-'.($has_file_errors ? 'danger' : 'success').' requirements-card mb-3 collapsable '.($has_file_errors ? '' : 'collapsed-card').'">
            <div class="card-header with-border requirements-card-header requirements-card-header-'.($has_file_errors ? 'danger' : 'success').'">
                <h3 class="card-title requirements-card-title requirements-card-title-'.($has_file_errors ? 'danger' : 'success').'">
                    <i class="fa '.$file_icon.' mr-2 requirements-icon"></i>
                    '.tr('File personalizzati').'
                    '.($file_count > 0 ? '<span class="badge badge-info ml-2">'.$file_count.'</span>' : '').'
                </h3>
                <div class="card-tools pull-right">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa '.$file_expand_icon.'"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">';

        if ($has_file_errors) {
            echo '
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th width="40%">'.tr('Percorso').'</th>
                                    <th width="60%">'.tr('Files').'</th>
                                </tr>
                            </thead>
                            <tbody>';

            foreach ($custom_files as $element) {
                $files_list = implode(', ', array_map(fn ($file) => '<code>'.$file.'</code>', $element['files']));

                echo '
                                <tr>
                                    <td><strong>'.$element['path'].'</strong></td>
                                    <td>'.$files_list.'</td>
                                </tr>';
            }

            echo '
                            </tbody>
                        </table>
                    </div>';
        } else {
            echo '
                    <p class="text-success mb-0">
                        <i class="fa fa-check-circle"></i> '.tr('Nessun file personalizzato rilevato').'
                    </p>';
        }

        echo '
                </div>
        </div>';

        // Card Tabelle
        $table_icon = $has_table_errors ? 'fa-exclamation-circle' : 'fa-check-circle';
        $table_count = $has_table_errors ? count($tables) : 0;
        $table_expand_icon = $has_table_errors ? 'fa-minus' : 'fa-plus';

        echo '
        <div class="card card-outline card-'.($has_table_errors ? 'danger' : 'success').' requirements-card mb-3 collapsable '.($has_table_errors ? '' : 'collapsed-card').'">
            <div class="card-header with-border requirements-card-header requirements-card-header-'.($has_table_errors ? 'danger' : 'success').'">
                <h3 class="card-title requirements-card-title requirements-card-title-'.($has_table_errors ? 'danger' : 'success').'">
                    <i class="fa '.$table_icon.' mr-2 requirements-icon"></i>
                    '.tr('Tabelle personalizzate').'
                    '.($table_count > 0 ? '<span class="badge badge-info ml-2">'.$table_count.'</span>' : '').'
                </h3>
                <div class="card-tools pull-right">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa '.$table_expand_icon.'"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">';

        if ($has_table_errors) {
            echo '
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th width="100%">'.tr('Nome tabella').'</th>
                                </tr>
                            </thead>
                            <tbody>';

            foreach ($tables as $table) {
                echo '
                                <tr>
                                    <td><code>'.$table.'</code></td>
                                </tr>';
            }

            echo '
                            </tbody>
                        </table>
                    </div>';
        } else {
            echo '
                    <p class="text-success mb-0">
                        <i class="fa fa-check-circle"></i> '.tr('Nessuna tabella personalizzata rilevata').'
                    </p>';
        }

        echo '
                </div>
        </div>';

        // Card Viste
        $view_icon = $has_view_errors ? 'fa-exclamation-circle' : 'fa-check-circle';
        $view_count = $has_view_errors ? count($custom_views_not_standard) : 0;
        $view_expand_icon = $has_view_errors ? 'fa-minus' : 'fa-plus';

        echo '
        <div class="card card-outline card-'.($has_view_errors ? 'danger' : 'success').' requirements-card mb-3 collapsable '.($has_view_errors ? '' : 'collapsed-card').'">
            <div class="card-header with-border requirements-card-header requirements-card-header-'.($has_view_errors ? 'danger' : 'success').'">
                <h3 class="card-title requirements-card-title requirements-card-title-'.($has_view_errors ? 'danger' : 'success').'">
                    <i class="fa '.$view_icon.' mr-2 requirements-icon"></i>
                    '.tr('Viste personalizzate').'
                    '.($view_count > 0 ? '<span class="badge badge-info ml-2">'.$view_count.'</span>' : '').'
                </h3>
                <div class="card-tools pull-right">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa '.$view_expand_icon.'"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">';

        if ($has_view_errors) {
            echo '
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th width="12%">'.tr('Nome vista').'</th>
                                    <th width="15%">'.tr('Modulo').'</th>
                                    <th width="10%">'.tr('Tipo modifica').'</th>
                                    <th width="31.5%">'.tr('Query attuale').'</th>
                                    <th width="31.5%">'.tr('Query prevista').'</th>
                                </tr>
                            </thead>
                            <tbody>';

            foreach ($custom_views_not_standard as $index => $view) {
                $badge_class = match ($view['reason']) {
                    'Vista aggiuntiva' => 'badge-warning',
                    'Vista mancante' => 'badge-dark',
                    'Query modificata' => 'badge-info',
                    'Modulo non previsto' => 'badge-danger',
                    default => 'badge-secondary',
                };

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
        $module_icon = $has_module_errors ? 'fa-exclamation-circle' : 'fa-check-circle';
        $module_count = $has_module_errors ? count($custom_modules_not_standard) : 0;
        $module_expand_icon = $has_module_errors ? 'fa-minus' : 'fa-plus';

        echo '
        <div class="card card-outline card-'.($has_module_errors ? 'danger' : 'success').' requirements-card mb-3 collapsable '.($has_module_errors ? '' : 'collapsed-card').'">
            <div class="card-header with-border requirements-card-header requirements-card-header-'.($has_module_errors ? 'danger' : 'success').'">
                <h3 class="card-title requirements-card-title requirements-card-title-'.($has_module_errors ? 'danger' : 'success').'">
                    <i class="fa '.$module_icon.' mr-2 requirements-icon"></i>
                    '.tr('Moduli personalizzati').'
                    '.($module_count > 0 ? '<span class="badge badge-info ml-2">'.$module_count.'</span>' : '').'
                </h3>
                <div class="card-tools pull-right">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa '.$module_expand_icon.'"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">';

        if ($has_module_errors) {
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
                $badge_class = match ($modulo['reason']) {
                    'Options2 valorizzato' => 'badge-warning',
                    'Options modificato' => 'badge-info',
                    'Modulo non previsto' => 'badge-danger',
                    default => 'badge-secondary',
                };

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
        } else {
            echo '
                    <p class="text-success mb-0">
                        <i class="fa fa-check-circle"></i> '.tr('Nessun modulo personalizzato rilevato').'
                    </p>';
        }

        echo '
                </div>
            </div>
        </div>';

        // Sezione campi personalizzati (se presente)
        if ($has_field_errors) {
            echo '
            <div class="card card-info mb-3">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa fa-plus-square"></i> '.tr('Campi personalizzati aggiunti').'
                        <span class="badge badge-info">'.count($custom_fields).'</span>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-sm">
                            <thead>
                                <tr>
                                    <th width="40%">'.tr('Nome campo').'</th>
                                    <th width="30%">'.tr('Modulo').'</th>
                                    <th width="30%">'.tr('Plugin').'</th>
                                </tr>
                            </thead>
                            <tbody>';

            foreach ($custom_fields as $field) {
                echo '
                                <tr>
                                    <td><strong>'.$field['name'].'</strong></td>
                                    <td>'.($field['module_name'] ?: '<span class="text-muted">-</span>').'</td>
                                    <td>'.($field['plugin_name'] ?: '<span class="text-muted">-</span>').'</td>
                                </tr>';
            }

            echo '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>';
        }

        echo '
                    </div>
                </div>
            </div>
        </div>';
    } else {
        echo '
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa fa-check"></i> '.tr('Personalizzazioni').'
                        </h3>
                    </div>
                    <div class="card-body">
                        <p class="text-success mb-0"><i class="fa fa-check-circle"></i> '.tr('Non sono state rilevate personalizzazioni nel sistema').'.</p>
                    </div>
                </div>
            </div>
        </div>';
    }
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

function checksum(button) {
    openModal("'.tr('Controllo dei file').'", "'.$module->fileurl('checksum.php').'?id_module='.$id_module.'");
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
        <div class="card card-info card-outline h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-search"></i> '.tr('Ricerca Aggiornamenti').'
                </h3>
            </div>
            <div class="card-body text-center d-flex flex-column">
                <div class="mb-3">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background-color: rgba(23, 162, 184, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                        <i class="fa fa-search fa-lg" style="color: #17a2b8;"></i>
                    </div>
                    <p class="text-muted">'.tr('Verifica la disponibilità di nuove versioni del gestionale').'</p>
                </div>
                <div id="update-search" class="mt-auto">';
if (extension_loaded('curl')) {
    echo '                  <button type="button" class="btn btn-info btn-block" onclick="search(this)">
                                <i class="fa fa-search mr-2"></i>'.tr('Verifica Aggiornamenti').'
                            </button>
                            <div class="mt-2">
                                <small class="text-muted">'.tr('Controlla automaticamente su GitHub').'</small>
                            </div>';
} else {
    echo '                  <div class="alert alert-warning mb-0">
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
        <div class="card card-success card-outline h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-upload"></i> '.tr('Installa Aggiornamenti').'
                </h3>
            </div>
            <div class="card-body text-center d-flex flex-column">
                <div class="mb-3">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background-color: rgba(40, 167, 69, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                        <i class="fa fa-upload fa-lg" style="color: #28a745;"></i>
                    </div>
                    <p class="text-muted">'.tr('Carica e installa aggiornamenti o nuovi moduli').'</p>
                </div>';

// Avviso personalizzazioni nella card di caricamento
if ($has_any_errors) {
    echo '
                <div class="alert alert-warning mb-2" role="alert">
                    <i class="fa fa-exclamation-triangle mr-1"></i>
                    <strong>'.tr('Attenzione!').'</strong>
                    '.tr("Il gestionale presenta delle personalizzazioni: si sconsiglia l'aggiornamento senza il supporto dell'assistenza ufficiale").'
                </div>';
}

echo '
                <div class="mt-auto">
                    <form action="'.base_path().'/controller.php?id_module='.$id_module.'" method="post" enctype="multipart/form-data" id="update">
                        <input type="hidden" name="op" value="upload">
                        <div class="mb-3">
                            {[ "type": "file", "name": "blob", "required": 1, "accept": ".zip", "disabled": '.(setting('Attiva aggiornamenti') ? 0 : 1).' ]}
                        </div>
                        ';

if ($has_any_errors) {
    $disabled = 'disabled';
    echo '                          <div class="alert alert-warning mt-2 mb-2">
                                <div class="form-check mb-0">
                                    <input type="checkbox" id="aggiorna_custom" class="form-check-input" value="1">
                                    <label for="aggiorna_custom" class="form-check-label">
                                        <i class="fa fa-exclamation-triangle mr-2 text-warning"></i>'.tr("Desidero comunque procedere all'aggiornamento").'
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
        <div class="card card-primary card-outline h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-shield"></i> '.tr('Controlli di Integrità').'
                </h3>
            </div>
            <div class="card-body text-center d-flex flex-column">
                <div class="mb-3">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background-color: rgba(0, 123, 255, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                        <i class="fa fa-shield fa-lg" style="color: #007bff;"></i>
                    </div>
                    <p class="text-muted">'.tr('Verifica l\'integrità del sistema').'</p>
                </div>
                <div class="mt-auto">
                    <button type="button" class="btn btn-primary btn-block mb-2" onclick="checksum(this)">
                    <i class="fa fa-list-alt mr-2"></i>'.tr('File').'
                </button>
                <button type="button" class="btn btn-primary btn-block mb-2" onclick="database(this)">
                    <i class="fa fa-database mr-2"></i>'.tr('Database').'
                </button>
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
