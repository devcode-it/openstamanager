<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">

		<div class="col-md-6">
				{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "name", "required": 1, "class": "", "value": "$name$", "extra": "" ]}
		</div>
		
		<div class="col-md-6">
				{[ "type": "text", "label": "<?php echo tr('Maschera'); ?>", "name": "pattern", "required": 1, "class": "alphanumeric-mask", "value": "$pattern$", "maxlength": 25, "placeholder":"####/YY", "extra": "" ]}
		</div>
		
		
	</div>
	
	<div class="row">
		
		<div class="col-md-6">
				{[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "id_module_", "required": 1, "class": "", "values": "list=\"14\": \"Fatture di vendita\",  \"15\": \"Fatture di acquisto\"", "value": "$id_module$", "extra": "" ]}
		</div>
		
		<div class="col-md-6">
				{[ "type": "checkbox", "label": "<?php echo tr('Predefinito'); ?>", "name": "predefined", "value": "0", "help": "<?php echo tr('Seleziona per rendere il segmento predefinito.'); ?>", "placeholder": "<?php echo tr('Segmento predefinito'); ?>" ]}
		</div>

	</div>

	<div class="row">

		<div class="col-md-12">
				{[ "type": "textarea", "label": "Note", "name": "note", "required": 0, "class": "", "value": "", "extra": "" ]}
		</div>

	</div>


	<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> Inserisci</button>
	<div class="clearfix"></div>
</form>
<!--script>
	$(document).ready( function(){
		start_jquerychosen();
	});
</script-->
