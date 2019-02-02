<?php

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
			{[ "type": "number", "label": "<?php echo tr('Percentuale'); ?>", "name": "percentuale", "icon-after": "<i class=\"fa fa-percent\"></i>" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "number", "label": "<?php echo tr('Percentuale imponibile'); ?>", "name": "percentuale_imponibile", "help": "<?php echo tr('Percentuale imponibile sui cui applicare il calcolo della ritenuta'); ?>", "icon-after": "<i class=\"fa fa-percent\"></i>" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>