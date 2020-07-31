<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-3">
			{[ "type": "text", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "maxlength": 10, "class": "alphanumeric-mask", "required": 1 ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1 ]}
		</div>

		<div class="col-md-3">
			{[ "type": "text", "label": "<?php echo tr('Colore'); ?>", "name": "colore", "id": "colore_", "required": 1, "class": "colorpicker text-center", "value": "#ffffff", "extra": "maxlength='7'", "icon-after": "<div class='img-circle square'></div>" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>

<script>
	$(document).ready( function() {
		$('.colorpicker').colorpicker().on('changeColor', function() {
			$('#modals > div #colore_').parent().find('.square').css( 'background', $('#modals > div #colore_').val() );
		});

		$('#modals > div #colore_').parent().find('.square').css( 'background', $('#modals > div #colore_').val() );
	});
</script>
