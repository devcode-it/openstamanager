<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="plugin_editor.php?id_plugin=$id_plugin$&id_module=$id_module$&id_parent='.$id_parent.'" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="updatesede">
	<input type="hidden" name="id" value="'.$records[0]['id'].'">

	<div class="row">
		<div class="col-xs-12 col-md-12">
			{[ "type": "text", "label": "'.tr('Nome sede').'", "name": "nomesede", "required": 1, "value": "$nomesede$" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Indirizzo').'", "name": "indirizzo", "required": 1, "value": "$indirizzo$" ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Secondo indirizzo').'", "name": "indirizzo2", "value": "$indirizzo2$" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('P.Iva').'", "name": "piva", "value": "$piva$" ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Codice Fiscale').'", "name": "codice_fiscale", "value": "$codice_fiscale$" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-3">
			{[ "type": "text", "label": "'.tr('Città').'", "name": "citta", "value": "$citta$" ]}
		</div>

		<div class="col-xs-12 col-md-3">
			{[ "type": "text", "label": "'.tr('C.A.P.').'", "name": "cap", "value": "$cap$" ]}
		</div>

		<div class="col-xs-12 col-md-3">
			{[ "type": "text", "label": "'.tr('Provincia').'", "name": "provincia", "value": "$provincia$" ]}
		</div>

		<div class="col-xs-12 col-md-3">
			{[ "type": "number", "label": "'.tr('Km').'", "name": "km", "value": "$km$" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "select", "label": "'.tr('Nazione').'", "name": "id_nazione", "values": "query=SELECT `id`, `nome` AS `descrizione` FROM `an_nazioni` ORDER BY `descrizione` ASC", "value": "$id_nazione$" ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Telefono').'", "name": "telefono", "value": "$telefono$" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Fax').'", "name": "fax", "value": "$fax$" ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Cellulare').'", "name": "cellulare", "value": "$cellulare$" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Indirizzo email').'", "name": "email", "value": "$email$" ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "select", "label": "'.tr('Zona').'", "name": "idzona", "values": "query=SELECT `id`, CONCAT(`nome`, \' - \', `descrizione`) AS `descrizione` FROM `an_zone` ORDER BY `descrizione` ASC", "value": "$idzona$" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
            <a class="btn btn-danger ask" data-backto="record-edit" data-href="'.$rootdir.'/plugin_editor.php" data-op="deletesede" data-id="'.$records[0]['id'].'" data-id_plugin="'.$id_plugin.'" data-id_module="'.$id_module.'" data-id_parent="'.$id_parent.'">
                <i class="fa fa-trash"></i> '.tr('Elimina').'
            </a>

			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.tr('Modifica').'</button>
		</div>
	</div>
</form>

<script src="'.$rootdir.'/lib/init.js"></script>';
