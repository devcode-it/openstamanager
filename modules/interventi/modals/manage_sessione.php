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

include_once __DIR__.'/../../../core.php';
include_once __DIR__.'/../../../../core.php';

use Models\Module;

$show_prezzi = true;
// Limitazione delle azioni dei tecnici
if ($user['gruppo'] == 'Tecnici') {
    $show_prezzi = !empty($user['idanagrafica']) && setting('Mostra i prezzi al tecnico');
}

$sessione = $dbo->fetchOne('SELECT in_interventi_tecnici.*, an_anagrafiche.ragione_sociale, an_anagrafiche.deleted_at, in_interventi_tecnici.tipo_scontokm AS tipo_sconto_km, in_interventi_tecnici.prezzo_ore_unitario, in_interventi_tecnici.prezzo_km_unitario, in_interventi_tecnici.prezzo_dirittochiamata FROM in_interventi_tecnici INNER JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica WHERE in_interventi_tecnici.id = '.prepare(get('id_sessione')));

$op = 'edit_sessione';
$button = '<i class="fa fa-edit"></i> '.tr('Modifica');
echo '
<form id="add_form" action="'.base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.get('id_record').'" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_sessione" value="'.$sessione['id'].'">';

// Tecnico
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Tecnico').'", "name": "idtecnico", "value": "'.$sessione['idtecnico'].'", "required": 1, "ajax-source": "tecnici", "icon-after": "add|'.Module::where('name', 'Anagrafiche')->first()->id.'|tipoanagrafica=Tecnico&readonly_tipo=1" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Tipo attività').'", "name": "idtipointerventot", "value": "'.$sessione['idtipointervento'].'", "required": 1, "ajax-source": "tipiintervento-tecnico", "select-options": '.json_encode(['idtecnico' => $sessione['idtecnico'], 'id_intervento' => $id_record]).' ]}
        </div>
    </div>';

$class = $show_prezzi ? '' : 'hide';
// Orari
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "timestamp", "label": "'.tr('Inizio attività').'", "name": "orario_inizio", "required": 1, "value": "'.$sessione['orario_inizio'].'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "timestamp", "label": "'.tr('Fine attività').'", "name": "orario_fine", "required": 1, "value": "'.$sessione['orario_fine'].'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Km').'", "name": "km", "value": "'.$sessione['km'].'","decimals": "qta"]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 '.$class.'">
            {[ "type": "number", "label": "'.tr('Addebito orario').'", "name": "prezzo_ore_unitario", "value": "'.$sessione['prezzo_ore_unitario'].'" ]}
        </div>

        <div class="col-md-4 '.$class.'">
            {[ "type": "number", "label": "'.tr('Addebito al km').'", "name": "prezzo_km_unitario", "value": "'.$sessione['prezzo_km_unitario'].'" ]}
        </div>

        <div class="col-md-4 '.$class.'">
            {[ "type": "number", "label": "'.tr('Addebito diritto ch.').'", "name": "prezzo_dirittochiamata", "value": "'.$sessione['prezzo_dirittochiamata'].'" ]}
        </div>
    </div>

    <div class="row">';

// Sconto ore
echo '
        <div class="col-md-4 '.$class.'" >
            {[ "type": "number", "label": "'.tr('Sconto orario').'", "name": "sconto", "value": "'.$sessione['sconto_unitario'].'", "icon-after": "choice|untprc|'.$sessione['tipo_sconto'].'"]}
        </div>';

// Sconto km
echo '
        <div class="col-md-4 '.$class.'">
            {[ "type": "number", "label": "'.tr('Sconto al km').'", "name": "sconto_km", "value": "'.$sessione['scontokm_unitario'].'", "icon-after": "choice|untprc|'.$sessione['tipo_sconto_km'].'"]}
        </div>';

echo '
    </div>

    <div class="row">
        <div class="col-md-8">
            {[ "type": "text", "label": "'.tr('Note').'", "name": "note", "value": "'.$sessione['note'].'" ]}
        </div>
    </div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="button" class="btn btn-primary" onclick="salvaSessione()">'.$button.'</button>
		</div>
    </div>
</form>';

echo '
<script>$(document).ready(init)</script>';

echo '
<script>
$(document).ready(function () {
    // Quando modifico orario inizio, allineo anche l\'orario fine
    $("#orario_inizio").on("dp.change", function (e) {
        if($("#orario_fine").data("DateTimePicker").date() < e.date){
            $("#orario_fine").data("DateTimePicker").date(e.date);
        }
    });

    $("#orario_fine").on("dp.change", function (e) {
        if($("#orario_inizio").data("DateTimePicker").date() > e.date){
            $("#orario_inizio").data("DateTimePicker").date(e.date);
        }
    });

    // Quando cambio il tecnico, aggiorno le select-options del tipo attività
    $("#idtecnico").change(function() {
        var idtecnico = $(this).val();

        if (!idtecnico) {
            return;
        }

        // Aggiorno le select-options per ricaricare i tipi di intervento del nuovo tecnico
        $("#idtipointerventot").setSelectOption("idtecnico", idtecnico);
        $("#idtipointerventot").setSelectOption("id_intervento", globals.id_record);

        // Resetto e ricarico il select dei tipi di intervento
        $("#idtipointerventot").selectReset();
    });

    // Quando cambio il tipo di intervento, aggiorno i prezzi
    $("#idtipointerventot").change(function() {
        var data = $(this).selectData();

        if (data) {
            $("#prezzo_ore_unitario").val(data.prezzo_ore_unitario);
            $("#prezzo_km_unitario").val(data.prezzo_km_unitario);
            $("#prezzo_dirittochiamata").val(data.prezzo_dirittochiamata);
        }
    });
});

function salvaSessione() {
    // Validazione del form
    var valid = $("#add_form").parsley().validate();
    if (!valid) {
        return false;
    }

    // Invio dei dati via AJAX
    $("#add_form").ajaxSubmit({
        url: globals.rootdir + "/actions.php",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            ajax: true,
        },
        type: "post",
        success: function(response) {
            renderMessages();

            // Chiusura del modale
            $("#modals > div").modal("hide");

            // Ricaricamento dei costi
            caricaCosti();
            caricaTecnici();
        },
        error: function() {
            alert("'.tr('Errore durante il salvataggio').'");
        }
    });
}
</script>';
