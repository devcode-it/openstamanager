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
<form action="" method="post" role="form" id="form_sedi">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
    <input type="hidden" name="id_record" value="'.$record['id'].'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data inizio').'", "name": "data_inizio", "value": "'.$record['data_inizio'].'", "required": 1 ]}
		</div>

		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data fine').'", "name": "data_fine", "value": "'.$record['data_fine'].'", "required": 1 ]}
		</div>

		<div class="col-md-4">
			{[ "type": "number", "label": "'.tr('Fido assicurato').'", "name": "fido_assicurato", "value": "'.$record['fido_assicurato'].'", "required": 1, "icon-after": "'.currency().'" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			<span class="pull-right" ><b>'.tr('Totale utilizzato').':</b> '.moneyFormat($record['totale']).'</span>
		</div>
		<div class="clearfix">&nbsp;</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
            <a class="btn btn-danger ask" data-backto="record-edit" data-op="delete" data-id_record="'.$record['id'].'" data-id_plugin="'.$id_plugin.'" data-id_parent="'.$id_parent.'">
                <i class="fa fa-trash"></i> '.tr('Elimina').'
            </a>

			<button type="submit" class="btn btn-primary pull-right">
			    <i class="fa fa-edit"></i> '.tr('Modifica').'
			</button>
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
