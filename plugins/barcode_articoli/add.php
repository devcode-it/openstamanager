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
	<input type="hidden" name="op" value="addbarcode">

    <!-- Fix creazione da Articolo -->
    <input type="hidden" name="id_record" value="0">

	<div class="row">
		<div class="col-md-12">
            <button type="button" class="btn btn-default btn-xs tip pull-right" id="generaBarcode"><i class="fa fa-refresh"></i> '.tr('Genera').'</button>
			{[ "type": "text", "label": "'.tr('Barcode').'", "name": "barcode", "validation": "barcode|'.$id_module.'|0", "class": "text-center", "value": "" ]}
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
<script>
function generaBarcode() {
    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "generate-barcode"
        },
        success: function(response) {
            response = JSON.parse(response);
            let input = $("#barcode");
            input.val(response.barcode);
        },
        error: function(xhr, status, error) {
        }
    });
}

$("#generaBarcode").click( function() {
	generaBarcode();
});
</script>';
