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

// Presenza di documenti associati
if ($record['doc_associati'] > 0) {
    echo '
<div class="alert alert-warning">'.tr('Non puoi eliminare questa ritenuta.').' '.tr('Ci sono _NUM_ documenti associati.', [
    '_NUM_' => $record['doc_associati'],
]).'</div>';
}

?>

<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-12">
					{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "number", "label": "<?php echo tr('Percentuale'); ?>", "name": "percentuale", "min-value": "1", "max-value": "100", "value": "$percentuale$", "icon-after": "<i class=\"fa fa-percent\"></i>" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "number", "label": "<?php echo tr('Percentuale imponibile'); ?>", "name": "percentuale_imponibile", "value": "$percentuale_imponibile$", "min-value": "1", "max-value": "100", "help": "<?php echo tr('Percentuale imponibile sui cui applicare il calcolo della ritenuta'); ?>", "icon-after": "<i class=\"fa fa-percent\"></i>" ]}
				</div>
			</div>
		</div>
	</div>

</form>

<?php

// Presenza di documenti associati
if ($record['doc_associati'] == 0) {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
}
