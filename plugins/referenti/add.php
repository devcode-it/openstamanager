<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="plugin_editor.php?id_plugin=$id_plugin$&id_module=$id_module$&id_parent=$id_parent$" method="post" role="form">
    <input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="addreferente">

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Nominativo').'", "name": "nome", "required": 1 ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Mansione').'", "name": "mansione", "required": 1 ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Telefono').'", "name": "telefono" ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Indirizzo email').'", "name": "email" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-12">
			{[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede", "values": "query=SELECT -1 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT_WS(\' - \', nomesede, citta) AS descrizione FROM an_sedi WHERE idanagrafica='.$id_parent.'" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-xs-12 col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>

<script src="'.$rootdir.'/lib/init.js"></script>';
