<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-8">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1 ]}
		</div>

		<div class="col-md-4">
			{[ "type": "number", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "required": 1, "decimals":0, "min-value":"0", "max-value":"999", "maxlength": 3 ]}
		</div>
	</div>

	<div class="row">
        <div class="col-md-6">
            {[ "type": "checkbox", "label": "<?php echo tr('Esente'); ?>", "name": "esente", "id": "esente-add", "value": "$esente$" ]}
        </div>

		<div class="col-md-6">
			{[ "type": "number", "label": "<?php echo tr('Percentuale'); ?>", "name": "percentuale", "id": "percentuale-add", "icon-after": "<i class=\"fa fa-percent\"></i>", "max-value": "100" ]}
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			{[ "type": "number", "label": "<?php echo tr('Indetraibile'); ?>", "name": "indetraibile", "icon-after": "<i class=\"fa fa-percent\"></i>", "max-value": "100" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Codice Natura (Fatturazione Elettronica)'); ?>", "name": "codice_natura_fe", "values": "query=SELECT codice as id, CONCAT(codice, ' - ', descrizione) AS descrizione FROM fe_natura", "extra": "disabled" ]}
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
    $('#modals > div #esente-add').change(function(){
        var checkbox = $(this).parent().find('[type=hidden]');

        if (checkbox.val() == 1) {
            $("#modals > div #percentuale-add").prop("disabled", true);
            $("#modals > div #codice_natura_fe").prop("required", true);
            $("#modals > div #codice_natura_fe").prop("disabled", false);
        } else {
            $("#modals > div #percentuale-add").prop("disabled", false);
            $("#modals > div #codice_natura_fe").prop("required", false);
            $("#modals > div #codice_natura_fe").val("").change();
            $("#modals > div #codice_natura_fe").prop("disabled", true);
        }
    });
});
</script>
