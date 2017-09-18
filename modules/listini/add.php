<?php

include_once __DIR__.'/../../core.php';

?><form action="editor.php?id_module=$id_module$" method="post">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "number", "label": "<?php echo tr('Rincaro/sconto'); ?>", "name": "prc_guadagno", "required": 1, "value": "0", "icon-after": "%", "help": "<?php echo tr('Il valore positivo indica uno sconto').'. '.tr('Per applicare una percentuale di rincaro inserire un valore negativo').'.'; ?>" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>
