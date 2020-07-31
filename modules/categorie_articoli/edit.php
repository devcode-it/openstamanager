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
				<div class="col-md-8">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Colore'); ?>", "name": "colore", "class": "colorpicker text-center", "value": "$colore$", "extra": "maxlength='7'", "icon-after": "<div class='img-circle square'></div>" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Nota'); ?>", "name": "nota", "value": "$nota$" ]}
				</div>
			</div>
		</div>
	</div>

</form>

<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title"><?php echo tr('Sottocategorie'); ?></h3>
	</div>

	<div class="panel-body">
		<div class="pull-left">
			<a class="btn btn-primary" data-href="<?php echo $rootdir; ?>/add.php?id_module=<?php echo $id_module; ?>&id_original=<?php echo $id_record; ?>" data-toggle="modal" data-title="<?php echo tr('Aggiungi riga'); ?>"><i class="fa fa-plus"></i> <?php echo tr('Sottocategoria'); ?></a><br>
		</div>
		<div class="clearfix"></div>
		<hr>

		<div class="row">
			<div class="col-md-12">
				<table class="table table-striped table-hover table-condensed">
				<tr>
					<th><?php echo tr('Nome'); ?></th>
					<th><?php echo tr('Colore'); ?></th>
					<th><?php echo tr('Nota'); ?></th>
					<th width="20%"><?php echo tr('Opzioni'); ?></th>
				</tr>

				<?php include $docroot.'/modules/'.Modules::get($id_module)['directory'].'/row-list.php'; ?>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
	$(document).ready( function() {
		$('.colorpicker').colorpicker().on('changeColor', function() {
			$('#colore').parent().find('.square').css( 'background', $('#colore').val() );
		});

		$('#colore').parent().find('.square').css( 'background', $('#colore').val() );
	});
</script>

<?php

$elementi = $dbo->fetchArray('SELECT `mg_articoli`.`id`, `mg_articoli`.`codice`, `mg_articoli`.`barcode`, `mg_articoli`.`deleted_at` FROM `mg_articoli` WHERE `id_categoria`='.prepare($id_record).' OR `id_sottocategoria`='.prepare($id_record).'  OR `id_sottocategoria` IN (SELECT id FROM `mg_categorie` WHERE `parent`='.prepare($id_record).')');

if (!empty($elementi)) {
    echo '
<div class="box box-warning collapsable collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-warning"></i> '.tr('Articoli collegati: _NUM_', [
            '_NUM_' => count($elementi),
        ]).'</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <ul>';

    foreach ($elementi as $elemento) {
        $descrizione = tr('Articolo _CODICE_ _DELETED_AT_', [
        '_CODICE_' => !empty($elemento['codice']) ? $elemento['codice'] : $elemento['barcode'],
        '_DELETED_AT_' => (!empty($elemento['deleted_at']) ? tr('Eliminato il:').' '.Translator::dateToLocale($elemento['deleted_at']) : ''),
    ]);
        $modulo = 'Articoli';
        $id = $elemento['id'];

        echo '
		<li>'.Modules::link($modulo, $id, $descrizione).'</li>';
    }

    echo '
	</ul>
</div>
</div>';
} else {
    echo '
    <a class="btn btn-danger ask" data-backto="record-list">
        <i class="fa fa-trash"></i> '.tr('Elimina').'
    </a>';
}
