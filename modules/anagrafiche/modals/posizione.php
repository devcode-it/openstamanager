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
        <div class="col-md-6" id="geocomplete">
            {[ "type": "text", "label": "'.tr('Indirizzo').'", "name": "gaddress", "value": "'.$record['gaddress'].'", "extra": "data-geo=\'formatted_address\'" ]}
        </div>

        <div class="col-md-2">
            <label>&nbsp;</label>
            <br><button type="button" class="btn btn-primary" onclick="initGeocomplete();"><i class="fa fa-search"></i> '.tr('Cerca').'</button>
        </div>

        <div class="col-md-2">
            {[ "type": "text", "label": "'.tr('Latitudine').'", "name": "lat", "value": "'.$record['lat'].'", "extra": "data-geo=\'lat\'", "class": "text-right", "readonly": true ]}
        </div>

        <div class="col-md-2">
            {[ "type": "text", "label": "'.tr('Longitudine').'", "name": "lng", "value": "'.$record['lng'].'", "extra": "data-geo=\'lng\'", "class": "text-right", "readonly": true ]}
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
$("#modals > div").on("shown.bs.modal", function () {
    if (input("lat").get() && input("lng").get()) {
        caricaMappa();
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

var map = null;
function caricaMappa() {
    const lat = parseFloat(input("lat").get());
    const lng = parseFloat(input("lng").get());

    var container = L.DomUtil.get("map"); 
    if(container._leaflet_id != null){ 
        map.eachLayer(function (layer) {
			if(layer instanceof L.Marker) {
				map.removeLayer(layer);
			}
		});
	} else {
		map = L.map("map", {
			gestureHandling: true
		});

		L.tileLayer("'.setting('Tile server OpenStreetMap').'", {
			maxZoom: 17,
			attribution: "© OpenStreetMap"
		}).addTo(map); 
	}

	var icon = new L.Icon({
		iconUrl: globals.rootdir + "/assets/dist/img/marker-icon.png",
		shadowUrl:globals.rootdir + "/assets/dist/img/leaflet/marker-shadow.png",
		iconSize: [25, 41],
		iconAnchor: [12, 41],
		popupAnchor: [1, -34],
		shadowSize: [41, 41]
	});

    var marker = L.marker([lat, lng], {
        icon: icon
    }).addTo(map);

	map.setView([lat, lng], 10);
}

// Ricaricamento della pagina alla chiusura
$("#modals > div button.close").on("click", function() {
    location.reload();
});
</script>';
