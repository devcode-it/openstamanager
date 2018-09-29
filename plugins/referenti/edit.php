<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="updatereferente">

	<div class="row">
        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Nominativo').'", "name": "nome", "required": 1, "value" : "$nome$" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Mansione').'", "name": "mansione", "required": 1, "value" : "$mansione$" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Telefono').'", "name": "telefono", "value" : "$telefono$" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Indirizzo email').'", "name": "email", "value" : "$email$" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede", "values": "query=SELECT 0 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT_WS(\' - \', nomesede, citta) AS descrizione FROM an_sedi WHERE idanagrafica='.$id_parent.'", "value" : "$idsede$", "required": 1 ]}
        </div>
    </div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
            <a class="btn btn-danger ask" data-backto="record-edit" data-op="deletereferente" data-id_record="'.$record['id'].'" data-id_plugin="'.$id_plugin.'" data-id_module="'.$id_module.'" data-id_parent="'.$id_parent.'">
                <i class="fa fa-trash"></i> '.tr('Elimina').'
            </a>

			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.tr('Modifica').'</button>
		</div>
	</div>
</form>';

echo '
<script src="'.$rootdir.'/lib/init.js"></script>';
