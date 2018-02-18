<?php

include_once __DIR__.'/../../core.php';

$id_original = filter('id_original');

if (isset($id_record)) {
    include __DIR__.'/init.php';
}

?><form action="editor.php?id_module=$id_module$<?php
if (isset($id_record)) {
    echo '&id_record='.$id_record;
}
?>" method="post">
	<input type="hidden" name="backto" value="record-edit">

<?php
if (!isset($id_original)) {
    ?>
	<input type="hidden" name="op" value="add">

	<div class="row">
		<div class="col-xs-12 col-md-12">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1 ]}
		</div>
	</div>
<?php
} else {
        ?>
	<input type="hidden" name="op" value="row">
	<input type="hidden" name="id_original" value="<?php echo $id_original; ?>">

	<div class="row">
        <div class="col-xs-12 col-md-8">
            {[ "type": "text", "label": "<?php echo  tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$", "extra": "" ]}
        </div>

        <div class="col-xs-12 col-md-4">
            {[ "type": "text", "label": "<?php echo  tr('Colore'); ?>", "name": "colore", "class": "colorpicker text-center", "value": "$colore$", "extra": "maxlength=\"7\"", "icon-after": "<div class=\"img-circle square\"></div>" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-md-12">
            {[ "type": "textarea", "label": "<?php echo  tr('Nota'); ?>", "name": "nota", "value": "$nota$" ]}
        </div>
    </div>

	<script>
		$(document).ready( function(){
			$('.colorpicker').colorpicker().on('changeColor', function(){
				$('#colore').parent().find('.square').css('background', $('#colore').val());
			});

			$('#colore').parent().find('.square').css('background', $('#colore').val());
		});
	</script>
<?php
    }
?>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
	<?php
if (isset($id_record)) {
    ?>
			<button type="submit" class="btn btn-success"><i class="fa fa-save"></i> <?php echo tr('Salva'); ?></button>
<?php
} else {
        ?>
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
<?php
    }
?>
		</div>
	</div>
</form>
