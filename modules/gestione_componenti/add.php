<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-list">

	<div class="row">
		<div class="col-md-12">
			{[ "type": "text", "label": "<?php echo tr('Nome file'); ?>", "name": "nomefile", "required": 1 ]}
		</div>


		<div class="col-md-12">
			<a href="#" class="pull-right" id="default">[Default]</a>
			{[ "type": "textarea", "label": "<?php echo tr('Contenuto'); ?>", "name": "contenuto", "id": "contenuto_add", "required": 1, "class": "autosize", "extra": "rows='10'" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>

<script type="text/javascript">
	$(document).ready( function() {
		$('#modals > div #default').click( function() {
			if (confirm ('Generare un componente di default?')){

				var ini = '[Nome]\ntipo = span\nvalore = "Componente di esempio"\n\n[Marca]\ntipo = input\nvalore =\n\n[Tipo]\ntipo = select\nvalore =\nopzioni = "Tipo 1", "Tipo 2"\n\n[Data di installazione]\ntipo = date\nvalore =\n\n[Note]\ntipo = textarea\nvalore =\n';

				$("#modals > div #contenuto_add").val(ini);
			}
		});
	});
</script>
