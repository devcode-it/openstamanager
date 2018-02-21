<?php

include_once __DIR__.'/../../core.php';

$google = Settings::get('Google Maps API key');

/*
if (!empty($google)) {
    echo '
<script src="//maps.googleapis.com/maps/api/js?libraries=places&key='.$google.'"></script>';
}
*/

echo '
<form action="plugin_editor.php?id_plugin=$id_plugin$&id_module=$id_module$&id_record=$id_record$&id_parent=$id_parent$" method="post" role="form" id="form_sedi">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="updatesede">

	<div class="row">
		<div class="col-md-12">
			{[ "type": "text", "label": "'.tr('Nome sede').'", "name": "nomesede", "required": 1, "value": "$nomesede$" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Indirizzo').'", "name": "indirizzo", "id": "indirizzo_",  "required": 1, "value": "$indirizzo$" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Secondo indirizzo').'", "name": "indirizzo2", "value": "$indirizzo2$" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('P.Iva').'", "name": "piva", "value": "$piva$" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Codice Fiscale').'", "name": "codice_fiscale", "value": "$codice_fiscale$" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-3">
			{[ "type": "text", "label": "'.tr('Città').'", "name": "citta", "id": "citta_", "value": "$citta$" ]}
		</div>

		<div class="col-md-3">
			{[ "type": "text", "label": "'.tr('C.A.P.').'", "name": "cap", "value": "$cap$" ]}
		</div>

		<div class="col-md-3">
			{[ "type": "text", "label": "'.tr('Provincia').'", "name": "provincia", "value": "$provincia$" ]}
		</div>

		<div class="col-md-3">
			{[ "type": "number", "label": "'.tr('Km').'", "name": "km", "value": "$km$" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Nazione').'", "name": "id_nazione", "values": "query=SELECT `id`, `nome` AS `descrizione` FROM `an_nazioni` ORDER BY `descrizione` ASC", "value": "$id_nazione$" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Telefono').'", "name": "telefono", "value": "$telefono$" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Fax').'", "name": "fax", "value": "$fax$" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Cellulare').'", "name": "cellulare", "value": "$cellulare$" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Indirizzo email').'", "name": "email", "value": "$email$" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Zona').'", "name": "idzona", "values": "query=SELECT `id`, CONCAT(`nome`, \' - \', `descrizione`) AS `descrizione` FROM `an_zone` ORDER BY `descrizione` ASC", "value": "$idzona$" ]}
		</div>
    </div>';

if (!empty($google)) {
    echo '
	<div class="row">
		<div class="col-md-6" id="geocomplete">
			{[ "type": "text", "label": "'.tr('Indirizzo Google').'", "name": "gaddress", "value": "$gaddress$", "extra": "data-geo=\'formatted_address\'" ]}
		</div>

		<div class="col-md-2">
			{[ "type": "text", "label": "'.tr('Latitudine').'", "name": "lat", "id": "lat_", "value": "$lat$", "extra": "data-geo=\'lat\'", "class": "text-right" ]}
		</div>

		<div class="col-md-2">
			{[ "type": "text", "label": "'.tr('Longitudine').'", "name": "lng", "id": "lng_",  "value": "$lng$", "extra": "data-geo=\'lng\'", "class": "text-right" ]}
		</div>';

    // Vedi su google maps
    if (!empty($records[0]['indirizzo']) || (empty($records[0]['citta']))) {
        echo '
			<div  class="btn-group col-md-2"  >
				<label>&nbsp;</label><br>
				<a class="btn btn-info" title="'.tr('Mostra la sede su Google Maps').'"  onclick="window.open(\'https://maps.google.com/maps/search/\'+encodeURI( $(\'#indirizzo_\').val() )+\', \'+encodeURI( $(\'#citta_\').val() ) );">&nbsp;<i class="fa fa-map-marker">&nbsp;</i></a>
			';

        echo '
				<a title="'.tr('Calcola percoso da sede legale a questa sede').'" class="btn btn-primary btn-secondary" onclick="window.open(\'https://maps.google.com/maps/dir/\'+encodeURI( $(\'#indirizzo_\').val() )+\', \'+encodeURI( $(\'#citta_\').val() )+\'/\'+encodeURI( $(\'#indirizzo\').val() )+\',\'+encodeURI( $(\'#citta\').val() )+\',8z\');"><i class="fa fa-car"></i></a>
			</div>';
    }

    echo '
    </div>';

    if (!empty($records[0]['gaddress']) || (!empty($records[0]['lat']) && !empty($records[0]['lng']))) {
        echo '
    <div id="map" style="height:400px; width:100%"></div><br>';
    }
} else {
    echo '
    <div class="alert alert-info">
        '.Modules::link('Impostazioni', $dbo->fetchArray("SELECT `idimpostazione` FROM `zz_settings` WHERE sezione='Generali'")[0]['idimpostazione'], tr('Per abilitare la visualizzazione delle anagrafiche nella mappa, inserire la Google Maps API Key nella scheda Impostazioni')).'.
    </div>';
}

echo '
	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
            <a class="btn btn-danger ask" data-backto="record-edit" data-href="'.$rootdir.'/plugin_editor.php" data-op="deletesede" data-id_record="'.$records[0]['id'].'" data-id_plugin="'.$id_plugin.'" data-id_module="'.$id_module.'" data-id_parent="'.$id_parent.'">
                <i class="fa fa-trash"></i> '.tr('Elimina').'
            </a>

			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.tr('Modifica').'</button>
		</div>
	</div>
</form>';

echo '
<script src="'.$rootdir.'/lib/init.js"></script>';

echo '
<script>
$(document).ready( function(){
    $("#form_sedi #geocomplete input").geocomplete({
        map: $("#form_sedi #map").length ? "#form_sedi #map" : false,
        location: $("#form_sedi #gaddress").val() ? $("#form_sedi #gaddress").val() : [$("#form_sedi #lat_").val(), $("#form_sedi #lng_").val()],
        details: "#form_sedi .details",
        detailsAttribute: "data-geo"
    }).bind("geocode:result", function (event, result) {
        $("#form_sedi #lat_").val(result.geometry.location.lat());
        $("#form_sedi #lng_").val(result.geometry.location.lng());
    });
});
</script>';
