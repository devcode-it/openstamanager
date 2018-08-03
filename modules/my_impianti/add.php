<?php

include_once __DIR__.'/../../core.php';

//unset($_SESSION['superselect']['idanagrafica']);
//unset($_SESSION['superselect']['idsede']);


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
			{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Cliente' AND deleted_at IS NULL ORDER BY ragione_sociale", "value": "<?php echo $idanagrafica; ?>", "ajax-source": "clienti" ]}
		</div>
		
		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede", "values": "query=SELECT 0 AS id, 'Sede legale' AS descrizione UNION SELECT id, CONCAT_WS( ' - ', nomesede, citta ) AS descrizione FROM an_sedi WHERE idanagrafica='$idanagrafica$'", "value": "$idsede$", "required": "1", "ajax-source": "sedi"  ]}
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
$(document).ready(function(){

	$('#idanagrafica').change( function(){
		
		session_set('superselect,idanagrafica', $(this).val(), 0);

        var value = !$(this).val() ? true : false;

		$("#idsede").prop("disabled", value);
		$("#idsede").selectReset();
		
	});
	
	$('#idsede').change( function(){
		//session_set('superselect,idsede', $(this).val(), 0);
	});
		
});
</script>