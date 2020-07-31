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
				<div class="col-md-9">
					{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Colore'); ?>", "name": "colore", "required": 1, "class": "colorpicker text-center", "value": "$colore$", "extra": "maxlength='7'", "icon-after": "<div class='img-circle square'></div>" ]}
				</div>

			</div>
		</div>
	</div>

</form>

<?php
$righe = $dbo->fetchNum('SELECT idanagrafica FROM an_anagrafiche WHERE idrelazione='.prepare($id_record));

if (!empty($righe)) {
    echo '
<div class="alert alert-danger">
    '.tr('Ci sono _NUM_ anagrafiche collegate', [
        '_NUM_' => count($righe),
    ]).'.
</div>';
} else {
    ?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<?php
}
?>


<script>
	$(document).ready( function() {
		$('.colorpicker').colorpicker().on('changeColor', function() {
			$('#colore').parent().find('.square').css( 'background', $('#colore').val() );
		});
		$('#colore').parent().find('.square').css( 'background', $('#colore').val() );
	});
</script>
