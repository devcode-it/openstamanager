<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-12">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": "1" ]}
		</div>
	</div>
	
	<div class="row">
		<div class="col-md-8">
			{[ "type": "text", "label": "<?php echo tr('IBAN'); ?>", "name": "iban", "required": "1", "class": "alphanumeric-mask", "maxlength": 32, "value": "$iban$" ]}
		</div>
		<div class="col-md-4">
			{[ "type": "text", "label": "<?php echo tr('BIC'); ?>", "name": "bic", "class": "alphanumeric-mask", "maxlength": 11, "value": "$bic$" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>
