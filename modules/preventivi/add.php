<?php

include_once __DIR__.'/../../core.php';

$id_anagrafica = !empty(get('idanagrafica')) ? get('idanagrafica') : $user['idanagrafica'];

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

    <!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "date", "label": "<?php echo tr('Data bozza'); ?>", "name": "data_bozza", "value": "<?php echo '-now-'; ?>", "required": 1 ]}
        </div>
		<div class="col-md-6">
			 {[ "type": "text", "label": "<?php echo tr('Nome preventivo'); ?>", "name": "nome", "required": 1 ]}
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
				{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "value": "<?php echo $id_anagrafica; ?>", "ajax-source": "clienti", "icon-after": "add|<?php echo Modules::get('Anagrafiche')['id']; ?>|tipoanagrafica=Cliente&readonly_tipo=1" ]}
		</div>

		<div class="col-md-6">
				{[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede", "ajax-source": "sedi", "placeholder": "Sede legale" ]}
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Tipo di Attività'); ?>", "name": "idtipointervento", "required": 1, "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento" ]}
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
	$('#modals > div #idanagrafica').change(function() {
        updateSelectOption("idanagrafica", $(this).val());
        session_set('superselect,idanagrafica', $(this).val(), 0);
	});
</script>
