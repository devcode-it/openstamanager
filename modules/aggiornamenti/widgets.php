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
use Update;

$query_conflitti = [];

// Funzioni per il controllo database (wrapper per compatibilità)
if (!function_exists('widgets_diff')) {
    function widgets_diff($expected, $current)
    {
        return IntegrityChecker::widgetsDiff($expected, $current);
    }
}

// Funzione per trovare i widgets aggiunti (non previsti)
if (!function_exists('widgets_added')) {
    function widgets_added($current, $expected)
    {
        return IntegrityChecker::widgetsAdded($current, $expected);
    }
}

$file = basename(__FILE__);
$effettua_controllo = filter('effettua_controllo');

if (empty($effettua_controllo)) {
    echo '
<div id="righe_controlli_widgets">
</div>

<div class="alert alert-info" id="card-loading-widgets">
    <i class="fa fa-spinner fa-spin"></i> '.tr('Caricamento in corso').'...
</div>

<script>
var content_widgets = $("#righe_controlli_widgets");
var loader_widgets = $("#card-loading-widgets");
$(document).ready(function () {
    loader_widgets.show();

    content_widgets.html("");
    content_widgets.load("'.$structure->fileurl($file).'?effettua_controllo=1", function() {
        loader_widgets.hide();
    });
})
</script>';

    return;
}

// Carica il file di riferimento principale per i widgets
$contents_widgets = file_get_contents(base_dir().'/widgets.json');
$data_widgets = json_decode($contents_widgets, true);

// Carica e accoda i widgets dai file widgets.json presenti nelle sottocartelle di modules/
$modules_dir = base_dir().'/modules/';
$widgets_json_files = glob($modules_dir.'*/widgets.json');

if (!empty($widgets_json_files)) {
    foreach ($widgets_json_files as $widgets_json_file) {
        $widgets_contents = file_get_contents($widgets_json_file);
        $widgets_data = json_decode($widgets_contents, true);

        if (!empty($widgets_data) && is_array($widgets_data)) {
            // Accoda i widgets del modulo a quelli principali
            $data_widgets = array_merge($data_widgets, $widgets_data);
        }
    }
}

$widgets = Update::getWidgets();
$results_widgets = widgets_diff($data_widgets, $widgets);
$results_widgets_added = widgets_added($widgets, $data_widgets);

if (!empty($results_widgets) || !empty($results_widgets_added)) {
    $widgets_danger_count = 0;
    $widgets_warning_count = 0;
    $widgets_info_count = 0;

    foreach ($results_widgets as $module_key => $module_widgets) {
        if (is_array($module_widgets)) {
            foreach ($module_widgets as $widget_name => $widget) {
                if (!isset($widget['current']) || !$widget['current']) {
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
                if (!isset($widget['current']) || $widget['current'] == null) {
                    ++$widgets_info_count;
                }
            }
        }
    }

    $widgets_badge_html = Utils::generateBadgeHtml($widgets_danger_count, $widgets_warning_count, $widgets_info_count);
    $widgets_border_color = Utils::determineBorderColor($widgets_danger_count, $widgets_warning_count);

    echo '
<div class="mb-3">
    <div class="d-flex align-items-center justify-content-between p-2 module-aggiornamenti db-section-header-dynamic" style="border-left-color: '.$widgets_border_color.';" onclick="$(this).next().slideToggle();">
        <div>
            <strong>zz_widgets</strong>
            '.$widgets_badge_html.'
        </div>
        <i class="fa fa-chevron-down"></i>
    </div>
    <div class="module-aggiornamenti db-section-content">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>'.tr('Nome').'</th>
                        <th>'.tr('Modulo').'</th>
                        <th class="module-aggiornamenti table-col-type">'.tr('Tipo').'</th>
                        <th>'.tr('Soluzione').'</th>
                    </tr>
                </thead>
                <tbody>';
    foreach ($results_widgets as $module_key => $module_widgets) {
        if (is_array($module_widgets)) {
            foreach ($module_widgets as $widget_name => $widget) {
                $badge_text = '';
                $badge_color = '';
                if (!isset($widget['current']) || !$widget['current']) {
                    $query = "INSERT INTO `zz_widgets` (`name`, `id_module`, `query`) VALUES ('".$widget_name."', '".$module_key."', '".$widget['expected']."')";
                    $query_conflitti[] = $query.';';
                    $badge_text = 'Widget mancante';
                    $badge_color = 'danger';
                } else {
                    $query = 'UPDATE `zz_widgets` SET `query` = '.prepare($widget['expected']).' WHERE `name` = '.prepare($widget_name).' AND `id_module` = '.prepare($module_key);
                    $query_conflitti[] = $query.';';
                    $badge_text = 'Widget modificato';
                    $badge_color = 'warning';
                }

                echo '
                    <tr>
                        <td class="column-name">
                            '.$widget_name.'
                        </td>
                        <td>
                            '.$module_key.'
                        </td>
                        <td class="text-center">
                            <span class="badge badge-'.$badge_color.'">'.$badge_text.'</span>
                        </td>
                        <td class="column-conflict">
                            '.$query.';
                        </td>
                    </tr>';
            }
        }
    }

    foreach ($results_widgets_added as $module_key => $module_widgets) {
        if (is_array($module_widgets)) {
            foreach ($module_widgets as $widget_name => $widget) {
                if (!isset($widget['expected']) || $widget['expected'] == null) {
                    $badge_text = 'Widget non previsto';
                    $badge_color = 'info';
                    echo '
                    <tr>
                        <td class="column-name">
                            '.$widget_name.'
                        </td>
                        <td>
                            '.$module_key.'
                        </td>
                        <td class="text-center">
                            <span class="badge badge-'.$badge_color.'">'.$badge_text.'</span>
                        </td>
                        <td class="column-conflict">
                            '.$widget['expected'] ?? ''.'
                        </td>
                    </tr>';
                }
            }
        }
    }

    echo '
                </tbody>
            </table>
        </div>
    </div>
</div>';
} else {
    echo '
<div class="alert alert-info alert-database">
    <i class="fa fa-info-circle"></i> '.tr('Non sono stati rilevati widgets personalizzati').'
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

// Log dell'esecuzione del controllo widgets
OperationLog::setInfo('id_module', $id_module);
OperationLog::setInfo('options', json_encode(['controllo_name' => 'Controllo widgets'], JSON_UNESCAPED_UNICODE));
OperationLog::build('effettua_controllo');