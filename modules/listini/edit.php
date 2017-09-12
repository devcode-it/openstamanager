<?php
include_once __DIR__.'/../../core.php';

?><form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI ARTICOLO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr("Listino"); ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>


			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr("Nome"); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
				</div>
				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr("Guadagno/sconto"); ?>", "name": "prc_guadagno", "required": 1, "value": "$prc_guadagno$", "icon-after": "%" ]}
				</div>

			</div>
			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr("Note"); ?>", "name": "note", "value": "$note$" ]}
				</div>
			</div>


		</div>
	</div>

</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
