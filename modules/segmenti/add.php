<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
            {[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "name", "required": 1, "value": "$name$" ]}
		</div>

		<div class="col-md-6">
            {[ "type": "text", "label": "<?php echo tr('Maschera'); ?>", "name": "pattern", "class": "alphanumeric-mask", "value": "$pattern$", "maxlength": 25, "placeholder":"####/YYYY" ]}
		</div>
	</div>

	<div class="row">

		<div class="col-md-6">
            {[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "module", "required": 1, "values": "query=SELECT id, IF(title!='', title, name) AS descrizione FROM zz_modules  WHERE enabled = 1 AND options != 'custom' ORDER BY descrizione ASC", "value": "" ]}
		</div>

		<div class="col-md-6">
            {[ "type": "checkbox", "label": "<?php echo tr('Predefinito'); ?>", "name": "predefined", "value": "0", "help": "<?php echo tr('Seleziona per rendere il segmento predefinito.'); ?>", "placeholder": "<?php echo tr('Segmento predefinito'); ?>" ]}
		</div>

	</div>

	<div class="row">

		<div class="col-md-12">
            {[ "type": "textarea", "label": "Note", "name": "note" ]}
		</div>

	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>
