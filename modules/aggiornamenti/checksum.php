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
use Models\OperationLog;

include_once __DIR__.'/../../core.php';

// ========================================================================
// FUNZIONI HELPER PER CONTROLLO CHECKSUM
// ========================================================================

/**
 * Carica i checksum dal file principale e dai moduli premium
 */
function loadAllChecksums()
{
    $checksum = [];

    // Carica checksum principale
    $main_checksum_file = base_dir().'/checksum.json';
    if (file_exists($main_checksum_file)) {
        $contents = file_get_contents($main_checksum_file);
        $checksum = json_decode($contents, true) ?: [];
    }

    // Carica checksum dai moduli premium
    $module_checksum_files = glob(base_dir().'/modules/*/checksum.json') ?: [];
    foreach ($module_checksum_files as $module_checksum_file) {
        $module_contents = file_get_contents($module_checksum_file);
        $module_checksum = json_decode($module_contents, true);
        if (!empty($module_checksum)) {
            $checksum = array_merge($checksum, $module_checksum);
        }
    }

    return $checksum;
}

/**
 * Verifica l'integrità dei file rispetto ai checksum
 */
function verifyFileIntegrity($checksum)
{
    $errors = [];
    foreach ($checksum as $file => $md5) {
        $verifica = md5_file(base_dir().'/'.$file);
        if ($verifica != $md5) {
            $errors[] = $file;
        }
    }
    return $errors;
}

/**
 * Renderizza la tabella degli errori di integrità
 */
function renderIntegrityErrorsTable($errors)
{
    $html = '<p>'.tr("Segue l'elenco dei file che presentano checksum diverso rispetto a quello registrato nella versione ufficiale").'.</p>';
    $html .= '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> '.tr('Attenzione: questa funzionalità può presentare dei risultati falsamente positivi, sulla base del contenuto del file _FILE_', ['_FILE_' => '<b>checksum.json</b>']).'.'.
    '</div>';
    $html .= '<table class="table table-bordered table-striped table-hover"><thead><tr><th>'.tr('File con integrità errata').'</th></tr></thead><tbody>';

    foreach ($errors as $error) {
        $html .= '<tr><td class="file-integrity-error"><i class="fa fa-exclamation-triangle"></i> '.$error.'</td></tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

// ========================================================================
// LOGICA PRINCIPALE
// ========================================================================

echo '<div class="module-aggiornamenti">';

$file = basename(__FILE__);
$effettua_controllo = filter('effettua_controllo');

// Schermata di caricamento
if (empty($effettua_controllo)) {
    echo '
<div id="righe_controlli"></div>
<div class="alert alert-info" id="card-loading">
    <i class="fa fa-spinner fa-spin"></i> '.tr('Caricamento in corso').'...
</div>
<script>
var content = $("#righe_controlli");
var loader = $("#card-loading");
$(document).ready(function () {
    loader.show();
    content.html("");
    content.load("'.$structure->fileurl($file).'?effettua_controllo=1&id_module='.$id_module.'", function() {
        loader.hide();
    });
})
</script>';
    echo '</div>';
    return;
}

// Carica checksum
$checksum = loadAllChecksums();

if (empty($checksum)) {
    echo '<div class="alert alert-warning"><i class="fa fa-warning"></i> '.tr('Impossibile effettuare controlli di integrità in assenza del file _FILE_', ['_FILE_' => '<b>checksum.json</b>']).'.'.
    '</div></div>';
    return;
}

// Verifica integrità
$errors = verifyFileIntegrity($checksum);
OperationLog::setInfo('id_module', $id_module);

// Renderizza risultati
if (!empty($errors)) {
    echo renderIntegrityErrorsTable($errors);
} else {
    echo '<div class="alert alert-info"><i class="fa fa-info-circle"></i> '.tr('Nessun file con problemi di integrità').'.'.
    '</div>';
}

OperationLog::setInfo('options', json_encode(['controllo_name' => 'Controllo file'], JSON_UNESCAPED_UNICODE));
OperationLog::build('effettua_controllo');

echo '</div>';
