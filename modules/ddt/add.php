<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';

    $rs = $dbo->fetchArray("SELECT id FROM dt_tipiddt WHERE descrizione='Ddt di vendita' LIMIT 0,1");
    $id_tipoddt = $rs[0]['id'];

    $tipo_anagrafica = tr('Cliente');
} else {
    $dir = 'uscita';

    $rs = $dbo->fetchArray("SELECT id FROM dt_tipiddt WHERE descrizione='Ddt di acquisto' LIMIT 0,1");
    $id_tipoddt = $rs[0]['id'];

    $tipo_anagrafica = tr('Fornitore');
}

?><form action="editor.php?id_module=$id_module$" method="post">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="dir" value="<?php echo $dir; ?>">

	<div class="row">
		<div class="col-md-4">
			 {[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "required": 1, "value": "-now-" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo $tipo_anagrafica; ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='<?php echo $tipo_anagrafica; ?>' AND deleted=0 ORDER BY ragione_sociale", "value": "<?php echo $idanagrafica; ?>" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Tipo ddt'); ?>", "name": "idtipoddt", "required": 1, "values": "query=SELECT id, descrizione FROM dt_tipiddt WHERE dir='<?php echo $dir; ?>'", "value": "<?php echo $id_tipoddt; ?>" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>
