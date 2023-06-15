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

echo '
<form action="" method="post" id="form-posizione">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="posizione">

    <div class="row">
        <div class="col-md-4" id="geocomplete">
            {[ "type": "text", "label": "'.tr('Indirizzo').'", "name": "gaddress", "value": "'.$record['gaddress'].'", "extra": "data-geo=\'formatted_address\'" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "text", "label": "'.tr('Latitudine').'", "name": "lat", "value": "'.$record['lat'].'", "extra": "data-geo=\'lat\'", "class": "text-right" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "text", "label": "'.tr('Longitudine').'", "name": "lng", "value": "'.$record['lng'].'", "extra": "data-geo=\'lng\'", "class": "text-right" ]}
        </div>

        <div class="col-md-2">
            <br><button type="button" class="btn btn-lg btn-default pull-right" onclick="initGeocomplete();"><i class="fa fa-search"></i> '.tr('Cerca').'</button>
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
$(document).ready(function(){
    if (input("lat").get() && input("lng").get()) {
        setTimeout(function () {
            caricaMappa();
        }, 1000);
    }
});

function initGeocomplete() {
    $.ajax({
        url: "https://nominatim.openstreetmap.org/search.php?q=" + encodeURI(input("gaddress").get()) + "&format=jsonv2",
        type : "GET",
        dataType: "JSON",
        success: function(data){
            input("lat").set(data[0].lat);
            input("lng").set(data[0].lon);
            input("gaddress").set(data[0].display_name);
            caricaMappa();
        }
    });
}

function caricaMappa() {
    const lat = parseFloat(input("lat").get());
    const lng = parseFloat(input("lng").get());

    var container = L.DomUtil.get("map"); 
    if(container != null){ 
        container._leaflet_id = null; 
    }

    var map = L.map("map", {
        center: [lat, lng],
        zoom: 10,
        gestureHandling: true
    });
    
    var icon = new L.Icon({
        iconUrl: globals.rootdir + "/assets/dist/img/marker-icon.png",
        shadowUrl:globals.rootdir + "/assets/dist/img/leaflet/marker-shadow.png",
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    L.tileLayer("'.setting("Tile layer OpenStreetMap").'", {
        maxZoom: 17,
        attribution: "© OpenStreetMap"
    }).addTo(map); 
    
    var marker = L.marker([lat, lng], {
        icon: icon
    }).addTo(map);
}

// Ricaricamento della pagina alla chiusura
$("#modals > div button.close").on("click", function() {
    location.reload();
});
</script>';
