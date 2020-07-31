<?php

include_once __DIR__.'/../../core.php';

$source = get('source');
$idanagrafica = null;

if ($source == 'Attività') {
    $idanagrafica = $_SESSION['superselect']['idanagrafica'];
}

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="source" value="<?php echo $source; ?>">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "<?php echo tr('Matricola'); ?>", "name": "matricola", "required": 1, "class": "text-center alphanumeric-mask", "maxlength": 25 ]}
		</div>

		<div class="col-md-8">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1 ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Cliente' AND deleted_at IS NULL ORDER BY ragione_sociale", "value": "<?php echo $idanagrafica; ?>", "ajax-source": "clienti", "icon-after": "add|<?php echo Modules::get('Anagrafiche')['id']; ?>|tipoanagrafica=Cliente&readonly_tipo=1" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede", "value": "$idsede$", "ajax-source": "sedi", "placeholder": "Sede legale"  ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Tecnico'); ?>", "name": "idtecnico", "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Tecnico' AND deleted_at IS NULL ORDER BY ragione_sociale" ]}
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
	$('#modals > div #idanagrafica').change(function() {
        updateSelectOption("idanagrafica", $(this).val());
		session_set('superselect,idanagrafica', $(this).val(), 0);

        var value = !$(this).val();

		$("#modals > div #idsede").prop("disabled", value)
            .selectReset();
	});
});
</script>
