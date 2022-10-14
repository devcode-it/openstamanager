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

<link rel="stylesheet" href="<?=$rootdir?>/modules/mappa/css/app.css">

<?php
if(!empty(setting('Google Maps API key'))){
?>

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
                {[ "type": "select", "name": "idanagrafica", "id": "idanagrafica", "required": 1, "ajax-source": "clienti_fornitori" ]}
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
    $rs_stati = $dbo->fetchArray("SELECT * FROM in_statiintervento");

    foreach($rs_stati AS $stato){
?>
            <div class="col-md-4">
                <label><?=$stato['descrizione']?></label>
                <div class="material-switch">
                    <input id="<?=$stato['descrizione']?>" name="<?=$stato['descrizione']?>" type="checkbox" checked/>
                    <label for="<?=$stato['descrizione']?>" class="label-success"></label>
                </div>
			</div>
<?php
    }
?>
        </div>
    </div>
</div>

<script>
    var ROOTDIR = '<?=$rootdir?>';

    function calcolaPercorso(indirizzo) {
        window.open("https://www.google.com/maps/dir/?api=1&destination=" + indirizzo);
    }
</script>

<script type="text/javascript" src="<?=$rootdir?>/modules/mappa/js/app.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?callback=initialize&key=<?=setting('Google Maps API key')?>&libraries=&v=weekly" async></script>

<?php
}else{
    echo '
    <div class="alert alert-info">
        '.Modules::link('Impostazioni', null, tr('Per abilitare la visualizzazione della mappa, inserire la Google Maps API Key nella scheda Impostazioni'), true, null, true, null, '&search=Google Maps API key').'.
    </div>';
}
?>