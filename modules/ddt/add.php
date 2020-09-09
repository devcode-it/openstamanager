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

$module = Modules::get($id_module);

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';

    $id_tipoddt = $dbo->fetchOne("SELECT id FROM dt_tipiddt WHERE descrizione='Ddt in uscita'")['id'];

    $tipo_anagrafica = tr('Cliente');
    $label = tr('Destinatario');
} else {
    $dir = 'uscita';

    $id_tipoddt = $dbo->fetchOne("SELECT id FROM dt_tipiddt WHERE descrizione='Ddt in entrata'")['id'];

    $tipo_anagrafica = tr('Fornitore');
    $label = tr('Mittente');
}

$id_anagrafica = !empty(get('idanagrafica')) ? get('idanagrafica') : '';

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="dir" value="<?php echo $dir; ?>">

    <!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="">

	<div class="row">
		<div class="col-md-4">
			 {[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "required": 1, "value": "-now-" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo $label; ?>", "name": "idanagrafica", "id": "idanagrafica_add", "required": 1, "value": "<?php echo $id_anagrafica; ?>", "ajax-source": "clienti_fornitori", "icon-after": "add|<?php echo Modules::get('Anagrafiche')['id']; ?>|tipoanagrafica=<?php echo $tipo_anagrafica; ?>" ]}
		</div>

		<!-- il campo idtipoddt può essere anche rimosso -->
		<div class="col-md-4 hide">
			{[ "type": "select", "label": "<?php echo tr('Tipo ddt'); ?>", "name": "idtipoddt", "required": 1, "values": "query=SELECT id, descrizione FROM dt_tipiddt WHERE dir='<?php echo $dir; ?>'", "value": "<?php echo $id_tipoddt; ?>" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Causale trasporto'); ?>", "name": "idcausalet", "required": 1, "value": "$idcausalet$", "ajax-source": "causali", "icon-after": "add|<?php echo Modules::get('Causali')['id']; ?>|||<?php echo $block_edit ? 'disabled' : ''; ?>" ]}
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
//autosubmit se tutti i campi obbligatori sono valorizzati
$('#modals > div').on('shown.bs.modal', function () {
	if ($('#add-form').parsley().isValid()) {
		$("#add-form").submit();
	}
});
</script>
