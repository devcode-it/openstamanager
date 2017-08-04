<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record ?>">

	<!-- DATI ARTICOLO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _('Automezzo') ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo _('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo _('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
				</div>
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo _('Targa'); ?>", "name": "targa", "required": 1, "value": "$targa$" ]}
				</div>

			</div>
			<div class="row">
				<div class="col-md-12">
					{[ "type": "text", "label": "<?php echo _('Descrizione'); ?>", "name": "descrizione", "value": "$descrizione$" ]}
				</div>
			</div>
		</div>
	</div>
</form>

<!-- TECNICI -->
<div class="panel panel-primary">

	<div class="panel-heading">
		<div class="row">
			<div class="col-md-6">
				<h3 class="panel-title"><?php echo _('Tecnici responsabili automezzo') ?></h3>
			</div>
			<div class="col-md-6">
				<h3 class="panel-title"><?php echo _('Magazzino automezzo') ?></h3>
			</div>
		</div>
	</div>

	<div class="panel-body">

		<div class="row">
			<div class="col-md-6" style="border-right:1px solid #DDD;">
				<form action="<?php echo $rootdir ?>/editor.php?id_module=<?php echo Modules::getModule('Automezzi')['id'] ?>&id_record=<?php echo $id_record ?>" id="updatetech-form" method="post" role="form">
					<input type="hidden" name="backto" value="record-edit">
					<input type="hidden" name="id_record" value="<?php echo $id_record ?>">
					<input type="hidden" name="op" value="">

					<?php
                    include($docroot.'/modules/automezzi/row-list-tecnici.php');
                    ?>
				</form>

				<a href="javascript:;" class="btn btn-sm btn-success pull-right" title="Aggiorna date" onclick="$('#updatetech-form input[name=op]').val('savetech'); $('#updatetech-form').submit();"><i class="fa fa-edit"></i> <?php echo _('Salva date') ?></a>

				<div class="pull-left">
					<a class="btn btn-sm btn-primary" data-href="<?php echo $rootdir ?>/modules/automezzi/add_tecnico.php?idautomezzo=<?php echo $id_record ?>" data-toggle="modal" data-title="Aggiungi tecnico" data-target="#bs-popup"><i class="fa fa-plus"></i> <?php echo _('Aggiungi tecnico') ?></a><br>
				</div>
				<div class="clearfix"></div>
			</div>


			<div class="col-md-6">
				<?php
                include($docroot.'/modules/automezzi/row-list-articoli.php');
                ?>

				<div class="pull-left">
					<a class="btn btn-sm btn-primary" data-href="<?php echo $rootdir ?>/modules/automezzi/add_articolo.php?idautomezzo=<?php echo $id_record ?>" data-toggle="modal" data-title="Aggiungi articoli" data-target="#bs-popup"><i class="fa fa-plus"></i> <?php echo _('Articolo magazzino') ?></a><br>
				</div>
				<div class="clearfix"></div>
			</div>

		</div>

	</div>
</div>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo _('Elimina'); ?>
</a>

