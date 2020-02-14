<?php
include_once __DIR__.'/../../core.php';

if ($record['can_delete']) {
    $attr = '';
} else {
    $attr = 'readonly';
    echo '<div class="alert alert-warning">'.tr('Alcune impostazioni non possono essere modificate per questo stato attività.').'</div>';
}
?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<div class="row">
		<div class="col-md-2">
			{[ "type": "text", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "value": "$codice$", "extra": "<?php echo $attr; ?>" ]}
		</div>

		<div class="col-md-5">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
		</div>

        <div class="col-md-2">
            {[ "type": "checkbox", "label": "<?php echo tr('Stato completato?'); ?>", "name": "completato", "value": "$completato$", "help": "<?php echo tr('Le attività che si trovano in questo stato verranno considerate come completate.'); ?>", "placeholder": "<?php echo tr('Completato'); ?>", "extra": "<?php echo $attr; ?>" ]}
		</div>

		<div class="col-md-3">
			{[ "type": "text", "label": "<?php echo tr('Colore'); ?>", "name": "colore", "required": 1, "class": "colorpicker text-center", "value": "$colore$", "extra": "maxlength='7'", "icon-after": "<div class='img-circle square'></div>" ]}
		</div>
	</div>

    <div class="row">
		<div class="col-md-2">
			{[ "type": "checkbox", "label": "<?php echo tr('Abilita notifiche'); ?>", "name": "notifica", "help": "<?php echo tr('Quando l\'attività passa in questo stato viene inviata una notifica ai destinatari designati.'); ?>.", "value": "$notifica$" ]}
		</div>

		<div class="col-md-5">
			{[ "type": "select", "label": "<?php echo tr('Template email'); ?>", "name": "email", "value": "$id_email$", "values": "query=SELECT id, name AS descrizione FROM em_templates WHERE id_module = <?php echo Modules::get('Interventi')['id']; ?> AND deleted_at IS NULL", "disabled": <?php echo intval(empty($record['notifica'])); ?> ]}
		</div>

        <div class="col-md-5">
            {[ "type": "text", "label": "<?php echo tr('Destinatari'); ?>", "name": "destinatari", "value": "$destinatari$", "disabled": <?php echo intval(empty($record['notifica'])); ?> ]}
		</div>
	</div>
</form>

<?php
// Record eliminabile solo se permesso
if ($record['can_delete']) {
    ?>
        <a class="btn btn-danger ask" data-backto="record-list">
            <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
        </a>
<?php
}
?>
<script>
	$(document).ready( function(){
		$('.colorpicker').colorpicker().on('changeColor', function(){
			$('#colore').parent().find('.square').css( 'background', $('#colore').val() );
		});
		$('#colore').parent().find('.square').css( 'background', $('#colore').val() );
	

	$("#notifica").change( function(){
		if ($(this).is(":checked")) {
			$("#email").attr("required", true);
			$("#email").attr("disabled", false);
			$("#destinatari").attr("required", true);
			$("#destinatari").attr("disabled", false);
		}else{
			$("#email").attr("required", false);
			$("#email").attr("disabled", true);
			$("#destinatari").attr("required", false);
			$("#destinatari").attr("disabled", true);
		}
	});

	});
</script>
