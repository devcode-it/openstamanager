<?php

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class='row'>
		<div class="col-md-12">
			{[ "type": "text", "label": "Nome", "name": "nome", "required": 1, "class": "", "value": "", "extra": "" ]}
		</div>
	</div>
	<div class='row'>
		<div class="col-md-6">
			{[ "type": "select", "label": "Categoria", "name": "idcategoria", "required": 1, "class": "", "ajax-source": "categorie_documenti" , "value": "", "extra": "", "icon-after": "add|<?php echo Modules::get('Categorie documenti')['id']; ?>"  ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "Data", "name": "data", "required": 1, "class": "datepicker text-center", "value": "", "extra": "" ]}
		</div>
	</div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>
