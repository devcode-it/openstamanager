<?php

include_once __DIR__.'/../../core.php';

?><form action="editor.php?id_module=$id_module$" method="post">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-xs-12 col-md-12">
			{[ "type": "text", "label": "<?php echo _('Descrizione') ?>", "name": "descrizione", "required": 1,  "value": "" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "number", "label": "<?php echo _('Percentuale') ?>", "name": "percentuale", "value": "", "icon-after": "<i class=\"fa fa-percent\"></i>" ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "number", "label": "<?php echo _('Indetraibile') ?>", "name": "indetraibile", "value": "", "icon-after": "<i class=\"fa fa-usd\"></i>" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo _('Aggiungi'); ?></button>
		</div>
	</div>
</form>
