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

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="add">

	<!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="0">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data inizio').'", "name": "data_inizio", "required": 1 ]}
		</div>

        <div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data fine').'", "name": "data_fine", "required": 1 ]}
		</div>

		<div class="col-md-4">
			{[ "type": "number", "label": "'.tr('Fido assicurato').'", "name": "fido_assicurato", "required": 1, "icon-after": "'.currency().'" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';

echo '
<script>$(document).ready(init)</script>';

echo '
<script>
$(document).ready(function () {

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
