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

$id_record = get('id_record');
$id_parent = get('id_parent');
$id_plugin = get('id_plugin');
$id_module = get('id_module');

$barcode = $dbo->table('mg_articoli_barcode')->where('id',$id_record)->first();

echo '
<form action="" method="post" role="form" id="form_sedi">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="updatebarcode">

	<div class="row">
		<div class="col-md-12">
            <button type="button" class="btn btn-default btn-xs tip pull-right" id="generaBarcode"><i class="fa fa-refresh"></i> '.tr('Genera').'</button>
			{[ "type": "text", "label": "'.tr('Barcode').'", "name": "barcode", "required": 1, "value": "'.$barcode->barcode.'" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
            <button type="button" class="btn btn-danger '.$disabled.'" onclick="rimuoviBarcode(this)">
                <i class="fa fa-trash"></i> '.tr('Elimina').'
            </button>

            <a type="button" class="btn btn-info" href="'.$rootdir.'/pdfgen.php?id_print='.Prints::getPrints()['Barcode'].'&id_record='.$id_parent.'&idbarcode='.$id_record.'" target="_blank"><i class="fa fa-print"></i> '.tr('Stampa barcode').'</a>
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-edit"></i> '.tr('Modifica').'</button>
		</div>
	</div>
</form>';

echo '
<script>
function rimuoviBarcode(button) {
    let hash = window.location.href.split("#")[1];

    confirmDelete(button).then(function () {
        redirect(globals.rootdir + "/editor.php", {
            backto: "record-edit",
            hash: hash,
            op: "deletebarcode",
            id: "'.$id_record.'",
            id_plugin: "'.$id_plugin.'",
            id_module: "'.$id_module.'",
            id_parent: "'.$id_parent.'",
        });
    }).catch(swal.noop);
}

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