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
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type":"text", "label":"'.tr('Nome').'", "name":"nome", "required":"1" ]}
		</div>

		<div class="col-md-3">
			{[ "type":"date", "label":"'.tr('Data attivazione').'", "name":"data_attivazione", "required":"1" ]}
		</div>

		<div class="col-md-3">
			{[ "type":"date", "label":"'.tr('Data scadenza default').'", "name":"data_scadenza_predefinita" ]}
		</div>
	</div>
	
	<div class="row">
		<div class="col-md-6">
			{[ "type":"checkbox", "label":"'.tr('Sempre visibile').'", "name":"is_sempre_visibile", "help": "'.tr('Se impostato il valore sarà sempre visibile sull\'articolo se il listino è attivo e la data di scadenza è ancora valida').'" ]}
		</div>

		<div class="col-md-6">
			{[ "type":"checkbox", "label":"'.tr('Attivo').'", "name":"attivo", "value": "1" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			{[ "type":"textarea", "label":"'.tr('Note').'", "name":"note" ]}
		</div>
	</div>


	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';
