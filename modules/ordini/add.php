<?php

include_once __DIR__.'/../../core.php';

$module = Modules::getModule($id_module);

if ($module['name'] == 'Ordini cliente') {
    $dir = 'entrata';

    $tipo_anagrafica = _('Cliente');
    $ajax = 'clienti';
} else {
    $dir = 'uscita';

    $tipo_anagrafica = _('Fornitore');
    $ajax = 'fornitori';
}

?><form action="editor.php?id_module=$id_module$" method="post">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="dir" value="<?php echo $dir ?>">

	<div class="row">
		<div class="col-md-6">
            {[ "type": "date", "label": "<?php echo _('Data'); ?>", "name": "data", "required": 1, "value": "-now-" ]}
		</div>

		<div class="col-md-6">
            {[ "type": "select", "label": "<?php echo $tipo_anagrafica ?>", "name": "idanagrafica", "required": 1, "value": "", "value": "<?php echo $idanagrafica ?>", "ajax-source": "<?php echo $ajax ?>" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo _('Aggiungi'); ?></button>
		</div>
	</div>
</form>
