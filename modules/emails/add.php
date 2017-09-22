<?php

include_once __DIR__.'/../../core.php';

?><form action="editor.php?id_module=$id_module$" method="post">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

    <div class="row">
		<div class="col-xs-12 col-md-12">
			{[ "type": "text", "label": "<?php echo tr('Nome') ?>", "name": "name", "required": 1 ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Modulo del template') ?>", "name": "module", "values": "query=SELECT id, title AS descrizione FROM zz_modules WHERE enabled = 1" ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Oggetto') ?>", "name": "subject" ]}
		</div>
    </div>

	<!-- PULSANTI -->
	<div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
        </div>
    </div>
</form>
