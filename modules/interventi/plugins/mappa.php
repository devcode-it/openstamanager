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

    if(get('op')=='getmappa'){
      $nome = 'Ricarica mappa';
    }
    else{
      $nome = 'Visualizza mappa';
    }

    echo "<center><a onclick=\"location.href='".$rootdir."/controller.php?id_module=".$id_module."&op=getmappa&r='+Math.random()+'#tab_".Plugins::get('Mostra su mappa')['id']."';\" id='button' class='btn btn-primary btn-lg btn-large'>".$nome."</a></center>";
    echo "<br>";


    if(get('op')=='getmappa'){
        $current_module = Modules::get($id_module);
        $total = Util\Query::readQuery($current_module);
        $module_query = Modules::replaceAdditionals($id_module, $total['query']);

        $search_filters = array();

        if( is_array( $_SESSION['module_'.$id_module] ) ){
            foreach( $_SESSION['module_'.$id_module] as $field_name => $field_value ){
                if( $field_value != '' && $field_name != 'selected' && $field_name != 'id_segment'){
                    $field_name = str_replace( "search_", "", $field_name );
                    $field_name = str_replace( "__", " ", $field_name );
                    $field_name = str_replace( "-", " ", $field_name );
                    array_push( $search_filters, "`".$field_name."` LIKE \"%".$field_value."%\"" );
                }
            }
        }
        if( sizeof($search_filters) > 0 ){
            $module_query = str_replace( "2=2", "2=2 AND (".implode( " AND ", $search_filters ).") ", $module_query);
        }

        $rs1 = $dbo->fetchArray( $module_query );

        //marker svg
        if (!file_exists($docroot.'/assets/dist/img/leaflet/place-marker.svg')) {
            throw new Exception("File not found: " . $docroot.'/assets/dist/img/leaflet/place-marker.svg');
        }
    
        $svgContent = file_get_contents($docroot.'/assets/dist/img/leaflet/place-marker.svg');
        if ($svgContent === false) {
            throw new Exception("Error reading file: " . $docroot.'/assets/dist/img/leaflet/place-marker.svg');
        }    
        $stringa_descrizioni = "";
        $stringa_content = "";
        $color = "";
        $lat = "";
        $lng = "";
        for( $i=0; $i<sizeof($rs1); $i++ ){//elenco delle righe
            $val = html_entity_decode( $rs1[$i]['idanagrafica'] );
            $id_sede = $dbo->selectOne('in_interventi', '*', ['id' => $rs1[$i]['id']])['idsede_destinazione'];
            if($id_sede){
                $query = "SELECT *, nomesede AS ragione_sociale FROM an_sedi WHERE id='".$id_sede."'";
                $rs=$dbo->fetchArray($query);
            }else{
                $query="SELECT *, ragione_sociale FROM an_anagrafiche WHERE idanagrafica='".$val."'";
                $rs=$dbo->fetchArray($query);
            }

            if($rs[0]['lat'] && $rs[0]['lng']){
                $color .= "'".$rs1[$i]['_bg_']."',";
                $lat .= "'".$rs[0]['lat']."',";
                $lng .= "'".$rs[0]['lng']."',";
                $stringa_descrizioni .= "'".str_replace("'", " ", $rs[0]['ragione_sociale'])."',";

                $stringa_content .= "'";
                    
                $stringa_content .= str_replace("'", "", "<big><b>".$rs[0]['ragione_sociale']."</b></big><br>".$rs[0]['indirizzo'].", ".$rs[0]['cap'].", ".$rs[0]['citta']." (".$rs[0]['provincia'].")".($rs[0]['telefono']!=''? "<br><i class=\"fa fa-phone\"></i> &nbsp;".$rs[0]['telefono'] : "").($rs[0]['email']!=''? "<br><i class=\"fa fa-envelope\"></i> &nbsp;".$rs[0]['email'] : "")."<br>");
                

                $altri_interventi = $dbo->fetchArray('SELECT * FROM in_interventi WHERE idsede_destinazione='.prepare($id_sede).' AND idanagrafica='.prepare($val).' AND id IN ('.implode(',', array_column($rs1, 'id')).')');
                for($j=0;$j<sizeof($altri_interventi);$j++){
                    $stringa_content .= str_replace("'", "", "<br> <a href=\"".$rootdir."/editor.php?id_module=".$id_module."&id_record=".$altri_interventi[$j]['id']."\">Intervento numero: ".$altri_interventi[$j]['codice']." del ".date('d/m/Y', strtotime($altri_interventi[$j]['data_richiesta']))."</a>");
                }
                
                $stringa_content .= "',";
            }

        }

        echo "<div id='mappa'></div>";
        $stringa_descrizioni = substr($stringa_descrizioni,0,-1);
        $stringa_content = substr($stringa_content,0,-1);
        $lat = substr($lat,0,-1);
        $lng = substr($lng,0,-1);
?>

<link rel="stylesheet" href="<?php echo $rootdir; ?>/modules/mappa/css/app.css">

<!-- Mappa -->
<script>
    var ROOTDIR = '<?php echo $rootdir; ?>';
    
    setTimeout(function(){
        caricaMap();
    }, 2000);

    function caricaMap(){
        var lat = [<?php echo $lat; ?>];
        var lng = [<?php echo $lng; ?>];
        var color = [<?php echo $color; ?>];
        var descrizioni = [<?php echo $stringa_descrizioni; ?>];
        var content = [<?php echo $stringa_content; ?>];
        var svgContent = `<?php echo $svgContent;?>`;
        const lt = "41.706";
        const ln = "13.228";
        var container = L.DomUtil.get("mappa");

        if(container._leaflet_id != null){ 
            map.eachLayer(function (layer) {
                if(layer instanceof L.Marker) {
                    map.removeLayer(layer);
                }
            });
        } else {
            map = L.map("mappa", {
                center: [lt, ln],
                zoom: 6,
                gestureHandling: true
            });

            L.tileLayer("<?php echo setting('Tile server OpenStreetMap'); ?>", {
                maxZoom: 17,
                attribution: "© OpenStreetMap"
            }).addTo(map); 

            var markerArray = [];
            if( document.getElementById('mappa') ){
                for (let i = 0; i < lng.length; i++) {
                    let svgIcon = L.divIcon({
                        html: svgContent.replace('fill="#cccccc"','fill="' + color[i] + '"'),
                        className: '',
                        shadowUrl:globals.rootdir + "/assets/dist/img/leaflet/marker-shadow.png",
                        iconSize: [45, 61],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    });

                    markerArray.push(L.marker([ lat[i], lng[i]], {
                        icon: svgIcon,
                    }));

                    setTimeout(function() {
                        L.marker([ lat[i], lng[i]], {
                            icon: svgIcon,
                        }).bindTooltip(descrizioni[i], 
                            {
                                permanent: false, 
                                direction: 'right'
                            }
                        ).bindPopup(content[i]
                        ).addTo(map);
                    }, 200 * i);
                }
                var group = L.featureGroup(markerArray).addTo(map);
                map.fitBounds(group.getBounds());
            }
        }
    }
</script>

<?php
}