<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-12">
					{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
				</div>
			</div>

			<div class="row">
                <div class="col-md-4">
					{[ "type": "checkbox", "label": "<?php echo tr('Esente'); ?>", "name": "esente", "id": "esente-edit", "value": "$esente$"]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Percentuale'); ?>", "name": "percentuale", "id": "percentuale-edit", "value": "$percentuale$", "icon-after": "<i class=\"fa fa-percent\"></i>", "disabled": <?php echo intval($record['esente']); ?> ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Indetraibile'); ?>", "name": "indetraibile", "value": "$indetraibile$", "icon-after": "<i class=\"fa fa-percent\"></i>" ]}
				</div>
			</div>

            <div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "value": "$codice$" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "select", "label": "<?php echo tr('Codice Natura (Fatturazione Elettronica)'); ?>", "name": "codice_natura_fe", "value": "$codice_natura_fe$", "values": "query=SELECT codice as id, CONCAT(codice, ' - ', descrizione) AS descrizione FROM fe_natura" ]}
				</div>
			</div>

            <div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Dicitura fissa in fattura'); ?>", "name": "dicitura", "value": "$dicitura$" ]}
				</div>
			</div>
		</div>
	</div>

</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<script>
$(document).ready(function(){
    $('#esente-edit').change(function(){
        var checkbox = $(this).parent().find('[type=hidden]');

        if (checkbox.val() == 1) {
            $("#percentuale-edit").prop("disabled", true);
        } else {
            $("#percentuale-edit").prop("disabled", false);
        }
    });
});
</script>
