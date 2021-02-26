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

?><form action="" method="post" id="add-form" enctype="multipart/form-data">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "file", "label": "<?php echo tr('File'); ?>", "name": "file", "required": 1, "accept": ".csv" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "id_import", "required": 1, "values": "query=SELECT id, name AS text FROM zz_imports" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button id="example" type="button" class="btn btn-info hidden">
                <i class="fa fa-file"></i> <?php echo tr('Scarica esempio CSV'); ?>
            </button>

			<button type="submit" class="btn btn-primary">
                <i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?>
            </button>
		</div>
	</div>
</form>

<script>
    $("#id_import").change(function () {
        if ($(this).val()) {
            $("#example").removeClass("hidden");
        } else {
            $("#example").addClass("hidden");
        }
    });

    $("#example").click(function() {
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "post",
            data: {
                op: "example",
                id_module: globals.id_module,
                id_import: $('#id_import').val(),
            },
            success: function(data) {
                if (data) {
                    window.location = data;
                }

                $('#main_loading').fadeOut();
            }
        });
    });
</script>
