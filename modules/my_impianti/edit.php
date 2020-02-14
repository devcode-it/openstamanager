<?php

include_once __DIR__.'/../../core.php';

$_SESSION['superselect']['idanagrafica'] = $record['idanagrafica'];

$img = null;
if (!empty($record['immagine'])) {
    $fileinfo = Uploads::fileInfo($record['immagine']);

    $default_img = '/'.Uploads::getDirectory($id_module).'/'.$fileinfo['filename'].'_thumb600.'.$fileinfo['extension'];

    $img = file_exists(DOCROOT.$default_img) ? ROOTDIR.$default_img : ROOTDIR.'/'.Uploads::getDirectory($id_module).'/'.$record['immagine'];
}

?><form action="" method="post" id="edit-form" enctype="multipart/form-data">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="matricola" value="<?php echo $id_record; ?>">

	<!-- DATI ANAGRAFICI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati impianto'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-3">
					{[ "type": "image", "label": "<?php echo tr('Immagine'); ?>", "name": "immagine", "class": "img-thumbnail", "value": "<?php echo $img; ?>" ]}
				</div>

				<div class="col-md-9">
					<div class="row">
						<div class="col-md-6">
							{[ "type": "text", "label": "<?php echo tr('Matricola'); ?>", "name": "matricola", "required": 1, "class": "text-center", "maxlength": 25, "value": "$matricola$" ]}
						</div>

						<div class="col-md-6">
							{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
						</div>
						<div class="clearfix"></div>

						<div class="col-md-6">
							<?php
                                echo Modules::link('Anagrafiche', $record['idanagrafica'], null, null, 'class="pull-right"');
                            ?>
							{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "value": "$idanagrafica$", "extra": "", "ajax-source": "clienti" ]}
						</div>
                        <div class="col-md-6">
                            {[ "type": "select", "label": "<?php echo tr('Categoria'); ?>", "name": "id_categoria", "required": 0, "value": "$id_categoria$", "values": "query=SELECT id, nome AS descrizione FROM my_impianti_categorie" ]}
                        </div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Tecnico assegnato'); ?>", "name": "idtecnico", "ajax-source": "tecnici", "value": "$idtecnico$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "date", "label": "<?php echo tr('Data installazione'); ?>", "name": "data", "value": "$data$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede", "value": "$idsede$", "required": "1", "ajax-source": "sedi", "placeholder": "Sede legale" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "value": "$descrizione$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "text", "label": "<?php echo tr('Proprietario'); ?>", "name": "proprietario", "value": "$proprietario$" ]}
				</div>

			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Ubicazione'); ?>", "name": "ubicazione", "value": "$ubicazione$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Palazzo'); ?>", "name": "palazzo", "value": "$palazzo$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Scala'); ?>", "name": "scala", "value": "$scala$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Piano'); ?>", "name": "piano", "value": "$piano$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Interno'); ?>", "name": "interno", "value": "$interno$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Occupante'); ?>", "name": "occupante", "value": "$occupante$" ]}
				</div>
			</div>

		</div>
	</div>
</form>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>


<script type="text/javascript">
$(document).ready(function(){

	$('#idanagrafica').change( function(){

		session_set('superselect,idanagrafica', $(this).val(), 0);

        var value = !$(this).val() ? true : false;

		$("#idsede").prop("disabled", value);
		$("#idsede").selectReset();

	});

	$('#idsede').change( function(){
		//session_set('superselect,idsede', $(this).val(), 0);
	});

});
</script>
