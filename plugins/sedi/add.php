<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="plugin_editor.php?id_plugin=$id_plugin$&id_module=$id_module$&id_parent=$id_parent$" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="addsede">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Nome sede').'", "name": "nomesede", "required": 1 ]}
		</div>

		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Indirizzo').'", "name": "indirizzo", "required": 1 ]}
		</div>

		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Secondo indirizzo').'", "name": "indirizzo2" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-3">
			{[ "type": "text", "label": "'.tr('Città').'", "name": "citta" ]}
		</div>

		<div class="col-md-3">
			{[ "type": "text", "label": "'.tr('C.A.P.').'", "name": "cap" ]}
		</div>

		<div class="col-md-3">
			{[ "type": "text", "label": "'.tr('Provincia').'", "name": "provincia" ]}
		</div>

		<div class="col-md-3">
			{[ "type": "text", "label": "'.tr('Km').'", "name": "km" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Cellulare').'", "name": "cellulare" ]}
		</div>

        <div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Telefono').'", "name": "telefono" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Indirizzo email').'", "name": "email" ]}
		</div>
	</div>

	<div class="row">
        <div class="col-md-12">
			{[ "type": "select", "label": "'.tr('Zona').'", "name": "idzona", "values": "query=SELECT `id`, CONCAT(`nome`, \' - \', `descrizione`) AS `descrizione` FROM `an_zone` ORDER BY `descrizione` ASC" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>

<script src="'.$rootdir.'/lib/init.js"></script>';
