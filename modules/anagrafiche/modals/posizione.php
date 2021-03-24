<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../../core.php';
include_once __DIR__.'/../init.php';

$google = setting('Google Maps API key');

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

    <div class="row">
        <div class="col-md-12">
            <div id="map" style="height:400px;"></div>
        </div>
    </div>
    <div>&nbsp;</div>
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
if(window.google){
    initGeocomplete();
} else {
    $.getScript("//maps.googleapis.com/maps/api/js?libraries=places&key='.$google.'", function() {
        initGeocomplete();
    });
}

function initGeocomplete() {
    $("#geocomplete input").geocomplete({
        map: $("#map").length ? "#map" : false,
        location: $("#gaddress").val() ? $("#gaddress").val() : [$("#lat").val(), $("#lng").val()],
        details: ".details",
        detailsAttribute: "data-geo"
    }).bind("geocode:result", function (event, result) {
        $("#lat").val(result.geometry.location.lat());
        $("#lng").val(result.geometry.location.lng());
    });
}

// Ricaricamento della pagina alla chiusura
$("#modals > div button.close").on("click", function() {
    location.reload();
});
</script>';
