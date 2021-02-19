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

$id_original = filter('id_original');

if (isset($id_record)) {
    include __DIR__.'/init.php';
}

?><form action="" method="post" id="add-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="add">

	<div class="row">
        <div class="col-md-8">
            {[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "text", "label": "<?php echo tr('Colore'); ?>", "name": "colore", "id": "colore_", "class": "colorpicker text-center", "value": "$colore$", "extra": "maxlength=\"7\"", "icon-after": "<div class=\"img-circle square\"></div>" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "<?php echo tr('Nota'); ?>", "name": "nota", "value": "$nota$" ]}
        </div>
    </div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>

<script>
		$(document).ready( function() {
			$('.colorpicker').colorpicker().on('changeColor', function() {
				$('#modals > div #colore_').parent().find('.square').css('background', $('#modals > div #colore_').val());
			});

			$('#modals > div #colore_').parent().find('.square').css('background', $('#modals > div #colore_').val());
		});
</script>
