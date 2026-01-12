<?php

include_once __DIR__.'/../../core.php';
use Models\Module;

unset($_SESSION['superselect']['idautomezzo']);

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- DATI ARTICOLO -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Automezzo'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
				</div>
				<div class="col-md-6">
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
		<div class="card card-primary">
			<div class="card-header">
				<div class="row">
					<div class="col-md-12">
						<h3 class="card-title"><?php echo tr('Utenti responsabili automezzo'); ?></h3>
					</div>
				</div>
			</div>

			<div class="card-body">
				<div class="row">
					<div class="col-md-12" >
						<form action="<?php echo $rootdir; ?>/editor.php?id_module=<?php echo Module::where('name', 'Automezzi')->first()->id; ?>&id_record=<?php echo $id_record; ?>" id="updatetech-form" method="post" role="form">
							<input type="hidden" name="backto" value="record-edit">
							<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
							<input type="hidden" name="op" value="">

							<?php
                            include $docroot.'/modules/automezzi/row-list-tecnici.php';
?>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>


	<!-- MAGAZZINO AUTOMEZZO -->
	<div class="col-md-6">
		<div class="card card-primary">
			<div class="card-header">
				<div class="row">
					<div class="col-md-12">
						<h3 class="card-title"><?php echo tr('Magazzino automezzo'); ?></h3>
					</div>
				</div>
			</div>

			<div class="card-body">
				<div class="row">
					<div class="col-md-12">
						<?php
                        include $docroot.'/modules/automezzi/row-list-articoli.php';
?>

						<div class="pull-left">
							<a class="btn btn-sm btn-primary" data-href="<?php echo $rootdir; ?>/modules/automezzi/add_articolo.php?idautomezzo=<?php echo $id_record; ?>" data-card-widget="modal" data-title="Aggiungi articoli"><i class="fa fa-plus"></i> <?php echo tr('Articolo magazzino'); ?></a><br>
						</div>
						<div class="clearfix"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- REGISTRI DI VIAGGIO -->
<div class="card card-primary">
	<div class="card-header">
		<h3 class="card-title"><?php echo tr('Registro di viaggio'); ?></h3>
	</div>

	<div class="card-body">
		<div class="row">
			<div class="col-md-12">
				<?php
                include $docroot.'/modules/automezzi/row-list-viaggi.php';
?>

				<div class="pull-left">
					<a class="btn btn-sm btn-primary" data-href="<?php echo $module->fileurl('modals/manage_viaggio.php'); ?>?id_record=<?php echo $id_record; ?>" data-card-widget="modal" data-title="Aggiungi viaggio"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi viaggio'); ?></a>
					<a class="btn btn-sm btn-info" data-href="<?php echo $module->fileurl('modals/stampa_registro.php'); ?>?id_record=<?php echo $id_record; ?>" data-card-widget="modal" data-title="<?php echo tr('Stampa registro viaggio'); ?>"><i class="fa fa-print"></i> <?php echo tr('Stampa registro'); ?></a>
				</div>
				<div class="clearfix"></div>
			</div>
		</div>
	</div>
</div>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<script>

	$(document).ready(function(){
		$("#pulsanti .btn-info").addClass("hidden");
	});

</script>
