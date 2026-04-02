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
<form action="" method="post" role="form" id="add_componente_form">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="add">

	<div class="row">
		<div class="col-md-12">
            {["type": "select", "label": "'.tr('Articolo').'", "name": "id_articolo", "id": "id_articolo_componente", "ajax-source": "articoli", "value": "", "required": 1, "select-options": {"permetti_movimento_a_zero": 1, "ricerca_codici_fornitore": 1} ]}
		</div>
	</div>

	<div class="row" id="row_serial" style="display: none;">
		<div class="col-md-12">
            {["type": "select", "label": "'.tr('Serial').'", "name": "serial", "id": "serial_componente", "ajax-source": "serial-articolo", "value": "" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="modal-footer">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary">
			    <i class="fa fa-plus"></i> '.tr('Aggiungi').'
			</button>
		</div>
	</div>
</form>

<script>
    $("#id_articolo_componente").on("change", function() {
        var id_articolo = $(this).val();
        
        if (id_articolo) {
            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "POST",
                dataType: "json",
                data: {
                    id_module: "'.$id_module.'",
                    id_record: "'.$id_record.'",
                    id_plugin: "'.$id_plugin.'",
                    op: "check_serial",
                    id_articolo: id_articolo
                },
                success: function(response) {
                    if (response.abilita_serial) {
                        $("#row_serial").show();
                        $("#serial_componente").enableSelect2();
                        $("#serial_componente").select2("data", {idarticolo: id_articolo});
                    } else {
                        $("#row_serial").hide();
                        $("#serial_componente").val("").change();
                    }
                }
            });
        } else {
            $("#row_serial").hide();
            $("#serial_componente").val("").change();
        }
    });
</script>';
