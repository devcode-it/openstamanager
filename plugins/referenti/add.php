<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
    <input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="addreferente">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Nominativo').'", "name": "nome", "required": 1 ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Mansione').'", "name": "mansione", "required": 1 ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Telefono').'", "name": "telefono" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Indirizzo email').'", "name": "email" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			{[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede", "values": "query=SELECT 0 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT_WS(\' - \', nomesede, citta) AS descrizione FROM an_sedi WHERE idanagrafica='.$id_parent.'", "value": "0", "required": 1 ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';
