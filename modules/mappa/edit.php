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

<div class="row">
    <!-- Mappa Container -->
    <div id="map-container" class="col-md-12">
        <div class="card">
            <div class="card-header with-border">
                <h3 class="card-title">
                    <i class="fa fa-map-marker text-primary"></i> <?php echo tr('Mappa'); ?>
                </h3>
                <div class="card-tools pull-right">
                    <button type="button" class="btn btn-tool" id="toggle-filters-btn" title="<?php echo tr('Mostra/nascondi filtri'); ?>">
                        <i class="fa fa-sliders"></i>
                    </button>
                    <button type="button" class="btn btn-tool" id="fullscreen-btn" title="<?php echo tr('Schermo intero'); ?>">
                        <i class="fa fa-expand"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="mappa"></div>

                <!-- Menu filtri sovrapposto -->
                <div id="filters-container">
                    <div id="menu-filtri" class="card card-primary card-outline">
                        <div class="card-header with-border">
                            <h3 class="card-title">
                                <i class="fa fa-filter text-primary"></i> <?php echo tr('Filtri'); ?>
                            </h3>
                            <div class="card-tools pull-right">
                                <button type="button" class="btn btn-tool" id="menu-filtri-toggle" title="<?php echo tr('Chiudi pannello'); ?>">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
            <div class="card-body p-2" id="lista-filtri">
                <div class="section-title">
                    <i class="fa fa-search"></i> <?php echo tr('Ricerca posizione'); ?>
                </div>
                <div class="row">
                    <div class="col-md-12" id="geocomplete">
                        <input type="hidden" name="lat" id="lat" value="">
                        <input type="hidden" name="lng" id="lng" value="">
                        {[ "type": "text", "label": "<?php echo tr('Indirizzo'); ?>", "name": "gaddress", "value": "", "extra": "data-geo='formatted_address' placeholder=\"<?php echo tr('Inserisci un indirizzo da cercare'); ?>\"", "icon-after":"<i class=\"fa fa-search address-icon\" onclick=\"initGeocomplete();\" title=\"<?php echo tr('Cerca indirizzo'); ?>\"></i>", "icon-before":"<i class=\"fa fa-crosshairs location-icon\" onclick=\"getLocation();\" title=\"<?php echo tr('Usa posizione attuale'); ?>\"></i>" ]}
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12" id="geocomplete">
                        <input type="hidden" name="lat" id="lat" value="">
                        <input type="hidden" name="lng" id="lng" value="">
                        {[ "type": "number", "label": "<?php echo tr('Nel raggio di'); ?>", "name": "range", "value": "1000", "min": "100", "max": "50000", "decimals": 0, "icon-after":"m" ]}
                    </div>
                </div>

                <div class="section-title">
                    <i class="fa fa-users"></i> <?php echo tr('Filtro per anagrafica'); ?>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        {[ "type": "select", "name": "idanagrafica", "id": "idanagrafica", "required": 1, "ajax-source": "clienti_mappa", "placeholder": "<?php echo tr('Seleziona un\'opzione'); ?>" ]}
                    </div>
                </div>

                <div class="section-title">
                    <i class="fa fa-tasks"></i> <?php echo tr('Filtro per stato'); ?>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="stati-container">
                        <?php
                            $rs_stati = $dbo->fetchArray('SELECT * FROM `in_statiintervento`LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND `in_statiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')');

foreach ($rs_stati as $stato) {
    ?>
                            <div class="stato-item">
                                <div class="switch-container">
                                    <label class="switch">
                                        <input id="<?php echo $stato['title']; ?>" name="<?php echo $stato['title']; ?>" type="checkbox" checked/>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <label class="stato-label"><?php echo $stato['title']; ?></label>
                            </div>
                        <?php
}
?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var indirizzi = [];
    var coords = [];
    var circle = "";
    var ROOTDIR = '<?php echo $rootdir; ?>';
    var esri_url = '<?php echo setting('Tile server satellite'); ?>';
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

        var street = L.tileLayer('<?php echo setting('Tile server OpenStreetMap'); ?>', {
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

    function calculateZoomForRadius(radius) {
        if (radius >= 50000) return 4;
        if (radius >= 25000) return 5;
        if (radius >= 10000) return 6;
        if (radius >= 5000) return 7;
        if (radius >= 2500) return 8;
        if (radius >= 1000) return 9;
        if (radius >= 500) return 10;
        if (radius >= 250) return 11;
        if (radius >= 100) return 12;
        return 13;
    }

    function updateMapViewWithCircle(latlng, radius) {
        setTimeout(function() {
            if (circle) {
                map.fitBounds(circle.getBounds(), {
                    padding: [-10, -10],
                    maxZoom: 18
                });
            }
        }, 100);
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
                var radius = $("#range").val().toEnglish();

                L.marker(latlng).addTo(map)
                    .bindPopup("You are here").openPopup();


                if (circle) {
                    map.removeLayer(circle);
                }

                circle = L.circle(latlng, {
                    radius: radius
                }).addTo(map);

                updateMapViewWithCircle(latlng, radius);

                reload_pointers();
            }
        });
    }


    $("#gaddress, #range").on("keypress", function(e){
        if(e.which == 13){
            e.preventDefault();
            initGeocomplete();
        }
    });

    $("#gaddress").on("blur", function(){
        if($("#gaddress").val().trim() !== ""){
            initGeocomplete();
        }
    });

    $("#range").on("blur input", function(){
        updateCircleRadius();
    });

    function updateCircleRadius() {
        if (circle && $("#lat").val() && $("#lng").val()) {
            var lat = $("#lat").val();
            var lng = $("#lng").val();
            var radius = $("#range").val().toEnglish();
            var latlng = L.latLng(lat, lng);

            map.removeLayer(circle);

            circle = L.circle(latlng, {
                radius: radius
            }).addTo(map);

            updateMapViewWithCircle(latlng, radius);

            if (typeof reload_pointers === 'function') {
                reload_pointers();
            }
        }
    }

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
        var radius = $("#range").val().toEnglish();

        if (circle) {
            map.removeLayer(circle);
        }

        L.marker(latlng).addTo(map)
            .bindPopup("You are here").openPopup();

        circle = L.circle(latlng, {
            radius: radius
        }).addTo(map);

        updateMapViewWithCircle(latlng, radius);

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