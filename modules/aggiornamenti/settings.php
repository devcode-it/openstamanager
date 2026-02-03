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
<div id="righe_controlli_settings">
</div>

<div class="alert alert-info" id="card-loading-settings">
    <i class="fa fa-spinner fa-spin"></i> '.tr('Caricamento in corso').'...
</div>

<script>
var content_settings = $("#righe_controlli_settings");
var loader_settings = $("#card-loading-settings");
$(document).ready(function () {
    loader_settings.show();

    content_settings.html("");
    content_settings.load("'.$structure->fileurl($file).'?effettua_controllo=1", function() {
        loader_settings.hide();
    });
})
</script>';

    return;
}

// Carica il file di riferimento principale per le impostazioni
$contents = file_get_contents(base_dir().'/settings.json');
$data_settings = json_decode($contents, true);

// Carica e accoda le impostazioni dai file settings.json presenti nelle sottocartelle di modules/
$modules_dir = base_dir().'/modules/';
$settings_json_files = glob($modules_dir.'*/settings.json');

if (!empty($settings_json_files)) {
    foreach ($settings_json_files as $settings_json_file) {
        $settings_contents = file_get_contents($settings_json_file);
        $settings_data = json_decode($settings_contents, true);

        if (!empty($settings_data) && is_array($settings_data)) {
            // Accoda le impostazioni del modulo a quelle principali
            $data_settings = array_merge($data_settings, $settings_data);
        }
    }
}

$settings = Update::getSettings();
$results_settings = settings_diff($data_settings, $settings);
$results_settings_added = settings_diff($settings, $data_settings);

if (!empty($results_settings) || !empty($results_settings_added)) {
    $settings_danger_count = 0;
    $settings_warning_count = 0;
    $settings_info_count = 0;

    foreach ($results_settings as $key => $setting) {
        if (!$setting['current']) {
            ++$settings_danger_count;
        } else {
            ++$settings_warning_count;
        }
    }

    foreach ($results_settings_added as $key => $setting) {
        if ($setting['current'] == null) {
            ++$settings_info_count;
        }
    }

    $settings_badge_html = Utils::generateBadgeHtml($settings_danger_count, $settings_warning_count, $settings_info_count);
    $settings_border_color = Utils::determineBorderColor($settings_danger_count, $settings_warning_count);

    echo '
<div class="mb-3">
    <div class="d-flex align-items-center justify-content-between p-2 module-aggiornamenti db-section-header-dynamic" style="border-left-color: '.$settings_border_color.';" onclick="$(this).next().slideToggle();">
        <div>
            <strong>zz_settings</strong>
            '.$settings_badge_html.'
        </div>
        <i class="fa fa-chevron-down"></i>
    </div>
    <div class="module-aggiornamenti db-section-content">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>'.tr('Nome').'</th>
                        <th class="module-aggiornamenti table-col-type">'.tr('Tipo').'</th>
                        <th>'.tr('Soluzione').'</th>
                    </tr>
                </thead>
                <tbody>';
    foreach ($results_settings as $key => $setting) {
        $badge_text = '';
        $badge_color = '';
        if (!$setting['current']) {
            // Gestisci il caso di valore null
            $valore_value = ($setting['expected'] === null) ? 'NULL' : prepare($setting['expected']);
            $query = 'INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('.prepare($key).', '.$valore_value.", 'string', 1, 'Generali')";
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
                        <td class="text-center">
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
                        <td class="text-center">
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
} else {
    echo '
<div class="alert alert-info alert-database">
    <i class="fa fa-info-circle"></i> '.tr('Non sono state rilevate personalizzazioni delle impostazioni').'
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

// Log dell'esecuzione del controllo impostazioni
OperationLog::setInfo('id_module', $id_module);
OperationLog::setInfo('options', json_encode(['controllo_name' => 'Controllo impostazioni'], JSON_UNESCAPED_UNICODE));
OperationLog::build('effettua_controllo');
