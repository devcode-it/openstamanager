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

$id_anagrafica = filter('id_anagrafica');

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "<?php echo tr('Matricola'); ?>", "name": "matricola", "required": 1, "class": "text-center alphanumeric-mask", "maxlength": 25 ]}
		</div>

		<div class="col-md-8">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1 ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "id": "idanagrafica_impianto", "required": 1, "value": "<?php echo $id_anagrafica; ?>", "ajax-source": "clienti", "icon-after": "add|<?php echo Modules::get('Anagrafiche')['id']; ?>|tipoanagrafica=Cliente&readonly_tipo=1" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede", "value": "$idsede$", "ajax-source": "sedi", "select-options": <?php echo json_encode(['idanagrafica' => $id_anagrafica]); ?>, "placeholder": "Sede legale" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Tecnico assegnato'); ?>", "name": "idtecnico", "ajax-source": "tecnici" ]}
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
$(document).ready(function() {
	input('idanagrafica').change(function() {
        updateSelectOption("idanagrafica", $(this).val());
		session_set('superselect,idanagrafica', $(this).val(), 0);

        let value = !input('idanagrafica').get();

        input('idsede').setDisabled(value)
            .getElement().selectReset();
	});
});
</script>
