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

include_once __DIR__.'/../../core.php';

?>

<link rel="stylesheet" href="<?php echo $rootdir; ?>/modules/mappa/css/app.css">

<!-- Mappa -->
<div id="mappa"></div>

<!-- Menu laterale -->
<div id="menu-filtri" class="open-menu">
    <div style='width:100%;height:50px;background-color:#4d4d4d;padding:8px;font-size:25px;color:white;' class='text-center'>
        <div class="pull-left"><i class='fa fa-forward clickable' id="menu-filtri-toggle"></i></div>
        <b><?php echo tr('Filtri'); ?></b>
    </div>

    <div id="lista-filtri" style="padding:20px 40px;height:637px;overflow:auto;">

        <div class="row">
            <div class="col-md-12" id="geocomplete">
                <input type="hidden" name="lat" id="lat" value="">
                <input type="hidden" name="lng" id="lng" value="">
                {[ "type": "text", "label": "<?php echo tr('Indirizzo'); ?>", "name": "gaddress", "value": "", "extra": "data-geo='formatted_address'", "icon-after":"<button type=\"button\" class=\"btn btn-info\" onclick=\"initGeocomplete();\"><i class=\"fa fa-search\"></i></button>", "icon-before":"<button type=\"button\" class=\"btn btn-info\" onclick=\"getLocation();\"><i class=\"fa fa-map-marker\"></i></button>" ]}
            </div>
        </div>
        <div class="row">
            <div class="col-md-12" id="geocomplete">
                <input type="hidden" name="lat" id="lat" value="">
                <input type="hidden" name="lng" id="lng" value="">
                {[ "type": "number", "label": "<?php echo tr('Nel raggio di'); ?>", "name": "range", "value": "", "decimals": 0, "icon-after":"m" ]}
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <label style='font-size:12pt;'><?php echo tr('Geolocalizzazione attività per anagrafica'); ?></label>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                {[ "type": "select", "name": "idanagrafica", "id": "idanagrafica", "required": 1, "ajax-source": "clienti" ]}
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <label style='font-size:12pt;'><?php echo tr('Geolocalizzazione attività per stato'); ?></label>
                <hr>
            </div>
        </div>

        <div class="row">
<?php
    $rs_stati = $dbo->fetchArray('SELECT * FROM `in_statiintervento`LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND `in_statiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')');

foreach ($rs_stati as $stato) {
    ?>
            <div class="col-md-4">
                <label><?php echo $stato['title']; ?></label>
                <div class="material-switch">
                    <input id="<?php echo $stato['title']; ?>" name="<?php echo $stato['title']; ?>" type="checkbox" checked/>
                    <label for="<?php echo $stato['title']; ?>" class="badge-success"></label>
                </div>
			</div>
<?php
}
?>
        </div>
    </div>
</div>

<script>
    var indirizzi = [];
    var coords = [];
    var circle = "";
    var ROOTDIR = '<?php echo $rootdir; ?>';
    var esri_url = '<?php echo setting('Tile server satellite');?>';
    var esri_attribution = "© Esri © OpenStreetMap Contributors";

    function caricaMappa() {
        const lat = "41.706";
        const lng = "13.228";

        map = L.map("mappa", {
            center: [lat, lng],
            zoom: 6,
            gestureHandling: true
        });

        L.tileLayer("<?php echo setting('Tile server OpenStreetMap'); ?>", {
            maxZoom: 17,
            attribution: "© OpenStreetMap"
        }); 

        var street = L.tileLayer('<?php echo setting('Tile server OpenStreetMap');?>', {
            maxZoom: 17,
            attribution: "© OpenStreetMap",
        }).addTo(map); 

        var satellite = L.tileLayer(esri_url, {id: "mappa", maxZoom: 17, tileSize: 512, zoomOffset: -1, attribution: esri_attribution});

        var baseLayers = {
            "Strade": street,
            "Satellite": satellite
        };

        L.control.layers(baseLayers).addTo(map);
    }

    function initGeocomplete() {
        $.ajax({
            url: "https://nominatim.openstreetmap.org/search.php?q=" + encodeURI(input("gaddress").get()) + "&format=jsonv2",
            type : "GET",
            dataType: "JSON",
            success: function(data){
                input("lat").set(data[0].lat);
                input("lng").set(data[0].lon);
                input("gaddress").set(data[0].display_name);

                var latlng = L.latLng(data[0].lat, data[0].lon);
                map.setView(latlng, 16);

                L.marker(latlng).addTo(map)
                    .bindPopup("You are here").openPopup();

                // Aggiungi cerchio per indicare l'accuratezza
                if (circle) {
                    map.removeLayer(circle);
                }

                circle = L.circle(latlng, {
                    radius: $("#range").val().toEnglish()
                }).addTo(map);

                reload_pointers();
            }
        });
    }

    // Avvio ricerca indirizzo premendo Invio
    $("#gaddress, #range").on("keypress", function(e){
        if(e.which == 13){
            e.preventDefault();
            initGeocomplete();
        }
    });

    // Funzione per ottenere e visualizzare la geolocalizzazione
    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(onLocationFound, onLocationError);
        } else {
            alert("Geolocation is not supported by this browser.");
        }
    }

    function onLocationFound(position) {
        var lat = position.coords.latitude;
        var lng = position.coords.longitude;

        var latlng = L.latLng(lat, lng);
        map.setView(latlng, 16);

        if (circle) {
            map.removeLayer(circle);
        }

        L.marker(latlng).addTo(map)
            .bindPopup("You are here").openPopup();

        // Aggiungi cerchio per indicare l'accuratezza
        circle = L.circle(latlng, {
            //radius: position.coords.accuracy
            radius: $("#range").val().toEnglish()
        }).addTo(map);

        // Invia richiesta per ottenere l'indirizzo
        $.getJSON('https://nominatim.openstreetmap.org/reverse', {
            lat: lat,
            lon: lng,
            format: 'json'
        }, function(data) {
            input("lat").set(data.lat);
            input("lng").set(data.lon);
            input("gaddress").set(data.display_name);

            reload_pointers();
        });
    }

    function onLocationError(error) {
        alert("Error: " + error.message);
    }
</script>

<script type="text/javascript" src="<?php echo $rootdir; ?>/modules/mappa/js/app.js"></script>