<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="plugin_editor.php?id_plugin=$id_plugin$&id_module=$id_module$&id_record=$id_record$&id_parent=$id_parent$" method="post" role="form" id="form_sedi">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="updatereferente">

	<div class="row">
        <div class="col-xs-12 col-md-6">
            {[ "type": "text", "label": "'.tr('Nominativo').'", "name": "nome", "required": 1, "value" : "$nome$" ]}
        </div>

        <div class="col-xs-12 col-md-6">
            {[ "type": "text", "label": "'.tr('Mansione').'", "name": "mansione", "required": 1, "value" : "$mansione$" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-md-6">
            {[ "type": "text", "label": "'.tr('Telefono').'", "name": "telefono", "value" : "$telefono$" ]}
        </div>

        <div class="col-xs-12 col-md-6">
            {[ "type": "text", "label": "'.tr('Indirizzo email').'", "name": "email", "value" : "$email$" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-md-12">
            {[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede", "values": "query=SELECT -1 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT_WS(\' - \', nomesede, citta) AS descrizione FROM an_sedi WHERE idanagrafica='.$id_parent.'", "value" : "$idsede$" ]}
        </div>
    </div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
            <a class="btn btn-danger ask" data-backto="record-edit" data-href="'.$rootdir.'/plugin_editor.php" data-op="deletereferente" data-id_record="'.$records[0]['id'].'" data-id_plugin="'.$id_plugin.'" data-id_module="'.$id_module.'" data-id_parent="'.$id_parent.'">
                <i class="fa fa-trash"></i> '.tr('Elimina').'
            </a>

			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.tr('Modifica').'</button>
		</div>
	</div>
</form>';

echo '
<script src="'.$rootdir.'/lib/init.js"></script>';
