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

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-12">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1 ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Causale ritenuta'); ?>", "name": "causale", "values": "query=SELECT codice AS id, CONCAT_WS(' - ', codice, descrizione) AS descrizione FROM fe_causali_pagamento_ritenuta", "required": "1" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Tipo ritenuta'); ?>", "name": "tipologia", "values": "query=SELECT codice AS id, CONCAT_WS(' - ', codice, descrizione) AS descrizione FROM fe_tipi_ritenuta", "required": "1" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "number", "label": "<?php echo tr('Percentuale'); ?>", "name": "percentuale", "min-value": "1", "max-value": "100","icon-after": "<i class=\"fa fa-percent\"></i>", "value": "100" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "number", "label": "<?php echo tr('Percentuale imponibile'); ?>", "name": "percentuale_imponibile", "min-value": "1", "max-value": "100", "help": "<?php echo tr('Percentuale imponibile sui cui applicare il calcolo della ritenuta'); ?>", "icon-after": "<i class=\"fa fa-percent\"></i>", "value": "100" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="modal-footer">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>
