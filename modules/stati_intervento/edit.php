<?php
include_once __DIR__.'/../../core.php';
?>

<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<div class="row">
		<div class="col-md-2">
			{[ "type": "span", "label": "<?php echo tr('Codice'); ?>", "name": "idstatointervento", "value": "$idstatointervento$" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$", "extra": "<?php echo $attr; ?>" ]}
		</div>

		<div class="col-md-2">
			{[ "type": "text", "label": "<?php echo tr('Colore'); ?>", "name": "colore", "required": 1, "class": "colorpicker text-center", "value": "$colore$", "extra": "maxlength='7'", "icon-after": "<div class='img-circle square'></div>" ]}
		</div>
	</div>
</form>

<?php
// Record eliminabile solo se permesso
if ($records[0]['can_delete']) {
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
	});
</script>
