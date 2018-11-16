<?php

include_once __DIR__.'/../../core.php';

if (!$record['default']) {
    $attr = '';
} else {
    $attr = 'readonly';
    echo '<div class="alert alert-warning">'.tr('Alcune impostazioni non possono essere modificate.').'</div>';
}

$esigibilita = [
    [
        'id' => 'I',
        'text' => tr('IVA ad esigibilità immediata'),
    ],
    [
        'id' => 'D',
        'text' => tr('IVA ad esigibilità differita'),
    ],
    [
        'id' => 'S',
        'text' => tr('Scissione dei pagamenti'),
    ],
];

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
					{[ "type": "checkbox", "label": "<?php echo tr('Esente'); ?>", "name": "esente", "id": "esente-edit", "value": "$esente$", "extra": "<?php echo $attr; ?>"]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Percentuale'); ?>", "name": "percentuale", "id": "percentuale-edit", "value": "$percentuale$", "icon-after": "<i class=\"fa fa-percent\"></i>", "disabled": <?php echo intval($record['esente']); ?>, "extra": "<?php echo $attr; ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Indetraibile'); ?>", "name": "indetraibile", "value": "$indetraibile$", "icon-after": "<i class=\"fa fa-percent\"></i>", "extra": "<?php echo $attr; ?>" ]}
				</div>
			</div>

            <div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "value": "$codice$", "class":"alphanumeric-mask", "maxlength": 10, "extra": "<?php echo $attr; ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Codice Natura (Fatturazione Elettronica)'); ?>", "name": "codice_natura_fe", "value": "$codice_natura_fe$", "required": <?php echo intval($record['esente']); ?>, "disabled": <?php echo intval(!$record['esente']); ?>, "values": "query=SELECT codice as id, CONCAT(codice, ' - ', descrizione) AS descrizione FROM fe_natura", "extra": "<?php echo $attr; ?>" ]}
				</div>

                <div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Esigibilità (Fatturazione Elettronica)'); ?>", "name": "esigibilita", "value": "$esigibilita$", "values": <?php echo json_encode($esigibilita); ?>, "required": 1, "extra": "<?php echo $attr; ?>" ]}
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

<?php
// Record eliminabile solo se permesso
if (!$record['default']) {
    ?>
        <a class="btn btn-danger ask" data-backto="record-list">
            <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
        </a>
<?php
}
?>

<script>
$(document).ready(function(){
    $('#esente-edit').change(function(){
        var checkbox = $(this).parent().find('[type=hidden]');

        if (checkbox.val() == 1) {
            $("#percentuale-edit").prop("disabled", true);
            $("#codice_natura_fe").prop("required", true);
            $("#codice_natura_fe").prop("disabled", false);
        } else {
            $("#percentuale-edit").prop("disabled", false);
            $("#codice_natura_fe").prop("required", false);
            $("#codice_natura_fe").val("").change();
            $("#codice_natura_fe").prop("disabled", true);
        }
    });
});
</script>
