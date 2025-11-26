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

use Carbon\Carbon;
use Models\Module;
use Models\Upload;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Sede;
use Modules\Contratti\Contratto;
use Modules\Interventi\Intervento;
use Modules\Ordini\Ordine;
use Modules\Preventivi\Preventivo;
use Modules\Scadenzario\Scadenza;

// Anagrafica
$anagrafica = $anagrafica = Anagrafica::withTrashed()->find($intervento->idanagrafica);

// Sede
if ($intervento->idsede_destinazione) {
    $sede = $dbo->selectOne('an_sedi', '*', ['id' => $intervento->idsede_destinazione]);
} else {
    $sede = $anagrafica ? $anagrafica->toArray() : null;
}

// Referente
$referente = null;
if ($intervento->idreferente) {
    $referente = $dbo->selectOne('an_referenti', '*', ['id' => $intervento->idreferente]);
}

// Contratto
$contratto = null;
$ore_erogate = 0;
$ore_previste = 0;
$perc_ore = 0;
$color = 'danger';
if ($intervento->id_contratto) {
    $contratto = Contratto::find($intervento->id_contratto);
    $ore_erogate = $contratto->interventi->sum('ore_totali');
    $ore_previste = $contratto->getRighe()->where('um', 'ore')->sum('qta');
    $perc_ore = $ore_previste != 0 ? ($ore_erogate * 100) / ($ore_previste ?: 1) : 0;
    if ($perc_ore < 75) {
        $color = 'success';
    } elseif ($perc_ore <= 100) {
        $color = 'warning';
    }
}

// Preventivo
$preventivo = null;
if ($intervento->id_preventivo) {
    $preventivo = Preventivo::find($intervento->id_preventivo);
}

// Ordine
$ordine = null;
if ($intervento->id_ordine) {
    $ordine = Ordine::find($intervento->id_ordine);
}

// Altre attività
$interventi_programmati = Intervento::select('in_interventi.*')
    ->join('in_statiintervento', 'in_interventi.idstatointervento', '=', 'in_statiintervento.id')
    ->where('idanagrafica', $intervento->idanagrafica)
    ->where('idsede_destinazione', $intervento->idsede_destinazione)
    ->where('is_bloccato', '!=', 1)
    ->where('in_interventi.id', '!=', $id_record)
    ->get();

// Insoluti
$insoluti = Scadenza::where('idanagrafica', $intervento->idanagrafica)
    ->whereRaw('co_scadenziario.da_pagare > co_scadenziario.pagato')
    ->whereRaw('co_scadenziario.scadenza < NOW()')
    ->count();

// Logo
$logo = Upload::where('id_module', Module::where('name', 'Anagrafiche')->first()->id)->where('id_record', $intervento->idanagrafica)->where('name', 'Logo azienda')->first()->filename;

$logo = $logo ? base_path_osm().'/files/anagrafiche/'.$logo : App::getPaths()['img'].'/logo_header.png';

echo '
<hr>
<div class="row">
    <div class="col-md-4">
        <div class="card card-outline card-primary shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-vcard"></i> <span style="color: #000;">'.tr('Cliente').'</span></h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <img src="'.$logo.'" class="img-fluid img-thumbnail">
                    </div>

                    <div class="col-md-9">';

// Cliente
echo '
                        <h4 class="mb-2"><b>'.Modules::link('Anagrafiche', $anagrafica->idanagrafica, $anagrafica->ragione_sociale, $anagrafica->ragione_sociale).'</b></h4>

                        <p class="mb-2">
                            '.($sede['nomesede'] ? '<i class="fa fa-building-o text-muted mr-1"></i> '.$sede['nomesede'].'<br>' : '').'
                            <i class="fa fa-map-marker text-muted mr-1"></i> '.$sede['indirizzo'].'<br>
                            <i class="fa fa-map text-muted mr-1"></i> '.$sede['cap'].' - '.$sede['citta'].' ('.$sede['provincia'].')
                        </p>

                        <div class="mt-3">
                            '.($sede['telefono'] ? '<a class="btn btn-light btn-xs mr-1 mb-1" href="tel:'.$sede['telefono'].'" target="_blank"><i class="fa fa-phone text-primary"></i> '.$sede['telefono'].'</a>' : '').'
                            '.($sede['email'] ? '<a class="btn btn-light btn-xs mr-1 mb-1" href="mailto:'.$sede['email'].'"><i class="fa fa-envelope text-primary"></i> '.$sede['email'].'</a>' : '').'
                            '.($referente['nome'] ? '<div class="mt-2"><i class="fa fa-user-o text-muted"></i> '.$referente['nome'].'</div>' : '').'
                            '.($referente['telefono'] ? '<a class="btn btn-light btn-xs mr-1 mb-1" href="tel:'.$referente['telefono'].'" target="_blank"><i class="fa fa-phone text-primary"></i> '.$referente['telefono'].'</a>' : '').'
                            '.($referente['email'] ? '<a class="btn btn-light btn-xs mr-1 mb-1" href="mailto:'.$referente['email'].'"><i class="fa fa-envelope text-primary"></i> '.$referente['email'].'</a>' : '').'
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';

// Panoramica
$show_prezzi = auth_osm()->getUser()['gruppo'] != 'Tecnici' || (auth_osm()->getUser()['gruppo'] == 'Tecnici' && setting('Mostra i prezzi al tecnico'));
$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

$stato = Modules\Interventi\Stato::find($intervento->stato->id);
echo '
    <div class="col-md-4">
        <div class="card card-outline card-primary shadow">

            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fa fa-wrench"></i> <span style="color: #000;">'.tr('Attività').'</span> <strong class="text-primary">'.$intervento->codice.'</strong> <span style="color: #000;">'.tr('del').'</span> <strong>'.Translator::dateToLocale($intervento->data_richiesta).'</strong></h3>
                    <div class="ml-auto">
                        <button type="button" class="btn btn-sm" style="background-color: '.$stato->colore.'; color: <?php echo color_inverse($stato->colore); ?>;">
                            <i class="fa fa-calendar-check-o mr-1"></i> '.$intervento->stato->name.'
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body pt-3">

                <div class="row mb-4">
                    <div class="col-md-3 text-center">
                        <div class="d-flex flex-column">
                            <span class="text-muted small mb-1">'.tr('Sessioni').'</span>
                            <span class="badge badge-light p-2 font-weight-bold"><i class="fa fa-user text-primary mr-1"></i> '.$intervento->sessioni->count().'</span>
                        </div>
                    </div>

                    <div class="col-md-3 text-center">
                        <div class="d-flex flex-column">
                            <span class="text-muted small mb-1">'.tr('Ore').'</span>
                            <span class="badge badge-light p-2 font-weight-bold"><i class="fa fa-hourglass text-primary mr-1"></i> '.Translator::numberToLocale($intervento->sessioni->sum('ore')).'</span>
                        </div>
                    </div>

                    <div class="col-md-3 text-center">
                        <div class="d-flex flex-column">
                            <span class="text-muted small mb-1">'.tr('Distanza').'</span>
                            <span class="badge badge-light p-2 font-weight-bold"><i class="fa fa-truck text-primary mr-1"></i> '.Translator::numberToLocale($intervento->sessioni->sum('km')).' '.tr('km').'</span>
                        </div>
                    </div>

                    <div class="col-md-3 text-center">
                        <div class="d-flex flex-column">
                            <span class="text-muted small mb-1">'.tr('Importo').'</span>
                            <span class="badge badge-light p-2 font-weight-bold"><i class="fa fa-money text-primary mr-1"></i> '.($show_prezzi ? moneyFormat($prezzi_ivati ? $intervento->totale : $intervento->totale_imponibile, 2) : '-').'</span>
                        </div>
                    </div>
                </div>

                <div class="mb-3 status-item insoluti-item">
                    <div class="d-flex align-items-center">
                        <span class="status-icon badge badge-'.($insoluti ? 'danger' : 'success').' mr-2"><i class="fa fa-'.($insoluti ? 'exclamation-circle' : 'check-circle').'"></i></span>
                        <span class="status-text">'.($insoluti ? tr('Sono presenti insoluti') : tr('Non sono presenti insoluti')).'</span>
                    </div>
                </div>

                <div class="mb-3 status-item attivita-item">
                    <div class="d-flex align-items-center mb-2">
                        <span class="status-icon badge badge-'.(count($interventi_programmati) == 0 ? 'success' : 'warning').' mr-2"><i class="fa fa-'.(count($interventi_programmati) == 0 ? 'check-circle' : 'calendar').'"></i></span>
                        <span class="status-text">'.(count($interventi_programmati) == 0 ? tr('Non sono presenti altre attività programmate') : tr('Attività aperte')).'</span>
                    </div>';
if (count($interventi_programmati) != 0) {
    echo ' <div class="readmore mt-1" data-height="60">';
    foreach ($interventi_programmati as $intervento_programmato) {
        $diffTime = (new Carbon($intervento_programmato->data_richiesta))->diffForHumans();
        // Remove the "days" label by using a regex to extract just the number
        $diffTime = preg_replace('/(\d+)\s+giorni?.*/', '$1', $diffTime);
        echo ' <a class="btn btn-outline-primary btn-sm mr-1 mb-1" href="'.base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.$intervento_programmato->id.'" target="_blank"><i class="fa fa-calendar-check-o mr-1"></i>'.$intervento_programmato->codice.' <span class="badge badge-light ml-1">('.$diffTime.')</span></a>';
    }
    echo ' </div>';
}
echo '
                </div>';
// Contratto
if ($contratto) {
    echo '
                <div class="mb-3 status-item contratto-item">
                    <div class="d-flex align-items-center">
                        <span class="status-icon badge badge-info mr-2"><i class="fa fa-book"></i></span>
                        <span class="status-text">'.Modules::link('Contratti', $contratto->id, tr('Contratto num. _NUM_ del _DATA_', ['_NUM_' => '<strong>'.$contratto->numero.'</strong>', '_DATA_' => Translator::dateToLocale($contratto->data_bozza)])).'</span>
                    </div>';
    if ($ore_previste > 0) {
        echo '
                    <div class="mt-1 ml-4">
                        <span class="badge badge-light">'.Translator::numberToLocale($ore_erogate, 2).'/'.$ore_previste.' '.tr('ore').'</span>
                        <div class="progress mt-1" style="height:6px; border-radius:3px;">
                            <div class="progress-bar bg-'.$color.'" style="width:'.$perc_ore.'%"></div>
                        </div>
                    </div>';
    }
    echo '
                </div>';
}

// Preventivo
if ($preventivo) {
    echo '
                <div class="mb-3 status-item preventivo-item">
                    <div class="d-flex align-items-center">
                        <span class="status-icon badge badge-info mr-2"><i class="fa fa-file-text-o"></i></span>
                        <span class="status-text">'.Modules::link('Preventivi', $preventivo->id, tr('Preventivo num. _NUM_ del _DATA_', ['_NUM_' => '<strong>'.$preventivo->numero.'</strong>', '_DATA_' => Translator::dateToLocale($preventivo->data_bozza)])).'</span>
                    </div>
                </div>';
}

// Ordine
if ($ordine) {
    echo '
                <div class="mb-3 status-item ordine-item">
                    <div class="d-flex align-items-center">
                        <span class="status-icon badge badge-info mr-2"><i class="fa fa-shopping-cart"></i></span>
                        <span class="status-text">'.Modules::link('Ordini cliente', $ordine->id, tr('Ordine num. _NUM_ del _DATA_', ['_NUM_' => '<strong>'.$ordine->numero_esterno.'</strong>', '_DATA_' => Translator::dateToLocale($ordine->data)])).'</span>
                    </div>
                </div>';
}
echo '
            </div>
        </div>
    </div>';

// Geolocalizzazione
$anagrafica_cliente = $intervento->anagrafica;
$sede_cliente = $anagrafica_cliente->sedeLegale;
if (!empty($intervento->idsede_destinazione)) {
    $sede_cliente = Sede::find($intervento->idsede_destinazione);
}

$anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));
$sede_azienda = $anagrafica_azienda->sedeLegale;

echo '
    <div class="col-md-4">
        <div class="card card-outline card-primary shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-map"></i> <span style="color: #000;">'.tr('Geolocalizzazione').'</span></h3>
            </div>
            <div class="card-body">';

if (!empty($sede_cliente->gaddress) || (!empty($sede_cliente->lat) && !empty($sede_cliente->lng))) {
    echo '
                <div id="map-edit" style="width: 100%; min-height: 150px; border-radius: 4px; border: 1px solid #eee;"></div>

                <div class="clearfix"></div>
                <div class="mt-3">
                    <div class="row">
                        <div class="col-md-6 mb-2">';
    // Navigazione diretta verso l'indirizzo
    echo '
                            <button class="btn btn-outline-primary btn-sm btn-block" onclick="caricaMappa();">
                                <div class="load"><i class="fa fa-compass mr-1"></i> '.tr('Carica mappa').'</div>
                                <a class="go-to hidden" href="geo://'.$sede['lat'].','.$sede['lng'].'"><i class="fa fa-map mr-1"></i> '.tr('Apri mappa').'</a>
                            </button>
                        </div>

                        <div class="col-md-6 mb-2">';
    // Navigazione diretta verso l'indirizzo
    echo '
                            <a class="btn btn-outline-primary btn-sm btn-block" onclick="calcolaPercorso()">
                                <i class="fa fa-map-signs mr-1"></i> '.tr('Calcola percorso').'
                            </a>
                        </div>
                    </div>
                </div>';
} else {
    echo '
                <div class="alert alert-light text-center mb-3">
                    <i class="fa fa-map-o fa-3x text-muted mb-2"></i>
                    <p class="text-muted">'.tr('Nessuna mappa disponibile').'</p>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-2">';
    // Navigazione diretta verso l'indirizzo
    echo '
                        <a class="btn btn-outline-primary btn-sm btn-block" onclick="calcolaPercorso()">
                            <i class="fa fa-map-signs mr-1"></i> '.tr('Calcola percorso').'
                        </a>
                    </div>

                    <div class="col-md-6 mb-2">';
    // Ricerca diretta su Mappa
    echo '
                        <a class="btn btn-outline-primary btn-sm btn-block" onclick="cercaOpenStreetMap()">
                            <i class="fa fa-map-marker mr-1"></i> '.tr('Cerca su Mappa').'
                        </a>
                    </div>
                </div>';
}

echo '
            </div>
        </div>
    </div>
</div>

<script>
    function modificaPosizione() {
        openModal("'.tr('Modifica posizione').'", "'.$module->fileurl('modals/posizione.php').'?id_module='.$id_module.'&id_record='.$id_record.'");
    }

    function cercaOpenStreetMap() {
        const indirizzo = getIndirizzoAnagrafica();

        const destinazione = (!isNaN(indirizzo[0]) && !isNaN(indirizzo[1])) ? indirizzo[0] + ","+ indirizzo[1] : indirizzo[2];

        // Funzione di fallback per rilevare dispositivi mobili
        function isMobileDevice() {
            // Controlla se isMobile è disponibile
            if (typeof isMobile !== "undefined" && isMobile.any) {
                return true;
            }

            // Fallback usando globals.is_mobile se disponibile
            if (typeof globals !== "undefined" && globals.is_mobile) {
                return true;
            }

            // Fallback usando user agent
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        if (isMobileDevice()) {
            window.open("geo:" + destinazione + "?z=16&q=" + destinazione);
        } else {
            if (!isNaN(indirizzo[0]) && !isNaN(indirizzo[1])) {
                window.open("https://www.openstreetmap.org/?mlat=" + indirizzo[0] + "&mlon=" + indirizzo[1] + "#map=12/" + destinazione + "/" + indirizzo[1]);
            } else {
                window.open("https://www.openstreetmap.org/search?query=" + indirizzo[2] + "#map=12");
            }
        }
    }

    function calcolaPercorso() {
        const indirizzo_partenza = getIndirizzoAzienda();
        const indirizzo_destinazione = getIndirizzoAnagrafica();

        const destinazione = (!isNaN(indirizzo_destinazione[0]) && !isNaN(indirizzo_destinazione[1])) ? indirizzo_destinazione[0] + ","+ indirizzo_destinazione[1] : indirizzo_destinazione[2];

        // Funzione di fallback per rilevare dispositivi mobili
        function isMobileDevice() {
            // Controlla se isMobile è disponibile
            if (typeof isMobile !== "undefined" && isMobile.any) {
                return true;
            }

            // Fallback usando globals.is_mobile se disponibile
            if (typeof globals !== "undefined" && globals.is_mobile) {
                return true;
            }

            // Fallback usando user agent
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        if (isMobileDevice()) {
            window.open("geo:" + destinazione + "?z=16&q=" + destinazione);
        } else {
            window.open("https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=" + indirizzo_partenza + ";" + destinazione);
        }
    }

    function getIndirizzoAzienda() {
        const indirizzo = "'.$sede_azienda->indirizzo.'";
        const citta = "'.$sede_azienda->citta.'";

        const lat = parseFloat("'.$sede_azienda->lat.'");
        const lng = parseFloat("'.$sede_azienda->lng.'");

        if (lat && lng){
            return lat + ","+ lng;
        } else {
            return "";
        }
    }

    function getIndirizzoAnagrafica() {
        const indirizzo = "'.$sede_cliente->indirizzo.'";
        const citta = "'.$sede_cliente->citta.'";

        const lat = parseFloat("'.$sede_cliente->lat.'");
        const lng = parseFloat("'.$sede_cliente->lng.'");

        const indirizzo_default = encodeURI(indirizzo) + "," + encodeURI(citta);

        return [lat, lng, indirizzo_default];
    }

    var maps = {};
    function caricaMappa() {
        // Trova tutti i contenitori di header del modulo (inclusi quelli nei plugin)
        $(".module-header").each(function(index) {
            const $module_header = $(this);
            const $map_container = $module_header.find(".card").eq(2);

            // Genera un ID univoco per ogni mappa basato sul tab container
            const $tab_pane = $module_header.closest(\'.tab-pane\');
            const tab_id = $tab_pane.length ? $tab_pane.attr(\'id\') : \'main\';
            const map_id = "map-edit-" + tab_id;

            // Aggiorna l\'ID del contenitore mappa se necessario
            let $map_element = $map_container.find("#map-edit");
            if ($map_element.length === 0) {
                $map_element = $map_container.find("[id^=\'map-edit\']");
            }

            if ($map_element.length > 0) {
                $map_element.attr("id", map_id);
            } else {
                // Se non trova il contenitore mappa, salta questo header
                return;
            }

            // Ingrandimento area mappa
            $map_container.css("height", "320px");
            alignMaxHeight(".module-header .card");
            $("#" + map_id).css("height", "85%");
            $("#" + map_id).css("border", "none");

            $map_container.find(".load").addClass("hidden");
            $map_container.find(".go-to").removeClass("hidden");

            const lat = parseFloat("'.$sede_cliente->lat.'");
            const lng = parseFloat("'.$sede_cliente->lng.'");

            var container = L.DomUtil.get(map_id);
            if (!container) {
                return; // Salta se il contenitore non esiste
            }

            if(container._leaflet_id != null && maps[map_id]){
                maps[map_id].eachLayer(function (layer) {
                    if(layer instanceof L.Marker) {
                        maps[map_id].removeLayer(layer);
                    }
                });
            } else {
                maps[map_id] = L.map(map_id, {
                    gestureHandling: true
                });

                L.control
                    .fullscreen({
                        position: "topright",
                        title: "'.tr('Vai a schermo intero').'",
                        titleCancel: "'.tr('Esci dalla modalità schermo intero').'",
                        content: null,
                        forceSeparateButton: true,
                        forcePseudoFullscreen: true,
                        fullscreenElement: false
                    }).addTo(maps[map_id]);

                L.tileLayer("'.setting('Tile server OpenStreetMap').'", {
                    maxZoom: 17,
                    attribution: "© OpenStreetMap"
                }).addTo(maps[map_id]);
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
            }).addTo(maps[map_id]);

            maps[map_id].setView([lat, lng], 10);
        });
    }
</script>';
