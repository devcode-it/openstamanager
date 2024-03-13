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
        <b>Filtri</b>
    </div>

    <div id="lista-filtri" style="padding:20px 40px;height:637px;overflow:auto;">

        <div class="row">
            <div class="col-md-12">
                <label style='font-size:12pt;'>Geolocalizzazione attività per anagrafica</label>
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
                <label style='font-size:12pt;'>Geolocalizzazione attività per stato</label>
                <hr>
            </div>
        </div>

        <div class="row">
<?php
    $rs_stati = $dbo->fetchArray('SELECT * FROM `in_statiintervento`LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND `in_statiintervento_lang`.`id_lang` = '.prepare(\App::getLang()).')');

foreach ($rs_stati as $stato) {
    ?>
            <div class="col-md-4">
                <label><?php echo $stato['name']; ?></label>
                <div class="material-switch">
                    <input id="<?php echo $stato['name']; ?>" name="<?php echo $stato['name']; ?>" type="checkbox" checked/>
                    <label for="<?php echo $stato['name']; ?>" class="label-success"></label>
                </div>
			</div>
<?php
}
?>
        </div>
    </div>
</div>

<script>
    var ROOTDIR = '<?php echo $rootdir; ?>';

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
        }).addTo(map); 
    }
</script>

<script type="text/javascript" src="<?php echo $rootdir; ?>/modules/mappa/js/app.js"></script>