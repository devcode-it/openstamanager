<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record ?>">

	<div class="pull-right">
		<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
	</div>
	<div class="clearfix"></div>

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "<?php echo tr('Categoria'); ?>", "name": "categoria", "required": 1, "value": "$categoria$" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
		</div>
	</div>
</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina') ?>
</a>
