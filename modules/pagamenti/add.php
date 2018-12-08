<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1 ]}
		</div>
		
		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Codice Modalità (Fatturazione Elettronica)'); ?>", "name": "codice_modalita_pagamento_fe", "value": "", "values": "query=SELECT codice as id, CONCAT(codice, ' - ', descrizione) AS descrizione FROM fe_modalita_pagamento", "required": 1 ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>
