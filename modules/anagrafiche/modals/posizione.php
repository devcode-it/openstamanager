<?php

include_once __DIR__.'/../../../core.php';
include_once __DIR__.'/../init.php';

echo '
<form action="" method="post" id="form-posizione">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="posizione">

    <div class="row">
        <div class="col-md-4" id="geocomplete">
            {[ "type": "text", "label": "'.tr('Indirizzo Google').'", "name": "gaddress", "value": "'.$record['gaddress'].'", "extra": "data-geo=\'formatted_address\'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "text", "label": "'.tr('Latitudine').'", "name": "lat", "value": "'.$record['lat'].'", "extra": "data-geo=\'lat\'", "class": "text-right" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "text", "label": "'.tr('Longitudine').'", "name": "lng", "value": "'.$record['lng'].'", "extra": "data-geo=\'lng\'", "class": "text-right" ]}
        </div>
    </div>

    <div id="map" style="height:400px; width:100%"></div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> '.tr('Salva').'
            </button>
		</div>
	</div>
</form>';

echo '
<script>$(document).ready(init)</script>

<script>
$("#geocomplete input").geocomplete({
    map: $("#map").length ? "#map" : false,
    location: $("#gaddress").val() ? $("#gaddress").val() : [$("#lat").val(), $("#lng").val()],
    details: ".details",
    detailsAttribute: "data-geo"
}).bind("geocode:result", function (event, result) {
    $("#lat").val(result.geometry.location.lat());
    $("#lng").val(result.geometry.location.lng());
});
</script>';
