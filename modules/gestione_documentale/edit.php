<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">

	<!-- SCHEDA FILE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"> Scheda file</h3>
		</div>

		<div class="panel-body">

			<div class="row">

				<div class="col-md-6">
					{[ "type": "text", "label": "Nome", "name": "nome", "required": 1, "class": "", "value": "$nome$", "extra": "" ]}
				</div>



				<div class="col-md-3">
					{[ "type": "select", "label": "Categoria", "name": "idcategoria", "required": 1, "class": "", "ajax-source": "categorie_documenti", "value": "$idcategoria$", "extra": "" ]}
				</div>


				<div class="col-md-3">
					{[ "type": "text", "label": "Data", "name": "data", "required": 1, "class": "datepicker text-center", "value": "<?php echo date('d/m/Y', strtotime($record['data'])); ?>", "extra": "" ]}
				</div>

			</div>
		</div>
	</div>

</form>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
