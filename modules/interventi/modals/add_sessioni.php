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

$sessione = $dbo->fetchOne('SELECT in_interventi_tecnici.*, an_anagrafiche.ragione_sociale, an_anagrafiche.deleted_at, in_interventi_tecnici.tipo_scontokm AS tipo_sconto_km, in_interventi_tecnici.prezzo_ore_unitario, in_interventi_tecnici.prezzo_km_unitario, in_interventi_tecnici.prezzo_dirittochiamata FROM in_interventi_tecnici INNER JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica WHERE in_interventi_tecnici.id = '.prepare(get('id_sessione')));

$op = 'add_sessioni';
$button = '<i class="fa fa-edit"></i> '.tr('Aggiungi');
$orario_inizio = date('H').':00:00';
$orario_fine = date('H').':00:00';

echo '
<form id="add_form" action="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.get('id_record').'" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">';
// Orari
echo '
        <div class="col-md-3">
            {[ "type": "time", "label": "'.tr('Ora inizio').'", "name": "orario_inizio", "required": 1, "value": "'.$orario_inizio.'" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "time", "label": "'.tr('Ora fine').'", "name": "orario_fine", "required": 1, "value": "'.$orario_fine.'" ]}
        </div>';

// Date
echo '
        <div class="col-md-3">
            {[ "type": "date", "label": "'.tr('Data inizio').'", "name": "data_inizio", "required": 1, "value": "-now-" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "date", "label": "'.tr('Data fine').'", "name": "data_fine", "required": 1, "value": "-now-" ]}
        </div>
    </div>';

// Tecnici
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "multiple":"1", "label": "'.tr('Giorni').'", "name": "giorni[]", "required": 0, "value": "'.strtolower(setting('Giorni lavorativi')).'", "values": "list=\"lunedì\":\"'.tr('Lunedì').'\", \"martedì\":\"'.tr('Martedì').'\", \"mercoledì\":\"'.tr('Mercoledì').'\", \"giovedì\":\"'.tr('Giovedì').'\", \"venerdì\":\"'.tr('Venerdì').'\", \"sabato\":\"'.tr('Sabato').'\", \"domenica\":\"'.tr('Domenica').'\"" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "select", "multiple": "1", "label": "'.tr('Tecnici').'", "name": "id_tecnici[]", "required": 1, "ajax-source": "tecnici" ]}
        </div>
    </div>';

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary">'.$button.'</button>
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

    $("#data_inizio").on("dp.change", function (e) {
        if($("#data_fine").data("DateTimePicker").date() < e.date){
            $("#data_fine").data("DateTimePicker").date(e.date);
        }
    });

    $("#data_fine").on("dp.change", function (e) {
        if($("#data_inizio").data("DateTimePicker").date() > e.date){
            $("#data_inizio").data("DateTimePicker").date(e.date);
        }
    });
});
</script>';
