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

$op = 'add_sessione';
$button = '<i class="fa fa-edit"></i> '.tr('Aggiungi');

echo '
<form id="add_form" action="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.get('id_record').'" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="backto" value="record-edit">';

// Tecnico
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Tecnico').'", "name": "id_tecnico", "required": 1, "ajax-source": "tecnici" ]}
        </div>';

// Orari
echo '
        <div class="col-md-4">
            {[ "type": "timestamp", "label": "'.tr('Inizio attività').'", "name": "orario_inizio", "required": 1, "value": "'.$sessione['orario_inizio'].'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "timestamp", "label": "'.tr('Fine attività').'", "name": "orario_fine", "required": 1, "value": "'.$sessione['orario_fine'].'" ]}
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
        $("#orario_fine").data("DateTimePicker").minDate(e.date);
        $("#orario_fine").change();
    });
});
</script>';
