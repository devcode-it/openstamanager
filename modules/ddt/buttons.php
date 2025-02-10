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

use Models\Module;

include_once __DIR__.'/../../core.php';

$id_module_collegamento = $ddt->direzione == 'entrata' ? Module::where('name', 'Ddt in entrata')->first()->id : Module::where('name', 'Ddt in uscita')->first()->id;

// Informazioni sui movimenti interni
if (!empty($ddt->id_ddt_trasporto_interno)) {
    echo '
<div class="tip" data-widget="tooltip" title="'.tr("Questo ddt è impostato sull'anagrafica Azienda, e pertanto rappresenta un trasporto interno di merce: il movimento tra sedi distinte è necessario completato tramite un DDT in direzione opposta").'.">
    <a class="btn btn-info" href="'.base_url().'/editor.php?id_module='.$id_module_collegamento.'&id_record='.$ddt->id_ddt_trasporto_interno.'">
        <i class="fa fa-truck"></i> '.tr('DDT di completamento trasporto').'
    </a>
</div>';
} elseif ($azienda->id == $ddt->anagrafica->id) {
    echo '
<div class="tip" data-widget="tooltip" title="'.tr("Questo ddt è impostato sull'anagrafica Azienda, e pertanto rappresenta un trasferimento interno di merci tra sedi distinte dell'Azienda: per completare la movimentazione, è necessario generare un DDT in direzione opposta tramite questo pulsante").'.">
    <button class="btn btn-warning '.($ddt->isImportabile() ? '' : 'disabled').'" onclick="completaTrasporto()">
        <i class="fa fa-truck"></i> '.tr('Completa trasferimento tra sedi ').'
    </button>
</div>

<script>
function completaTrasporto() {
    swal({
        title: "'.tr('Completare il trasporto?').'",
        html: "'.tr('Sei sicuro di voler completare il trasporto interno tramite un DDT in direzione opposta?').'" + `<br><br>{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_module_collegamento, 'is_sezionale' => 1]).', "value": "'.$_SESSION['module_'.$id_module_collegamento]['id_segment'].'" ]}`,
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn btn-lg btn-success",
        confirmButtonText: "'.tr('Completa').'",
    }).then(
        function() {
            if ($("select[name=id_segment]").val() == null) {
                swal( "'.tr('Attenzione').'", "'.tr('Devi prima selezionare un segmento').'...", "warning");
            } else {
                location.href = globals.rootdir + "/editor.php?id_module='.$id_module.'&id_segment=" + $("select[name=id_segment]").val() + "&id_record='.$id_record.'&op=completa_trasporto&backto=record-edit";
            }
        },
        function() {},
        start_superselect(),
    );
}
</script>';
}

if (!$is_anagrafica_deleted) {
    // Informazioni sull'importabilità del DDT
    $stati = $database->fetchArray('SELECT `title` as descrizione FROM `dt_statiddt` LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt`.`id` = `dt_statiddt_lang`.`id_record` AND `dt_statiddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `is_fatturabile` = 1');

    foreach ($stati as $stato) {
        $stati_importabili[] = $stato['descrizione'];
    }

    $causali = $database->fetchArray('SELECT `title` FROM `dt_causalet` LEFT JOIN `dt_causalet_lang` ON (`dt_causalet`.`id` = `dt_causalet_lang`.`id_record` 
    AND `dt_causalet_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `is_importabile` = 1');

    foreach ($causali as $causale) {
        $causali_importabili[] = $causale['title'];
    }

    echo '
    <div class="tip" data-widget="tooltip" title="'.tr('Il ddt è fatturabile solo se si trova negli stati _STATE_LIST_ e la relativa causale è una delle seguenti: _CAUSALE_LIST_', [
        '_STATE_LIST_' => implode(', ', $stati_importabili),
        '_CAUSALE_LIST_' => implode(', ', $causali_importabili),
    ]).'">
        <button class="btn btn-info '.($ddt->isImportabile() ? '' : 'disabled').'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-widget="modal" data-title="'.tr('Crea ').($ddt->reversed ? 'nota di credito' : ($dir == 'entrata' ? 'fattura di vendita' : 'fattura di acquisto')).'"><i class="fa fa-magic"></i> '.tr('Crea ').($ddt->reversed ? 'nota di credito' : ($dir == 'entrata' ? 'fattura di vendita' : 'fattura di acquisto')).'
        </button>
    </div>';
}

// Duplica ddt
echo '
<button type="button" class="btn btn-primary ask" data-title="'.tr('Duplicare questo Ddt?').'" data-msg="'.tr('Clicca su tasto duplica per procedere.').'" data-op="copy" data-button="'.tr('Duplica').'" data-class="btn btn-lg btn-primary" data-backto="record-edit">
    <i class="fa fa-copy"></i> '.tr('Duplica ddt').'
</button>';
