<?php

include_once __DIR__.'/../../core.php';

$id_original = filter('id_original');

if (isset($id_record)) {
    include __DIR__.'/init.php';
}

?><form action="" method="post" id="add-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="add">

	<div class="row">
        <div class="col-md-8">
            {[ "type": "text", "label": "<?php echo  tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "text", "label": "<?php echo  tr('Colore'); ?>", "name": "colore", "id": "colore_", "class": "colorpicker text-center", "value": "$colore$", "extra": "maxlength=\"7\"", "icon-after": "<div class=\"img-circle square\"></div>" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "<?php echo  tr('Nota'); ?>", "name": "nota", "value": "$nota$" ]}
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
				$('#modals > div #colore_').parent().find('.square').css('background', $('#modals > div #colore_').val());
			});

			$('#modals > div #colore_').parent().find('.square').css('background', $('#modals > div #colore_').val());
		});
</script>
