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
        <div class="col-md-4">
            {[ "type": "checkbox", "label": "<?php echo tr('Esente'); ?>", "name": "esente", "id": "esente-add", "value": "$esente$" ]}
        </div>

		<div class="col-md-4">
			{[ "type": "number", "label": "<?php echo tr('Percentuale'); ?>", "name": "percentuale", "id": "percentuale-add", "icon-after": "<i class=\"fa fa-percent\"></i>" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "number", "label": "<?php echo tr('Indetraibile'); ?>", "name": "indetraibile", "icon-after": "<i class=\"fa fa-percent\"></i>" ]}
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
$(document).ready(function(){
    $('#esente-add').change(function(){
        var checkbox = $(this).parent().find('[type=hidden]');

        if (checkbox.val() == 1) {
            $("#percentuale-add").prop("disabled", true);
        } else {
            $("#percentuale-add").prop("disabled", false);
        }
    });
});
</script>
