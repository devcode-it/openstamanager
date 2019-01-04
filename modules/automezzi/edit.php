<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- DATI ARTICOLO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Automezzo'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
				</div>
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Targa'); ?>", "name": "targa", "required": 1, "maxlength": 10, "class": "alphanumeric-mask", "value": "$targa$" ]}
				</div>

			</div>
			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "value": "$descrizione$" ]}
				</div>
			</div>
		</div>
	</div>
</form>

<!-- TECNICI + MAGAZZINO AUTOMEZZO -->
<div class="row">

	<!--TECNICI -->
	<div class="col-md-6">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<div class="row">
					<div class="col-md-12">
						<h3 class="panel-title"><?php echo tr('Tecnici responsabili automezzo'); ?></h3>
					</div>
				</div>
			</div>

			<div class="panel-body">
				<div class="row">
					<div class="col-md-12" >
						<form action="<?php echo $rootdir; ?>/editor.php?id_module=<?php echo Modules::get('Automezzi')['id']; ?>&id_record=<?php echo $id_record; ?>" id="updatetech-form" method="post" role="form">
							<input type="hidden" name="backto" value="record-edit">
							<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
							<input type="hidden" name="op" value="">

							<?php
                            include $docroot.'/modules/automezzi/row-list-tecnici.php';
                            ?>
						</form>

						<a href="javascript:;" class="btn btn-sm btn-success pull-right" title="Aggiorna date" onclick="$('#updatetech-form input[name=op]').val('savetech'); $('#updatetech-form').submit();"><i class="fa fa-edit"></i> <?php echo tr('Salva date'); ?></a>

						<div class="pull-left">
							<a class="btn btn-sm btn-primary" data-href="<?php echo $rootdir; ?>/modules/automezzi/add_tecnico.php?idautomezzo=<?php echo $id_record; ?>" data-toggle="modal" data-title="Aggiungi tecnico"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi tecnico'); ?></a><br>
						</div>
						<div class="clearfix"></div>
					</div>
				</div>
			</div>
		</div>
	</div>


	<!-- MAGAZZINO AUTOMEZZO -->
	<div class="col-md-6">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<div class="row">
					<div class="col-md-12">
						<h3 class="panel-title"><?php echo tr('Magazzino automezzo'); ?></h3>
					</div>
				</div>
			</div>

			<div class="panel-body">
				<div class="row">
					<div class="col-md-12">
						<?php
                        include $docroot.'/modules/automezzi/row-list-articoli.php';
                        ?>

						<div class="pull-left">
							<a class="btn btn-sm btn-primary" data-href="<?php echo $rootdir; ?>/modules/automezzi/add_articolo.php?idautomezzo=<?php echo $id_record; ?>" data-toggle="modal" data-title="Aggiungi articoli"><i class="fa fa-plus"></i> <?php echo tr('Articolo magazzino'); ?></a><br>
						</div>
						<div class="clearfix"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

