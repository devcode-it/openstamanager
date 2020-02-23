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
		<div class="col-md-2">
            {[ "type": "checkbox", "label": "<?php echo tr('Completato'); ?>", "name": "is_completato", "value": "$is_completato$", "help": "<?php echo tr('I preventivi che si trovano in questo stato verranno considerati come completati'); ?>", "placeholder": "<?php echo tr('Completato'); ?>", "extra": "" ]}
		</div>
		
		 <div class="col-md-2">
            {[ "type": "checkbox", "label": "<?php echo tr('Pianificabile'); ?>", "name": "is_pianificabile", "value": "$is_pianificabile$", "help": "<?php echo tr('I preventivi che si trovano in questo stato verranno considerati come pianificabili'); ?>", "placeholder": "<?php echo tr('Pianificabile'); ?>", "extra": "" ]}
		</div>
		
		 <div class="col-md-2">
            {[ "type": "checkbox", "label": "<?php echo tr('Fatturabile'); ?>", "name": "is_fatturabile", "value": "$is_fatturabile$", "help": "<?php echo tr('I preventivi che si trovano in questo stato verranno considerati come fatturabili'); ?>", "placeholder": "<?php echo tr('Fatturabile'); ?>", "extra": "" ]}
		</div>
		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Icona'); ?>", "name": "icona", "required": 1, "class": "text-center", "value": "fa ", "extra": "" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>