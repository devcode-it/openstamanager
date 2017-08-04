<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="plugin_editor.php?id_plugin=$id_plugin$&id_module=$id_module$&id_parent='.$id_parent.'" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="addsede">

	<div class="row">
		<div class="col-xs-12 col-md-4">
			{[ "type": "text", "label": "'._('Nome sede').'", "name": "nomesede", "required": 1 ]}
		</div>

		<div class="col-xs-12 col-md-4">
			{[ "type": "text", "label": "'._('Indirizzo').'", "name": "indirizzo", "required": 1 ]}
		</div>

		<div class="col-xs-12 col-md-4">
			{[ "type": "text", "label": "'._('Secondo indirizzo').'", "name": "indirizzo2" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-3">
			{[ "type": "text", "label": "'._('Città').'", "name": "citta" ]}
		</div>

		<div class="col-xs-12 col-md-3">
			{[ "type": "text", "label": "'._('C.A.P.').'", "name": "cap" ]}
		</div>

		<div class="col-xs-12 col-md-3">
			{[ "type": "text", "label": "'._('Provincia').'", "name": "provincia" ]}
		</div>

		<div class="col-xs-12 col-md-3">
			{[ "type": "text", "label": "'._('Km').'", "name": "km" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-4">
			{[ "type": "text", "label": "'._('Cellulare').'", "name": "cellulare" ]}
		</div>

        <div class="col-xs-12 col-md-4">
			{[ "type": "text", "label": "'._('Telefono').'", "name": "telefono" ]}
		</div>

		<div class="col-xs-12 col-md-4">
			{[ "type": "text", "label": "'._('Indirizzo email').'", "name": "email" ]}
		</div>
	</div>

	<div class="row">
        <div class="col-xs-12 col-md-12">
			{[ "type": "select", "label": "'._('Zona').'", "name": "idzona", "values": "query=SELECT `id`, CONCAT(`nome`, \' - \', `descrizione`) AS `descrizione` FROM `an_zone` ORDER BY `descrizione` ASC" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '._('Aggiungi').'</button>
		</div>
	</div>
</form>

<script src="'.$rootdir.'/lib/init.js"></script>';
