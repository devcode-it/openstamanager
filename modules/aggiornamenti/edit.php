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

if (!function_exists('normalizeForDiff')) {
    function normalizeForDiff($text) {
        $text = preg_replace('/<br\s*\/?>/i', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = str_replace(['"', "'", '`'], "'", $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim($text);
    }
}

function highlightDifferences($current, $expected) {
    if (empty($expected)) {
        return [
            'current' => htmlspecialchars($current),
            'expected' => '<span class="text-muted">-</span>'
        ];
    }

    $current_normalized = normalizeForDiff($current);
    $expected_normalized = normalizeForDiff($expected);

    $current_words = preg_split('/(\s+|[(),\'"`]|<[^>]*>)/', $current_normalized, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $expected_words = preg_split('/(\s+|[(),\'"`]|<[^>]*>)/', $expected_normalized, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    if ($current_normalized === $expected_normalized) {
        return [
            'current' => '<span class="diff-unchanged">' . htmlspecialchars($current) . '</span>',
            'expected' => '<span class="diff-unchanged">' . htmlspecialchars($expected) . '</span>'
        ];
    }

    $current_highlighted = '';
    $expected_highlighted = '';

    $i = 0; $j = 0;
    while ($i < count($current_words) || $j < count($expected_words)) {
        if ($i < count($current_words) && $j < count($expected_words) && $current_words[$i] === $expected_words[$j]) {
            $word = htmlspecialchars($current_words[$i]);
            $current_highlighted .= '<span class="diff-unchanged">' . $word . '</span>';
            $expected_highlighted .= '<span class="diff-unchanged">' . $word . '</span>';
            $i++; $j++;
        } elseif ($i < count($current_words) && ($j >= count($expected_words) || $current_words[$i] !== $expected_words[$j])) {
            $current_highlighted .= '<span class="diff-added">' . htmlspecialchars($current_words[$i]) . '</span>';
            $i++;
        } elseif ($j < count($expected_words)) {
            $expected_highlighted .= '<span class="diff-removed">' . htmlspecialchars($expected_words[$j]) . '</span>';
            $j++;
        }
    }

    return [
        'current' => $current_highlighted,
        'expected' => $expected_highlighted
    ];
}

if (function_exists('customComponents')) {
    $custom = customComponents();
    $custom_files = customStructureWithFiles();
    $tables = customTables();
    $custom_fields = customFields();

    $custom_views_not_standard = customViewsNotStandard();

    // Determina se ci sono errori per ogni sezione
    $has_file_errors = !empty($custom_files);
    $has_table_errors = !empty($tables);
    $has_view_errors = !empty($custom_views_not_standard);
    $has_field_errors = !empty($custom_fields);
    $has_any_errors = !empty($custom) || $has_file_errors || $has_table_errors || $has_view_errors || $has_field_errors;

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
                $files_list = implode(', ', array_map(function($file) {
                    return '<code>'.$file.'</code>';
                }, $element['files']));

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

            foreach ($custom_views_not_standard as $view) {
                switch ($view['reason']) {
                    case 'Vista aggiuntiva':
                        $badge_class = 'badge-warning';
                        break;
                    case 'Vista mancante':
                        $badge_class = 'badge-dark';
                        break;
                    case 'Query modificata':
                        $badge_class = 'badge-info';
                        break;
                    case 'Modulo non previsto':
                        $badge_class = 'badge-danger';
                        break;
                    default:
                        $badge_class = 'badge-secondary';
                }

                if (empty($view['current_query'])) {
                    $current_query_display = '<span class="text-muted">-</span>';
                    $expected_query_display = '<code style="white-space: pre-wrap; word-break: break-all;">' . htmlspecialchars($view['expected_query']) . '</code>';
                } else {
                    $diff_result = highlightDifferences($view['current_query'], $view['expected_query']);

                    $current_query_display = '<code style="white-space: pre-wrap; word-break: break-all;">' . $diff_result['current'] . '</code>';
                    $expected_query_display = '<code style="white-space: pre-wrap; word-break: break-all;">' . $diff_result['expected'] . '</code>';
                }

                $module_id_display = $view['module_id'] ? 'ID: '.$view['module_id'] : 'Mancante';
                $module_display = $view['module_name'] . ' <small class="text-muted">('.$module_id_display.')</small>';

                $view_name_display = !empty($view['name']) ?
                    $view['name'] :
                    '(Assente)';

                echo '
                                <tr>
                                    <td><code>'.$view_name_display.'</code></td>
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
                $("#update-search").html("<div class=\"alert alert-info mb-0\"><i class=\"fa fa-download\"></i> <strong>'.tr("Nuovo aggiornamento disponibile").':</strong> " + data + "</div>" + beta_warning + "<div class=\"mt-2\"><a href=\"https://github.com/devcode-it/openstamanager/releases\" target=\"_blank\" class=\"btn btn-sm btn-primary\"><i class=\"fa fa-external-link\"></i> '.tr('Scarica da GitHub').'</a></div>");
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
</div>';
