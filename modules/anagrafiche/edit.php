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

use Carbon\Carbon;
use Models\Locale;
use Models\Module;
use Models\Plugin;
use Modules\Anagrafiche\Anagrafica;
use Modules\Banche\Banca;

include_once __DIR__.'/../../core.php';

$id_cliente_finale = $record['id_cliente_finale'] ?? null;

$is_fornitore = in_array($id_fornitore, $tipi_anagrafica);
$is_cliente = in_array($id_cliente, $tipi_anagrafica);
$is_tecnico = in_array($id_tecnico, $tipi_anagrafica);
$is_agente = in_array($id_agente, $tipi_anagrafica);
$is_azienda = in_array($id_azienda, $tipi_anagrafica);

if (!$is_cliente && !$is_fornitore && !$is_azienda && $is_tecnico) {
    $ignore = Plugin::where('name', 'Sedi aggiuntive')
        ->orWhere('name', 'Referenti')
        ->orWhere('name', 'Dichiarazioni d\'intento')
        ->get();

    foreach ($ignore as $plugin) {
        echo '
        <script>
            $("li.btn-default.nav-item:has(#link-tab_'.$plugin->id.')").addClass("disabled");
        </script>';
    }
}

if (!$is_cliente) {
    $ignore = Plugin::where('name', 'Impianti del cliente')
        ->get();

    foreach ($ignore as $plugin) {
        echo '
        <script>
            $("li.btn-default.nav-item:has(#link-tab_'.$plugin->id.')").addClass("disabled");
        </script>';
    }
}

$nazione_anagrafica = $anagrafica->sedeLegale->nazione;

// Avvisi problemi scheda anagrafica
$problemi_anagrafica = [];
if ($is_cliente && empty($record['id_conto_cliente'])) {
    $problemi_anagrafica[] = '<div class="row" style="margin-bottom:5px;"><div class="col-md-3">'.tr('Piano dei conti mancante per il cliente').'</div><button type="button" class="btn btn-xs btn-success" onclick="risolviConto(\'cliente\')"><i class="fa fa-cog"></i> '.tr('Risolvi').'</button></div>';
}

if ($is_fornitore && empty($record['id_conto_fornitore'])) {
    $problemi_anagrafica[] = '<div class="row"><div class="col-md-3">'.tr('Piano dei conti mancante per il fornitore').'</div><button type="button" class="btn btn-xs btn-success" onclick="risolviConto(\'fornitore\')"><i class="fa fa-cog"></i> '.tr('Risolvi').'</button></div>';
}

if (count($problemi_anagrafica) > 0) {
    echo '<div class="alert alert-warning"><i class="fa fa-warning"></i> '.tr('ATTENZIONE: <br>_CAMPI_', [
        '_CAMPI_' => implode('', $problemi_anagrafica),
    ]).'</div>';
}

?>

<form action="" method="post" id="edit-form" enctype="multipart/form-data">
    <fieldset>
        <input type="hidden" name="backto" value="record-edit">
        <input type="hidden" name="op" value="update">

        <!-- DATI ANAGRAFICI -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><?php echo tr('Dati anagrafici'); ?></h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        {[ "type": "text", "label": "<?php echo tr('Denominazione'); ?>", "name": "ragione_sociale", "required": 1, "value": "$ragione_sociale$", "extra": "" ]}
                    </div>
                    <div class="col-md-2">
                        {[ "type": "text", "label": "<?php echo tr('Codice anagrafica'); ?>", "name": "codice", "required": 1, "class": "text-center alphanumeric-mask", "value": "$codice$", "maxlength": 20, "validation": "codice" ]}
                    </div>

                    <div class="col-md-2">
                        <?php
                        $help_codice_destinatario = tr("Per impostare il codice specificare prima il campo '_NATION_' dell'anagrafica", [
                            '_NATION_' => '<b>Nazione</b>',
                        ]).':<br><br><ul>
                                <li>'.tr('Azienda (B2B) - Codice Destinatario, 7 caratteri').'</li>
                                <li>'.tr('Ente pubblico (B2G/PA) - Codice Univoco Ufficio (www.indicepa.gov.it), 6 caratteri').'</li>
                                <li>'.tr('Privato (B2C) - viene utilizzato il Codice Fiscale').'</li></ul>
                            '.tr('Se non si conosce il codice destinatario lasciare vuoto il campo, e verrà applicato in automatico quello previsto di default dal sistema (\'0000000\', \'999999\', \'XXXXXXX\')').'.';

                        if (in_array($id_azienda, $tipi_anagrafica)) {
                            $help_codice_destinatario .= ' <br><b>'.tr('Attenzione').': </b>'.tr("Non è necessario comunicare il proprio codice destinatario ai fornitori in quanto è sufficiente che questo sia registrato all'interno portale del Sistema Di Interscambio dell'Agenzia Entrate (SDI) (ivaservizi.agenziaentrate.gov.it)").'.';
                        }
                        ?>
                        {[ "type": "text", "label": "<?php echo ($record['tipo'] == 'Ente pubblico') ? tr('Codice unico ufficio') : tr('Codice destinatario'); ?>", "name": "codice_destinatario", "required": 0, "class": "text-center text-uppercase alphanumeric-mask", "value": "$codice_destinatario$", "maxlength": <?php echo ($record['tipo'] == 'Ente pubblico') ? '6' : '7'; ?>, "help": "<?php echo tr($help_codice_destinatario); ?>", "readonly": "<?php echo intval($nazione_anagrafica ? !(($nazione_anagrafica->iso2 === 'IT') || ($nazione_anagrafica->iso2 === 'SM')) : 0); ?>", "validation": "codice_intermediario" ]}
                    </div>

                    <div class="col-md-2">
                        {[ "type": "select", "label": "<?php echo tr('Tipologia'); ?>", "name": "tipo", "values": "list=\"Azienda\": \"<?php echo tr('Azienda'); ?>\", \"Ente pubblico\": \"<?php echo tr('Ente pubblico'); ?>\" <?php echo $anagrafica->isAzienda() ? '' : ',\"Privato\":\"'.tr('Privato').'\"'; ?>", "value": "$tipo$", "placeholder": "<?php echo tr('Non specificato'); ?>" ]}
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        {[ "type": "text", "label": "<?php echo tr('Cognome'); ?>", "name": "cognome", "required": 0, "value": "$cognome$", "extra": "" ]}
                    </div>

                    <div class="col-md-3">
                        {[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 0, "value": "$nome$", "extra": "" ]}
                    </div>
                    <div class="col-md-4">
                        {[ "type": "image", "label": "<?php echo $anagrafica->isAzienda() ? tr('Logo stampe') : tr('Logo azienda'); ?>", "name": "logo", "class": "img-thumbnail img-fluid", "value": "<?php echo $anagrafica->image; ?>", "accept": "image/x-png,image/gif,image/jpeg", "help": "<?php echo tr('Formato consigliato: JPG, PNG o GIF - Risoluzione consigliata: 302x111 pixel'); ?>" ]}
                    </div>
                    
                </div>

               <!-- RIGA PER LE ANAGRAFICHE CON TIPOLOGIA 'PRIVATO' -->
               <?php if ($record['tipo'] == 'Privato') { ?>
               <div class="row">
                   <div class="col-md-4">
                       {[ "type": "text", "label": "<?php echo tr('Luogo di nascita'); ?>", "name": "luogo_nascita", "value": "$luogo_nascita$" ]}
                   </div>

                   <div class="col-md-4">
                       {[ "type": "date", "label": "<?php echo tr('Data di nascita'); ?>", "name": "data_nascita", "value": "$data_nascita$" ]}
                   </div>

                   <div class="col-md-4">
                       {[ "type": "select", "label": "<?php echo tr('Sesso'); ?>", "name": "sesso", "values": "list=\"\": \"Non specificato\", \"M\": \"<?php echo tr('Uomo'); ?>\", \"F\": \"<?php echo tr('Donna'); ?>\"", "value": "$sesso$" ]}
                   </div>
               </div>
               <?php } ?>

               <div class="row">
                   <div class="col-md-3">
                       {[ "type": "text", "label": "<?php echo tr('Partita IVA'); ?>", "maxlength": 16, "name": "p_iva", "class": "text-center alphanumeric-mask text-uppercase", "value": "$p_iva$", "validation": "partita_iva" ]}
                   </div>

                   <div class="col-md-3">
                       {[ "type": "text", "label": "<?php echo tr('Codice fiscale'); ?>", "maxlength": 16, "name": "codice_fiscale", "class": "text-center alphanumeric-mask text-uppercase", "value": "$codice_fiscale$", "validation": "codice_fiscale" ]}
                   </div>

                   <div class="col-md-3">
                       {[ "type": "email", "label": "<?php echo tr('PEC'); ?>", "name": "pec", "placeholder":"pec@dominio.ext", "value": "$pec$", "validation": "email" ]}
                   </div>

                   <div class="col-md-3">
                       {[ "type": "text", "label": "<?php echo tr('Sito web'); ?>", "name": "sito_web", "placeholder":"www.dominio.ext", "value": "$sito_web$", "icon-before": "<i class='fa fa-globe'></i>" ]}
                   </div>
               </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo tr('Sede legale'); ?></h3>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-10">
                                {[ "type": "text", "label": "<?php echo tr('Indirizzo'); ?>", "name": "indirizzo", "value": "$indirizzo$" ]}
                            </div>

                            <div class="col-md-2">
                                {[ "type": "text", "label": "<?php echo tr('C.A.P.'); ?>", "name": "cap", "maxlength": 6, "class": "text-center", "value": "$cap$" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                {[ "type": "text", "label": "<?php echo tr('Città'); ?>", "name": "citta", "class": "text-center", "value": "$citta$" ]}
                            </div>

                            <div class="col-md-4">
                                {[ "type": "text", "label": "<?php echo tr('Provincia'); ?>", "name": "provincia", "class": "text-center provincia-mask text-uppercase", "value": "$provincia$", "extra": "onkeyup=\"this.value = this.value.toUpperCase();\"" ]}
                            </div>

                            <div class="col-md-4">
                                {[ "type": "select", "label": "<?php echo tr('Nazione'); ?>", "name": "id_nazione", "value": "$id_nazione$", "ajax-source": "nazioni" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                {[ "type": "telefono", "label": "<?php echo tr('Telefono'); ?>", "name": "telefono", "class": "text-center", "value": "$telefono$" ]}
                            </div>

                            <div class="col-md-4">
                                {[ "type": "telefono", "label": "<?php echo tr('Cellulare'); ?>", "name": "cellulare", "class": "text-center", "value": "$cellulare$", "icon-after": "<?php echo !empty($record['cellulare']) ? "<btn class='clickable' onclick=sendWhatsAppMessage(".prepare($record['cellulare']).") ><i class='fa fa-whatsapp tip' title='".((str_starts_with((string) $record['cellulare'], '+')) ? substr((string) $record['cellulare'], 1) : $record['cellulare'])."'></i>" : "<i class='fa fa-whatsapp tip' title='".tr('Compila il campo per utilizzare WhatsApp.')."'></i>"; ?>" ]}
                            </div>

                            <div class="col-md-4">
                                {[ "type": "email", "label": "<?php echo tr('Email'); ?>", "name": "email", "placeholder": "casella@dominio.ext", "value": "$email$", "validation": "email" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                {[ "type": "text", "label": "<?php echo tr('Fax'); ?>", "name": "fax", "class": "text-center", "value": "$fax$", "icon-before": "<i class='fa fa-fax'></i>" ]}
                            </div>

                            <div class="col-md-4">
                                {[ "type": "select", "label": "<?php echo tr('Zona'); ?>", "name": "id_zona", "values": "query=SELECT id, CONCAT_WS( ' - ', nome, descrizione) AS descrizione FROM an_zone ORDER BY descrizione ASC", "value": "$id_zona$", "placeholder": "<?php echo tr('Nessuna zona'); ?>", "icon-after": "add|<?php echo Module::where('name', 'Zone')->first()->id; ?>" ]}
                            </div>

                            <div class="col-md-4">
                                {[ "type": "number", "label": "<?php echo tr('Distanza'); ?>", "name": "km", "decimals": "qta", "class": "text-center", "value": "$km$", "icon-after": "Km" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                {[ "type": "checkbox", "label": "<?php echo tr('Opt-in per newsletter'); ?>", "name": "enable_newsletter", "value": "<?php echo $record['enable_newsletter']; ?>", "help": "<?php echo tr('Blocco per l\'invio delle email.'); ?>" ]}
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <?php
            $sede_cliente = $anagrafica->sedeLegale;

$anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));
$sede_azienda = $anagrafica_azienda->sedeLegale;

echo '
            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"> '.tr('Geolocalizzazione').'</h3>
                    </div>
                    <div class="card-body">';

// Area caricamento mappa
echo '
                        <div id="map-edit" style="width: 100%;"></div>

                        <div class="clearfix"></div>
                        <br>';

if (!empty($sede_cliente->gaddress) || (!empty($sede_cliente->lat) && !empty($sede_cliente->lng))) {
    // Modifica manuale delle informazioni
    echo '
                        <a class="btn btn-info btn-block" onclick="modificaPosizione()">
                            <i class="fa fa-map"></i> '.tr('Aggiorna posizione').'
                        </a>';
} else {
    // Definizione manuale delle informazioni
    echo '
                        <a class="btn btn-primary btn-block" onclick="modificaPosizione()">
                            <i class="fa fa-map"></i> '.tr('Definisci posizione').'
                        </a>';
}

// Navigazione diretta verso l'indirizzo
echo '
                        <a class="btn btn-info btn-block '.((empty($sede_cliente->lat) && empty($sede_cliente->lng)) ? 'disabled' : '').'" onclick="$(\'#map-edit\').height(235); caricaMappa(); $(this).hide();">
                            <i class="fa fa-compass"></i> '.tr('Carica mappa').'
                        </a>';

// Navigazione diretta verso l'indirizzo
echo '
                        <a class="btn btn-info btn-block '.(($anagrafica->isAzienda() || (empty($sede_cliente->lat) || empty($sede_cliente->lng)) || (empty($sede_azienda->lat) || empty($sede_azienda->lng))) ? 'disabled' : '').'" onclick="calcolaPercorso()">
                            <i class="fa fa-map-signs"></i> '.tr('Calcola percorso').'
                            '.((!empty($sede_cliente->lat) && !empty($sede_azienda->lat)) ? tr('(GPS)') : '').'
                        </a>';

// Ricerca diretta su Mappa
echo '
                        <a class="btn btn-info btn-block" onclick="cercaOpenStreetMap()">
                            <i class="fa fa-map-marker"></i> '.tr('Cerca su Mappa').'
                            '.((!empty($sede_cliente->lat)) ? tr(' (GPS)') : '').'
                        </a>';

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

                return lat + "," + lng;
            }

            function getIndirizzoAnagrafica() {
                const indirizzo = $("#indirizzo").val();
                const citta = $("#citta").val();

                const lat = parseFloat("'.$sede_cliente->lat.'");
                const lng = parseFloat("'.$sede_cliente->lng.'");

                const indirizzo_default = encodeURI(indirizzo) + "," + encodeURI(citta);

                return [lat, lng, indirizzo_default];
            }

            var map = null;
            function caricaMappa() {
                const lat = parseFloat("'.$sede_cliente->lat.'");
                const lng = parseFloat("'.$sede_cliente->lng.'");

                if (!lat || !lng){
                    Swal.fire("'.tr('Errore').'", "'.tr('La posizione non è stata definita. Impossibile caricare la mappa.').'", "error");
                    return false;
                }

                var container = L.DomUtil.get("map-edit");
                if(container._leaflet_id != null){
                    map.eachLayer(function (layer) {
                        if(layer instanceof L.Marker) {
                            map.removeLayer(layer);
                        }
                    });
                } else {
                    map = L.map("map-edit", {
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

                L.control
                .fullscreen({
                    position: "topright",
                    title: "'.tr('Vai a schermo intero').'",
                    titleCancel: "'.tr('Esci dalla modalità schermo intero').'",
                    content: null,
                    forceSeparateButton: true,
                    forcePseudoFullscreen: true,
                    fullscreenElement: false
                }).addTo(map);

                map.setView([lat, lng], 14);
            }

            function risolviConto(tipo){
                $.ajax({
                    url: globals.rootdir + "/actions.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        id_module: globals.id_module,
                        id_record: globals.id_record,
                        tipo: tipo,
                        op: "risolvi_conto",
                    },
                    success: function (response) {
                        location.reload();
                    },
                    error: function() {
                        location.reload();
                    }
                });
            }


        </script>';

if ($is_cliente or $is_fornitore or $is_tecnico) {
    echo '

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">'.tr('Informazioni per tipo di anagrafica').'</h3>
            </div>

            <div class="card-body">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs nav-justified">
                        <li class="nav-item '.($is_cliente ? 'active"' : '"').'><a href="#cliente" data-card-widget="tab" class="nav-link '.($is_cliente ? '' : 'disabled').'" '.($is_cliente ? '' : 'disabled').'>'.tr('Cliente').'</a></li>

                        <li class="nav-item '.(!$is_cliente && $is_fornitore ? 'active"' : '"').'><a href="#fornitore" data-card-widget="tab" class="nav-link '.($is_fornitore ? '' : 'disabled').'" '.($is_fornitore ? '' : 'disabled').'>'.tr('Fornitore').'</a></li>

                        <li class="nav-item"><a href="#cliente_fornitore" data-card-widget="tab" class="nav-link '.($is_cliente || $is_fornitore ? '' : 'disabled').'" '.($is_cliente || $is_fornitore ? '' : 'disabled').'>'.tr('Cliente e fornitore').'</a></li>

                        <li class="nav-item'.(!$is_cliente && !$is_fornitore && $is_tecnico ? 'active"' : '"').'><a href="#tecnico" data-card-widget="tab" class="nav-link '.($is_tecnico ? '' : 'disabled').'" '.($is_tecnico ? '' : 'disabled').'>'.tr('Tecnico').'</a></li>
                    </ul>

                    <div class="tab-content '.(!$is_cliente && !$is_fornitore && !$is_tecnico ? 'hide' : '').'">
                        <div class="tab-pane '.(!$is_cliente && !$is_fornitore ? ' hide' : '').'" id="cliente_fornitore">
                            <div class="row">
                                <div class="col-md-3">
                                    {[ "type": "checkbox", "label": "'.tr('Abilitare lo split payment').'", "name": "split_payment", "value": "$split_payment$", "help": "'.tr('Lo split payment è disponibile per le anagrafiche di tipologia \"Ente pubblico\" o \"Azienda\" (iscritta al Dipartimento Finanze - Scissione dei pagamenti) ed <strong>&egrave; obbligatorio</strong> per:<ul><li>Stato;</li><li>organi statali ancorch&eacute; dotati di personalit&agrave; giuridica;</li><li>enti pubblici territoriali e dei consorzi tra essi costituiti;</li><li>Camere di Commercio;</li><li>Istituti universitari;</li><li>ASL e degli enti ospedalieri;</li><li>enti pubblici di ricovero e cura aventi prevalente carattere scientifico;</li><li>enti pubblici di assistenza e beneficienza;</li><li>enti di previdenza;</li><li>consorzi tra questi costituiti.</li></ul>').'", "placeholder": "'.tr('Split payment').'", "extra" : "'.($record['tipo'] == 'Ente pubblico' || $record['tipo'] == 'Azienda' ? '' : 'disabled').'" ]}
                                </div>

                                <div class="col-md-6">
                                    {[ "type": "text", "label": "'.tr('Dicitura fissa in fattura').'", "name": "dicitura_fissa_fattura", "value": "$dicitura_fissa_fattura$" ]}
                                </div>

                                <div class="col-md-3">
                                        {[ "type": "select", "label": "'.tr('Relazione').'", "name": "id_relazione", "ajax-source": "relazioni", "value": "$id_relazione$", "icon-after": "add|'.Module::where('name', 'Relazioni')->first()->id.'" ]}
                                </div>
                            </div>

                            <div class="row">

                            </div>';

    $banche = Banca::where('id_anagrafica', $anagrafica->id)->get();
    $banca_predefinita = $banche->first(fn ($item) => !empty($item['predefined']));
    $modulo_banche = Module::where('name', 'Banche')->first()->id;
    if (!$banche->isEmpty()) {
        echo '
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="'.base_path_osm().'/editor.php?id_module='.$modulo_banche.'&id_record='.$banca_predefinita->id.'">
                                        '.tr("Visualizza la banca predefinita per l'Anagrafica").' <i class="fa fa-external-link"></i>
                                    </a>
                                </div>

                                <div class="col-md-6">
                                    <a href="'.base_path_osm().'/controller.php?id_module='.$modulo_banche.'&search_Anagrafica='.rawurlencode((string) $anagrafica['ragione_sociale']).'">
                                        '.tr("Visualizza le banche disponibili per l'Anagrafica").' <i class="fa fa-external-link"></i>
                                    </a>
                                </div>
                            </div>';
    } else {
        echo '
                            <div class="alert alert-info">
                                '.tr('Non sono presenti banche per l\'anagrafica').'... '.Modules::link('Banche', null, tr('Creane una')).'
                            </div>';
    }

    echo '
                        </div>

                        <div class="tab-pane '.(!$is_cliente ? 'hide' : 'active').'" id="cliente">
                            <div class="row">
                                <div class="col-md-6">
                                        {[ "type": "select", "label": "'.tr('Provenienza').'", "name": "id_provenienza", "ajax-source": "provenienze", "value": "$id_provenienza$", "icon-after": "add|'.Module::where('name', 'Provenienze')->first()->id.'" ]}
                                </div>

                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Pagamento predefinito').'", "name": "id_pagamento_vendite", "values": "query=SELECT `co_pagamenti`.`id`, `co_pagamenti_lang`.`title` AS descrizione FROM `co_pagamenti` LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).') GROUP BY `descrizione` ORDER BY `descrizione` ASC", "value": "$id_pagamento_vendite$" ]}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Banca predefinita azienda per accrediti').'", "name": "id_banca_vendite", "ajax-source": "banche", "select-options": '.json_encode(['id_anagrafica' => $anagrafica_azienda->id]).', "value": "$id_banca_vendite$", "help": "'.tr("Banca predefinita dell'Azienda su cui accreditare i pagamenti").'" ]}
                                </div>

                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Iva predefinita').'", "name": "id_iva_vendite", "ajax-source": "iva", "value": "$id_iva_vendite$" ]}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr("Ritenuta d'acconto predefinita").'", "name": "id_ritenuta_acconto_vendite", "values": "query=SELECT id, descrizione FROM co_ritenuta_acconto ORDER BY descrizione ASC", "value": "$id_ritenuta_acconto_vendite$" ]}
                                </div>

                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Piano di sconto/magg. su articoli').'", "name": "id_piano_sconto_vendite", "values": "query=SELECT id, nome AS descrizione FROM mg_piani_sconto ORDER BY nome ASC", "value": "$id_piano_sconto_vendite$" ]}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Indirizzo di fatturazione').'", "name": "id_sede_fatturazione", "values": "query=SELECT id, IF(citta = \'\', nome_sede, CONCAT_WS(\', \', nome_sede, citta)) AS descrizione FROM an_sedi WHERE id_anagrafica='.prepare($id_record).' UNION SELECT \'0\' AS id, \'Sede legale\' AS descrizione ORDER BY descrizione", "value": "$id_sede_fatturazione$"  ]}
                                </div>

                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Agente principale').'", "name": "id_agente", "values": "query=SELECT `an_anagrafiche`.`id` AS id, IF(deleted_at IS NOT NULL, CONCAT(`ragione_sociale`, \' (Eliminato)\'), `ragione_sociale` ) AS descrizione FROM `an_anagrafiche` INNER JOIN (`an_tipi_anagrafiche_anagrafiche` INNER JOIN `an_tipi_anagrafiche` ON `an_tipi_anagrafiche_anagrafiche`.`id_tipo_anagrafica`=`an_tipi_anagrafiche`.`id` LEFT JOIN `an_tipi_anagrafiche_lang` ON (`an_tipi_anagrafiche_lang`.`id_record` = `an_tipi_anagrafiche`.`id` AND `an_tipi_anagrafiche_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')) ON `an_anagrafiche`.`id`=`an_tipi_anagrafiche_anagrafiche`.`id_anagrafica` WHERE (`title`=\'Agente\' AND `deleted_at` IS NULL)'.(isset($record['id_agente']) ? 'OR (`an_anagrafiche`.`id` = '.prepare($record['id_agente']).' AND `deleted_at` IS NOT NULL) ' : '').'ORDER BY `ragione_sociale`", "value": "$id_agente$" ]}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Listino').'", "name": "id_listino", "ajax-source": "listini", "value": "$id_listino$" ]}
                                </div>
                               
                                 <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Tipo attività predefinita').'", "name": "id_tipo_intervento_default", "ajax-source": "tipiintervento",  "select-options": '.json_encode(['idtipiintervento' => '']).', "value": "$id_tipo_intervento_default$" ]}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">';
    $idtipiintervento = ['-1'];
    $idtipiintervento = array_merge($idtipiintervento, database()->table('an_anagrafiche_tipi_intervento')->where('id_anagrafica', $id_record)->pluck('id_tipo_intervento')->toArray());

    // Prepara la query per il tipo attività predefinita filtrata
    $where_clause = '';
    $tipi_utilizzabili_filtro = array_filter($idtipiintervento, fn ($val) => $val != '-1');
    if (!empty($tipi_utilizzabili_filtro)) {
        $where_clause = 'WHERE in_tipi_intervento.id IN ('.implode(',', array_map(intval(...), $tipi_utilizzabili_filtro)).')';
    }

    echo '
                                    {[ "type": "select", "multiple": "1", "label": "'.tr('Tipi attività utilizzabili').'", "id": "idtipiintervento", "name": "idtipiintervento[]", "values": "query=SELECT in_tipi_intervento.id, title as descrizione FROM in_tipi_intervento LEFT JOIN `in_tipi_intervento_lang` ON (`in_tipi_intervento`.`id` = `in_tipi_intervento_lang`.`id_record` AND `in_tipi_intervento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).') ORDER BY title ASC", "value": "'.implode(',', $idtipiintervento).'" ]}
                                </div>

                               <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Per conto di').'", "name": "id_cliente_finale", "value": "'.$id_cliente_finale.'", "ajax-source": "clienti" ]}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr("Dichiarazione d'intento").'", "name": "id_dichiarazione_intento_default", "ajax-source": "dichiarazioni_intento", "select-options": {"id_anagrafica": '.$id_record.', "data": "'.Carbon::now().'"},"value": "$id_dichiarazione_intento_default$" ]}
                                </div>';

    // Collegamento con il conto
    $conto = database()->table('co_piano_dei_conti3')
        ->join('co_piano_dei_conti2', 'co_piano_dei_conti3.id_piano_dei_conti2', '=', 'co_piano_dei_conti2.id')
        ->where('co_piano_dei_conti3.id', $record['id_conto_cliente'])
        ->first(['co_piano_dei_conti3.id', 'co_piano_dei_conti2.numero as numero', 'co_piano_dei_conti3.numero as numero_conto', 'co_piano_dei_conti3.descrizione as descrizione']);

    echo '
                                <div class="col-md-6">
                                    <p><b>'.tr('Piano dei conti cliente').'</b></p>';

    if (!empty($conto->numero_conto)) {
        $piano_dei_conti_cliente = $conto->numero.'.'.$conto->numero_conto.' '.$conto->descrizione;
        echo Modules::link('Piano dei conti', null, $piano_dei_conti_cliente, null, '', 1, 'movimenti-'.$conto->id);
    } else {
        $piano_dei_conti_cliente = tr('Nessuno');
    }

    echo '
                                </div>
                            </div>
                        </div>';

    echo '
                        <div class="tab-pane '.(!$is_fornitore ? 'hide' : (!$is_cliente ? 'active' : '')).'" id="fornitore">
                            <div class="row">
                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Pagamento predefinito').'", "name": "id_pagamento_acquisti", "values": "query=SELECT `co_pagamenti`.`id`, `co_pagamenti_lang`.`title` AS descrizione FROM `co_pagamenti` LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).') GROUP BY `descrizione` ORDER BY `descrizione` ASC", "value": "$id_pagamento_acquisti$" ]}
                                </div>

                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Banca predefinita azienda per addebiti').'", "name": "id_banca_acquisti", "ajax-source": "banche", "select-options": '.json_encode(['id_anagrafica' => $anagrafica_azienda->id]).', "value": "$id_banca_acquisti$", "help": "'.tr("Banca predefinita dell'Azienda da cui addebitare i pagamenti").'" ]}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Iva predefinita').'", "name": "id_iva_acquisti", "ajax-source": "iva", "value": "$id_iva_acquisti$" ]}
                                </div>

                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr("Ritenuta d'acconto predefinita").'", "name": "id_ritenuta_acconto_acquisti", "values": "query=SELECT id, descrizione FROM co_ritenuta_acconto ORDER BY descrizione ASC", "value": "$id_ritenuta_acconto_acquisti$" ]}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Piano di sconto/magg. su articoli').'", "name": "id_piano_sconto_acquisti", "values": "query=SELECT id, nome AS descrizione FROM mg_piani_sconto ORDER BY nome ASC", "value": "$id_piano_sconto_acquisti$" ]}
                                </div>';

    // Collegamento con il conto
    $conto = database()->table('co_piano_dei_conti3')
        ->join('co_piano_dei_conti2', 'co_piano_dei_conti3.id_piano_dei_conti2', '=', 'co_piano_dei_conti2.id')
        ->where('co_piano_dei_conti3.id', $record['id_conto_fornitore'])
        ->first(['co_piano_dei_conti3.id', 'co_piano_dei_conti2.numero as numero', 'co_piano_dei_conti3.numero as numero_conto', 'co_piano_dei_conti3.descrizione as descrizione']);

    echo '
                                <div class="col-md-6">
                                    <p><b>'.tr('Piano dei conti fornitore').'</b></p>';

    if (!empty($conto->numero_conto)) {
        $piano_dei_conti_fornitore = $conto->numero.'.'.$conto->numero_conto.' '.$conto->descrizione;
        echo Modules::link('Piano dei conti', null, $piano_dei_conti_fornitore, null, '', 1, 'movimenti-'.$conto->id);
    } else {
        $piano_dei_conti_fornitore = tr('Nessuno');
    }

    echo '
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane'.(!$is_cliente && !$is_fornitore && $is_tecnico ? ' active' : '').''.(!$is_tecnico ? ' hide' : '').'" id="tecnico">
                            <div class="row">
                                <div class="col-md-6">
                                    {[ "type": "text", "label": "'.tr('Colore').'", "name": "colore", "id": "colore_t", "class": "colorpicker text-center", "value": "$colore$", "maxlength": "7", "icon-after": "<div class=\'img-circle square\'></div>" ]}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
}
?>

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><?php echo tr('Informazioni aggiuntive'); ?></h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        {[ "type": "text", "label": "<?php echo tr('Numero d\'iscrizione registro imprese'); ?>", "name": "codice_r_i", "value": "$codice_r_i$", "help": "<?php echo tr('Il numero registro imprese è il numero di iscrizione attribuito dal Registro Imprese della Camera di Commercio.'); ?>" ]}
                    </div>

                    <div class="col-md-3">
                        {[ "type": "text", "label": "<?php echo tr('Codice R.E.A.').' <small>('.tr('provincia-C.C.I.A.A.').')</small>'; ?>", "name": "codice_rea", "value": "$codice_rea$", "class": "rea-mask text-uppercase", "help": "<?php echo tr('Esempio: _PATTERN_', [
                            '_PATTERN_' => 'RM-123456',
                        ]); ?>" ]}
                    </div>

                    <div class="col-md-3">
                        {[ "type": "text", "label": "<?php echo tr('Riferimento Amministrazione'); ?>", "name": "riferimento_amministrazione", "value": "$riferimento_amministrazione$", "maxlength": "20" ]}
                    </div>
                    <?php
            if ($is_agente) {
                ?>
                    <div class="col-md-3">
                        {[ "type": "number", "label": "<?php echo tr('Provvigione predefinita'); ?>", "name": "provvigione_default", "value": "$provvigione_default$", "icon-after": "%" ]}
                    </div>
                <?php
            }
?>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        {[ "type": "text", "label": "<?php echo tr('Num. iscr. tribunale'); ?>", "name": "iscrizione_tribunale", "value": "$iscrizione_tribunale$" ]}
                    </div>

                    <div class="col-md-3">
                        {[ "type": "text", "label": "<?php echo tr('Num. iscr. albo artigiani'); ?>", "name": "n_alboartigiani", "value": "$n_alboartigiani$" ]}
                    </div>

                    <div class="col-md-3">
                        {[ "type": "text", "label": "<?php echo tr('Foro di competenza'); ?>", "name": "foro_competenza", "value": "$foro_competenza$" ]}
                    </div>

                    <div class="col-md-3">
                        {[ "type": "number", "label": "<?php echo tr('Capitale sociale'); ?>", "name": "capitale_sociale", "value": "$capitale_sociale$", "icon-after": "<?php echo currency(); ?>" ]}
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        {[ "type": "select", "label": "<?php echo tr('Settore merceologico'); ?>", "name": "id_settore", "ajax-source": "settori", "value": "$id_settore$", "icon-after": "add|<?php echo Module::where('name', 'Settori')->first()->id; ?>" ]}
                    </div>

                    <div class="col-md-3">
                        {[ "type": "text", "label": "<?php echo tr('Marche trattate'); ?>", "name": "marche", "value": "$marche$" ]}
                    </div>

                    <div class="col-md-3">
                        {[ "type": "number", "label": "<?php echo tr('Num. dipendenti'); ?>", "name": "dipendenti", "decimals": 0, "value": "$dipendenti$" ]}
                    </div>

                    <div class="col-md-3">
                        {[ "type": "number", "label": "<?php echo tr('Num. macchine'); ?>", "name": "macchine", "decimals": 0, "value": "$macchine$" ]}
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        {[ "type": "select", "multiple": "1", "label": "<?php echo tr('Tipo di anagrafica'); ?>", "name": "id_tipo_anagrafica[]", "values": "query=SELECT `an_tipi_anagrafiche`.`id`, `title` as descrizione FROM `an_tipi_anagrafiche` LEFT JOIN `an_tipi_anagrafiche_lang` ON (`an_tipi_anagrafiche`.`id` = `an_tipi_anagrafiche_lang`.`id_record` AND `an_tipi_anagrafiche_lang`.`id_lang` = <?php echo prepare(Locale::getDefault()->id); ?>) WHERE `an_tipi_anagrafiche`.`id` NOT IN (SELECT DISTINCT(`x`.`id_tipo_anagrafica`) FROM `an_tipi_anagrafiche_anagrafiche` x INNER JOIN `an_tipi_anagrafiche` t ON `x`.`id_tipo_anagrafica` = `t`.`id` LEFT JOIN `an_tipi_anagrafiche_lang` ON (`an_tipi_anagrafiche_lang`.`id_record` = `t`.`id` AND `an_tipi_anagrafiche_lang`.`id_lang` = <?php echo prepare(Locale::getDefault()->id); ?>) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`id` = `x`.`id_anagrafica` WHERE `an_tipi_anagrafiche_lang`.`title` = 'Azienda' AND `deleted_at` IS NULL) ORDER BY `title`", "value": "$idtipianagrafica$" ]}
                            <?php
    if (in_array($id_azienda, $tipi_anagrafica)) {
        echo '
						    <p class="badge badge-info">'.tr('Questa anagrafica è di tipo "Azienda"').'.</p>';
    }
?>
                    </div>
                    <div class="col-md-6">
                        {[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$", "charcounter": 1 ]}
                    </div>
                </div>
            </div>
        </div>
    </fieldset>
</form>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<?php

// Documenti collegati - Caricamento via AJAX
echo '
<div class="card card-warning collapsable collapsed-card" id="documenti-collegati-card">
    <div class="card-header with-border">
        <h3 class="card-title"><i class="fa fa-warning"></i> <span id="documenti-collegati-title">'.tr('Documenti collegati').'</span></h3>
        <div class="card-tools pull-right">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" id="documenti-collegati-toggle"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body" id="documenti-collegati-body">
        <div class="text-center" id="documenti-collegati-loading">
            <i class="fa fa-spinner fa-spin"></i> '.tr('Caricamento documenti collegati in corso').'
        </div>
        <div id="documenti-collegati-content" style="display: none;"></div>
    </div>
</div>';

echo '
<script type="text/javascript">
    // Funzioni per i documenti collegati
    var documentiCaricati = false;

    function caricaConteggioDocumenti() {
        $.get(globals.rootdir + "/ajax_documenti_collegati.php", {
            id_module: globals.id_module,
            id_record: globals.id_record,
            count_only: 1
        })
        .done(function(data) {
            var title = $("#documenti-collegati-title");
            var card = $("#documenti-collegati-card");
            
            if (data.count > 0) {
                card.removeClass("card-secondary").addClass("card-warning");
                title.html("'.tr('Documenti collegati').' (" + data.count + ")");
            } else {
                card.removeClass("card-warning").addClass("card-secondary");
                title.html("'.tr('Documenti collegati').'");
            }
        })
        .fail(function() {
            var title = $("#documenti-collegati-title");
            var card = $("#documenti-collegati-card");
            card.removeClass("card-warning").addClass("card-secondary");
            title.html("'.tr('Documenti collegati').'");
        });
    }

    function caricaDocumentiCollegati() {
        $("#documenti-collegati-loading").show();
        $("#documenti-collegati-content").hide();
        
        $.get(globals.rootdir + "/ajax_documenti_collegati.php", {
            id_module: globals.id_module,
            id_record: globals.id_record
        })
        .done(function(data) {
            $("#documenti-collegati-loading").hide();
            $("#documenti-collegati-content").html(data).show();
            documentiCaricati = true;
        })
        .fail(function() {
            $("#documenti-collegati-loading").hide();
            $("#documenti-collegati-content").html("<div class=\\"alert alert-danger\\">'.tr('Errore durante il caricamento dei documenti collegati').'</div>").show();
        });
    }

    $(document).ready(function() {
        // Carica il conteggio dei documenti collegati
        caricaConteggioDocumenti();

        // Carica i documenti quando la card viene espansa
        $("#documenti-collegati-card").on("expanded.lte.cardwidget", function() {
            if (!documentiCaricati) {
                caricaDocumentiCollegati();
            }
        });

        // Aggiorna l\'icona quando la card viene espansa/collassata
        $("#documenti-collegati-card").on("expanded.lte.cardwidget", function() {
            $("#documenti-collegati-toggle i").removeClass("fa-plus").addClass("fa-minus");
        });

        $("#documenti-collegati-card").on("collapsed.lte.cardwidget", function() {
            $("#documenti-collegati-toggle i").removeClass("fa-minus").addClass("fa-plus");
        });
    });
</script>';

if (empty($record['deleted_at'])) {
    if (!in_array($id_azienda, $tipi_anagrafica)) {
        if (!empty($elementi)) {
            echo '
<div class="alert alert-danger">
    '.tr('Eliminando questo documento si potrebbero verificare problemi nelle altre sezioni del gestionale').'.
</div>';
        }

        echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
    } else {
        echo '
<div class="alert alert-warning">'.tr('Questa è l\'anagrafica "Azienda" e non è possibile eliminarla').'.</div>';
    }
} else {
    echo '
<div class="alert alert-danger">'.tr('Questa anagrafica è stata eliminata').'.</div>';
}
?>

<script>

    var an_sdi = <?php echo ($anagrafica->tipo != 'Ente pubblico') ? $dbo->table('an_sdi')->pluck('codice')->toJson() : '[]'; ?>;

    $(document).ready(function() {

        // Auto-completamento codice intermediario per anagrafiche con tipologia Azienda
        if (an_sdi && an_sdi.length > 1) {
            const input = $("#codice_destinatario")[0];
            autocomplete({
                minLength: 0,
                input: input,
                emptyMsg: globals.translations.noResults,
                fetch: function (text, update) {
                    text = text.toLowerCase();
                    const suggestions = an_sdi.filter(n => n.toLowerCase().startsWith(text));

                    // Trasformazione risultati in formato leggibile
                    const results = suggestions.map(function (result) {
                        return {
                            label: result,
                            value: result
                        }
                    });

                    update(results);
                },
                onSelect: function (item) {
                    input.value = item.label;
                },
            });
        }

        $(".colorpicker").colorpicker({
            format: 'hex'
        }).on("colorpickerChange", function(event) {
            $("#colore_t").parent().find(".square").css("background", event.value);
        });

        $("#colore_t").parent().find(".square").css("background", $("#colore_t").val());

        // Abilito solo ragione sociale oppure solo cognome-nome in base a cosa compilo
        $('#nome, #cognome').bind("keyup change", function(e) {
            if ($('#nome').val() == '' && $('#cognome').val() == '') {
                $('#nome, #cognome').prop('disabled', true).prop('required', false);
                $('#ragione_sociale').prop('disabled', false).prop('required', true);
                $('#ragione_sociale').focus();
            } else {
                $('#nome, #cognome').prop('disabled', false).prop('required', true);
                $('#ragione_sociale').prop('disabled', true).prop('required', false);
            }
        });

        $('#ragione_sociale').bind("keyup change", function(e) {
            if ($('#ragione_sociale').val() == '') {
                $('#nome, #cognome').prop('disabled', false).prop('required', true);
                $('#ragione_sociale').prop('disabled', true).prop('required', false);
                $('#cognome').focus();
            } else {
                $('#nome, #cognome').prop('disabled', true).prop('required', false);
                $('#ragione_sociale').prop('disabled', false).prop('required', true);
                $('#ragione_sociale').focus();
            }
        });

        $('#ragione_sociale, #cognome').trigger('keyup');

        $('#idtipiintervento').change(function() {
            updateSelectOption("idtipiintervento", $('#idtipiintervento option:selected').map(function() { return $(this).val(); }).get());
 	    });
    });
</script>
