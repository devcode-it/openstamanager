<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "module_id", "values": "query=SELECT id, name as text FROM zz_modules WHERE enabled = 1" ]}
		</div>

        <div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Plugin'); ?>", "name": "plugin_id", "values": "query=SELECT id, name as text FROM zz_plugins WHERE enabled = 1" ]}
		</div>
    </div>

	<div class="row">
		<div class="col-md-12">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "name", "required": 1 ]}
		</div>
	</div>

    <div class="row">
		<div class="col-md-12">
			{[ "type": "textarea", "label": "<?php echo tr('Contenuto'); ?>", "name": "content", "required": 1, "value": "$content$" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>
