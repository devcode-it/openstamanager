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

use Modules\Interventi\Intervento;

$show_prezzi = true;
// Limitazione delle azioni dei tecnici
if ($user['gruppo'] == 'Tecnici') {
    $show_prezzi = !empty($user['idanagrafica']) && setting('Mostra i prezzi al tecnico');
}

$sessione = $dbo->fetchOne('SELECT in_interventi_tecnici.*, an_anagrafiche.ragione_sociale, an_anagrafiche.deleted_at, in_interventi_tecnici.tipo_scontokm AS tipo_sconto_km, in_interventi_tecnici.prezzo_ore_unitario, in_interventi_tecnici.prezzo_km_unitario, in_interventi_tecnici.prezzo_dirittochiamata FROM in_interventi_tecnici INNER JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica WHERE in_interventi_tecnici.id = '.prepare(get('id_sessione')));

$op = 'edit_sessione';
$button = '<i class="fa fa-edit"></i> '.tr('Modifica');

$intervento = Intervento::find($id_record);

if (!empty($intervento->id_contratto)) {
    $query = 'SELECT `in_tipiintervento`.`id`, `name`, `co_contratti_tipiintervento`.`costo_ore` AS prezzo_ore_unitario, `co_contratti_tipiintervento`.`costo_km` AS prezzo_km_unitario, `co_contratti_tipiintervento`.`costo_dirittochiamata` AS prezzo_dirittochiamata FROM `in_tipiintervento` LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id` = `in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).') INNER JOIN `co_contratti_tipiintervento` ON `in_tipiintervento`.`id` = `co_contratti_tipiintervento`.`idtipointervento` WHERE `co_contratti_tipiintervento`.`idcontratto` = '.prepare($intervento->id_contratto).' AND `in_tipiintervento`.`deleted_at` IS NULL ORDER BY `name`';
} else {
    $query = 'SELECT `in_tipiintervento`.`id`, `name`, `in_tariffe`.`costo_ore` AS prezzo_ore_unitario, `in_tariffe`.`costo_km` AS prezzo_km_unitario, `in_tariffe`.`costo_dirittochiamata` AS prezzo_dirittochiamata FROM `in_tipiintervento` LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id` = `in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).') INNER JOIN `in_tariffe` ON `in_tipiintervento`.`id` = `in_tariffe`.`idtipointervento` WHERE `in_tariffe`.`idtecnico` = '.prepare($sessione['idtecnico']).' AND `in_tipiintervento`.`deleted_at` IS NULL ORDER BY `name`';
}
echo '
<form id="add_form" action="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.get('id_record').'" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_sessione" value="'.$sessione['id'].'">
	<input type="hidden" name="idtecnico" value="'.$sessione['idtecnico'].'">';

// Tecnico
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "span", "label": "'.tr('Tecnico').'", "name": "tecnico", "required": 0, "value": "'.$sessione['ragione_sociale'].' '.(!empty($sessione['deleted_at']) ? '<small class="text-danger"><em>('.tr('Eliminato').')</em></small>' : '').'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Tipo attività').'", "name": "idtipointerventot", "value": "'.$sessione['idtipointervento'].'", "required": 1, "values": "query='.$query.'" ]}
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
            {[ "type": "number", "label": "'.tr('Km').'", "name": "km", "value": "'.$sessione['km'].'"]}
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

    $("#idtipointerventot").change(function() {
        data = $(this).selectData();

        $("#prezzo_ore_unitario").val(data.prezzo_ore_unitario);
        $("#prezzo_km_unitario").val(data.prezzo_km_unitario);
        $("#prezzo_dirittochiamata").val(data.prezzo_dirittochiamata);
    });
});
</script>';
