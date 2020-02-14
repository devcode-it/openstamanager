<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="addsede">

	<div class="row">
		<div class="col-md-12">
			{[ "type": "text", "label": "'.tr('Nome sede').'", "name": "nomesede", "required": 1 ]}
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Indirizzo').'", "name": "indirizzo", "required": 0 ]}
		</div>

		<div class="col-md-6">
            {[ "type": "text", "label": "'.($record['tipo_anagrafica'] == 'Ente pubblico' ? tr('Codice unico ufficio') : tr('Codice destinatario')).'", "name": "codice_destinatario", "required": 0, "class": "text-center text-uppercase alphanumeric-mask", "value": "$codice_destinatario$", "maxlength": '.($record['tipo_anagrafica'] == 'Ente pubblico' ? '6' : '7').',  "extra": "'.(empty($record['tipo_anagrafica']) || $record['tipo_anagrafica'] == 'Privato' ? 'disabled' : '').'", "help": "'.tr('<b>Attenzione</b>: per impostare il codice specificare prima \'Tipologia\' e \'Nazione\' dell\'anagrafica:<br><ul><li>Ente pubblico (B2G/PA) - Codice Univoco Ufficio (www.indicepa.gov.it), 6 caratteri</li><li>Azienda (B2B) - Codice Destinatario, 7 caratteri</li><li>Privato (B2C) - viene utilizzato il Codice Fiscale</li></ul>').'", "readonly": "'.intval($record['iso2'] != 'IT').'" ]}
		</div>
		
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Città').'", "name": "citta", "required": 1  ]}
		</div>

		<div class="col-md-2">
			{[ "type": "text", "label": "'.tr('C.A.P.').'", "name": "cap" ]}
		</div>

		<div class="col-md-2">
			{[ "type": "text", "label": "'.tr('Provincia').'", "name": "provincia", "maxlength": 2, "class": "text-center text-uppercase", "extra": "onkeyup=\"this.value = this.value.toUpperCase();\"" ]}
		</div>

		<div class="col-md-2">
			{[ "type": "text", "label": "'.tr('Km').'", "name": "km" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Nazione').'", "name": "id_nazione", "ajax-source": "nazioni" ]}
		</div>
        <div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Zona').'", "name": "idzona", "ajax-source": "zone", "placeholder": "'.tr('Nessuna zona').'", "icon-after": "add|'.Modules::get('Zone')['id'].'" ]}
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

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';
