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

use Modules\Anagrafiche\Anagrafica;
use Modules\Banche\Banca;

include_once __DIR__.'/../../core.php';

$is_fornitore = in_array($id_fornitore, $tipi_anagrafica);
$is_cliente = in_array($id_cliente, $tipi_anagrafica);
$is_tecnico = in_array($id_tecnico, $tipi_anagrafica);

if (!$is_cliente && !$is_fornitore && $is_tecnico) {
    $ignore = $dbo->fetchArray("SELECT id FROM zz_plugins WHERE name='Sedi' OR name='Referenti' OR name='Dichiarazioni d\'intento'");

    foreach ($ignore as $plugin) {
        echo '
<script>
    $("#link-tab_'.$plugin['id'].'").addClass("disabled");
</script>';
    }
}

if (!$is_cliente) {
    $ignore = $dbo->fetchArray("SELECT id FROM zz_plugins WHERE name='Impianti del cliente' OR name='Ddt del cliente'");

    foreach ($ignore as $plugin) {
        echo '
<script>
    $("#link-tab_'.$plugin['id'].'").addClass("disabled");
</script>';
    }
}

$nazione_anagrafica = $anagrafica->sedeLegale->nazione;

// Avvisi problemi scheda anagrafica
$problemi_anagrafica = [];
if ($is_cliente && empty($record['idconto_cliente'])) {
    array_push($problemi_anagrafica, ' Piano dei conti mancante per il cliente');
}

if ($is_fornitore && empty($record['idconto_fornitore'])) {
    array_push($problemi_anagrafica, ' Piano dei conti mancante per il fornitore');
}

if (sizeof($problemi_anagrafica) > 0) {
    echo '<div class="alert alert-warning"><i class="fa fa-warning"></i> '.tr('Attenzione: _CAMPI_', [
    '_CAMPI_' => implode(', ', $problemi_anagrafica),
    ]).'</div>';
}

?>

<form action="" method="post" id="edit-form"  autocomplete="<?php echo setting('Autocompletamento form'); ?>" >
	<fieldset>
		<input type="hidden" name="backto" value="record-edit">
		<input type="hidden" name="op" value="update">

		<!-- DATI ANAGRAFICI -->
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo tr('Dati anagrafici'); ?></h3>
			</div>

			<div class="panel-body">
				<div class="row">
					<div class="col-md-6">
						{[ "type": "text", "label": "<?php echo tr('Denominazione'); ?>", "name": "ragione_sociale", "required": 1, "value": "$ragione_sociale$", "extra": "" ]}
					</div>

					<div class="col-md-3">
                        {[ "type": "text", "label": "<?php echo tr('Partita IVA'); ?>", "maxlength": 16, "name": "piva", "class": "text-center alphanumeric-mask text-uppercase", "value": "$piva$", "validation": "partita_iva" ]}
                    </div>

					<div class="col-md-3">
						{[ "type": "select", "label": "<?php echo tr('Tipologia'); ?>", "name": "tipo", "values": "list=\"\": \"<?php echo tr('Non specificato'); ?>\", \"Azienda\": \"<?php echo tr('Azienda'); ?>\", \"Privato\": \"<?php echo tr('Privato'); ?>\", \"Ente pubblico\": \"<?php echo tr('Ente pubblico'); ?>\"", "value": "$tipo$" ]}
					</div>
				</div>

				<div class="row">

					<div class="col-md-4">
							{[ "type": "text", "label": "<?php echo tr('Cognome'); ?>", "name": "cognome", "required": 0, "value": "$cognome$", "extra": "" ]}
					</div>

					<div class="col-md-4">
							{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 0, "value": "$nome$", "extra": "" ]}
					</div>

					<div class="col-md-4">
                        {[ "type": "text", "label": "<?php echo tr('Codice fiscale'); ?>", "maxlength": 16, "name": "codice_fiscale", "class": "text-center alphanumeric-mask text-uppercase", "value": "$codice_fiscale$", "validation": "codice_fiscale" ]}
                    </div>

				</div>

				<!-- RIGA PER LE ANAGRAFICHE CON TIPOLOGIA 'PRIVATO' -->
				<?php if ($record['tipo'] == 'Privato') {
    ?>
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
				<?php
} ?>

				<div class="row">
					<div class="col-md-2">
						{[ "type": "text", "label": "<?php echo tr('Codice anagrafica'); ?>", "name": "codice", "required": 1, "class": "text-center alphanumeric-mask", "value": "$codice$", "maxlength": 20, "validation": "codice" ]}
					</div>

					<div class="col-md-2">
						<?php
                            $help_codice_destinatario = tr("Per impostare il codice specificare prima il campo '_NATION_' dell'anagrafica", [
                                '_NATION_' => '<b>Nazione</b>',
                            ]).':<br><br><ul>
                                <li>'.tr('Ente pubblico (B2G/PA) - Codice Univoco Ufficio (www.indicepa.gov.it), 6 caratteri').'</li>
                                <li>'.tr('Azienda (B2B) - Codice Destinatario, 7 caratteri').'</li>
                                <li>'.tr('Privato (B2C) - viene utilizzato il Codice Fiscale').'</li></ul>
                            '.tr('Se non si conosce il codice destinatario lasciare vuoto il campo, e verrà applicato in automatico quello previsto di default dal sistema (\'0000000\', \'999999\', \'XXXXXXX\')').'.';

                            if (in_array($id_azienda, $tipi_anagrafica)) {
                                $help_codice_destinatario .= ' <b>'.tr("Non è necessario comunicare il proprio codice destinatario ai fornitori in quanto è sufficiente che questo sia registrato nel portale del Sistema Di Interscambio dell'Agenzia Entrate (SDI)").'.</b>';
                            }
                        ?>
						{[ "type": "text", "label": "<?php echo ($record['tipo'] == 'Ente pubblico') ? tr('Codice unico ufficio') : tr('Codice destinatario'); ?>", "name": "codice_destinatario", "required": 0, "class": "text-center text-uppercase alphanumeric-mask", "value": "$codice_destinatario$", "maxlength": <?php echo ($record['tipo'] == 'Ente pubblico') ? '6' : '7'; ?>, "help": "<?php echo tr($help_codice_destinatario); ?>", "readonly": "<?php echo intval($nazione_anagrafica ? $nazione_anagrafica->iso2 != 'IT' : 0); ?>" ]}
					</div>

                    <div class="col-md-4">
						{[ "type": "text", "label": "<?php echo tr('PEC'); ?>", "name": "pec", "class": "email-mask", "placeholder":"pec@dominio.ext", "value": "$pec$", "icon-before": "<i class='fa fa-envelope-o'></i>", "validation": "email" ]}
					</div>

					<div class="col-md-4">
						{[ "type": "text", "label": "<?php echo tr('Sito web'); ?>", "name": "sitoweb", "placeholder":"www.dominio.ext", "value": "$sitoweb$", "icon-before": "<i class='fa fa-globe'></i>" ]}
					</div>
				</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-building"></i> <?php echo tr('Sede legale'); ?></h3>
                    </div>
                    <div class="panel-body">

                        <div class="row">
                            <div class="col-md-<?php echo (empty($record['indirizzo2'])) ? '10' : '8'; ?>">
                                {[ "type": "text", "label": "<?php echo tr('Indirizzo'); ?>", "name": "indirizzo", "value": "$indirizzo$" ]}
                            </div>

                            <div class="col-md-2<?php echo (empty($record['indirizzo2'])) ? ' hide' : ''; ?>">
                                {[ "type": "text", "label": "<?php echo tr('Civico'); ?>", "name": "indirizzo2", "value": "$indirizzo2$" ]}
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
                                {[ "type": "text", "label": "<?php echo tr('Provincia'); ?>", "name": "provincia", "maxlength": 2, "class": "text-center provincia-mask text-uppercase", "value": "$provincia$", "extra": "onkeyup=\"this.value = this.value.toUpperCase();\"" ]}
                            </div>

                            <div class="col-md-4">
                                {[ "type": "select", "label": "<?php echo tr('Nazione'); ?>", "name": "id_nazione", "value": "$id_nazione$", "ajax-source": "nazioni" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                {[ "type": "text", "label": "<?php echo tr('Telefono'); ?>", "name": "telefono", "class": "text-center", "value": "$telefono$", "icon-before": "<i class='fa fa-phone'></i>" ]}
                            </div>

                            <div class="col-md-4">
                                {[ "type": "text", "label": "<?php echo tr('Cellulare'); ?>", "name": "cellulare", "class": "text-center", "value": "$cellulare$", "icon-before": "<i class='fa fa-mobile'></i>" ]}
                            </div>

                            <div class="col-md-4">
                                {[ "type": "text", "label": "<?php echo tr('Email'); ?>", "name": "email", "class": "email-mask", "placeholder": "casella@dominio.ext", "value": "$email$", "icon-before": "<i class='fa fa-envelope'></i>", "validation": "email" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                {[ "type": "text", "label": "<?php echo tr('Fax'); ?>", "name": "fax", "class": "text-center", "value": "$fax$", "icon-before": "<i class='fa fa-fax'></i>" ]}
                            </div>

                            <div class="col-md-4">
                                {[ "type": "select", "label": "<?php echo tr('Zona'); ?>", "name": "idzona", "values": "query=SELECT id, CONCAT_WS( ' - ', nome, descrizione) AS descrizione FROM an_zone ORDER BY descrizione ASC", "value": "$idzona$", "placeholder": "<?php echo tr('Nessuna zona'); ?>", "icon-after": "add|<?php echo Modules::get('Zone')['id']; ?>" ]}
                            </div>

                            <div class="col-md-4">
                                {[ "type": "number", "label": "<?php echo tr('Distanza'); ?>", "name": "km", "decimals":"1", "class": "text-center", "value": "$km$", "icon-after": "Km" ]}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<?php
$sede_cliente = $anagrafica->sedeLegale;

$anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));
$sede_azienda = $anagrafica_azienda->sedeLegale;

$google = setting('Google Maps API key');

echo '
            <div class="col-md-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-map"></i> '.tr('Geolocalizzazione').'</h3>
                    </div>
                    <div class="panel-body">';

$map_load_message = '<p>'.tr('Clicca per visualizzare').'</p>';
if (empty($google)) {
    echo '
                        <div class="alert alert-info">
                            '.Modules::link('Impostazioni', null, tr('Per abilitare la visualizzazione delle anagrafiche nella mappa, inserire la Google Maps API Key nella scheda Impostazioni'), true, null, true, null, '&search=Google Maps API key').'.
                        </div>';
} elseif (!empty($sede_cliente->gaddress) || (!empty($sede_cliente->lat) && !empty($sede_cliente->lng))) {
    echo '
                        <div id="map-edit" style="height: 200px;width: 100%;display: flex;align-items: center;justify-content: center;" onclick="caricaMappa()">
                            '.$map_load_message.'
                        </div>

                        <div class="clearfix"></div>
                        <br>';

    // Modifica manuale delle informazioni
    echo '
                        <a class="btn btn-primary btn-block" onclick="modificaPosizione()">
                            <i class="fa fa-map"></i> '.tr('Modifica posizione').'
                        </a>';

    // Navigazione diretta verso l'indirizzo
    echo '
                        <a class="btn btn-info btn-block" onclick="calcolaPercorso()">
                            <i class="fa fa-map-signs"></i> '.tr('Calcola percorso').'
                        </a>';
} else {
    // Modifica manuale delle informazioni
    echo '
                        <a class="btn btn-primary btn-block" onclick="modificaPosizione()">
                            <i class="fa fa-map"></i> '.tr('Modifica posizione').'
                        </a>';

    // Navigazione diretta verso l'indirizzo
    echo '
                        <a class="btn btn-info btn-block" onclick="calcolaPercorso()">
                            <i class="fa fa-map-signs"></i> '.tr('Calcola percorso').'
                        </a>';

    // Ricerca diretta su Google Maps
    echo '
                        <a class="btn btn-info btn-block" onclick="cercaGoogleMaps()">
                            <i class="fa fa-map-marker"></i> '.tr('Cerca su Google Maps').'
                        </a>';
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

            function cercaGoogleMaps() {
                const indirizzo = getIndirizzoAnagrafica();
                window.open("https://maps.google.com/maps/search/" + indirizzo);
            }

            function calcolaPercorso() {
                const indirizzo_partenza = getIndirizzoAzienda();
                const indirizzo_destinazione = getIndirizzoAnagrafica();
                window.open("https://maps.google.com/maps/dir/" + indirizzo_partenza + "/" + indirizzo_destinazione);
            }

            function getIndirizzoAzienda() {
                const indirizzo = "'.$sede_azienda->indirizzo.'";
                const citta = "'.$sede_azienda->citta.'";

                const lat = parseFloat("'.$sede_azienda->lat.'");
                const lng = parseFloat("'.$sede_azienda->lng.'");

                const indirizzo_default = encodeURI(indirizzo) + "," + encodeURI(citta);
                if (!lat || !lng) return indirizzo_default;

                return lat + "," + lng;
            }

            function getIndirizzoAnagrafica() {
                const indirizzo = $("#indirizzo").val();
                const citta = $("#citta").val();

                const lat = parseFloat("'.$sede_cliente->lat.'");
                const lng = parseFloat("'.$sede_cliente->lng.'");

                const indirizzo_default = encodeURI(indirizzo) + "," + encodeURI(citta);
                if (!lat || !lng) return indirizzo_default;

                return lat + "," + lng;
            }

            function caricaMappa() {
                const map_div = $("#map-edit");
                if (map_div.html().trim() !== "'.$map_load_message.'"){
                    return;
                }

                $.getScript("//maps.googleapis.com/maps/api/js?libraries=places&key='.$google.'", function() {
                    const map_element = map_div[0];
                    const lat = parseFloat("'.$sede_cliente->lat.'");
                    const lng = parseFloat("'.$sede_cliente->lng.'");

                    if (!lat || !lng) return;
                    const position = new google.maps.LatLng(lat, lng);

                    // Create a Google Maps native view under the map_canvas div.
                    const map = new google.maps.Map(map_element, {
                        zoom: 14,
                        scrollwheel: false,
                        mapTypeControl: true,
                        mapTypeId: "roadmap",
                        mapTypeControlOptions: {
                            style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
                            mapTypeIds: ["roadmap", "terrain"],
                        }
                    });

                    map.setCenter(position);
                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                    });
               });
            }
        </script>';

if ($is_cliente or $is_fornitore or $is_tecnico) {
    echo '

    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">'.tr('Informazioni per tipo di anagrafica').'</h3>
        </div>

        <div class="panel-body">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs nav-justified">
                    <li '.($is_cliente ? 'class="active"' : '').'><a href="#cliente" data-toggle="tab" class="'.($is_cliente ? '' : 'disabled').'" '.($is_cliente ? '' : 'disabled').'>'.tr('Cliente').'</a></li>

                    <li '.(!$is_cliente && $is_fornitore ? 'class="active"' : '').'><a href="#fornitore" data-toggle="tab" class="'.($is_fornitore ? '' : 'disabled').'" '.($is_fornitore ? '' : 'disabled').'>'.tr('Fornitore').'</a></li>

                    <li><a href="#cliente_fornitore" data-toggle="tab" class="'.($is_cliente || $is_fornitore ? '' : 'disabled').'" '.($is_cliente || $is_fornitore ? '' : 'disabled').'>'.tr('Cliente e fornitore').'</a></li>

                    <li '.(!$is_cliente && !$is_fornitore && $is_tecnico ? 'class="active"' : '').'><a href="#tecnico" data-toggle="tab" class="'.($is_tecnico ? '' : 'disabled').'" '.($is_tecnico ? '' : 'disabled').'>'.tr('Tecnico').'</a></li>
                </ul>

                <div class="tab-content '.(!$is_cliente && !$is_fornitore && !$is_tecnico ? 'hide' : '').'">
                    <div class="tab-pane '.(!$is_cliente && !$is_fornitore ? ' hide' : '').'" id="cliente_fornitore">
                        <!--div class="row">
                             <div class="col-md-6">
                                 {[ "type": "text", "label": "'.tr('Appoggio bancario').'", "name": "appoggiobancario", "value": "$appoggiobancario$" ]}
                             </div>

                             <div class="col-md-6">
                                 {[ "type": "text", "label": "'.tr('Filiale banca').'", "name": "filiale", "value": "$filiale$" ]}
                             </div>
                        </div>

                        <div class="row">
                             <div class="col-md-6">
                                 {[ "type": "text", "label": "'.tr('Codice IBAN').'", "name": "codiceiban", "value": "$codiceiban$" ]}
                             </div>

                             <div class="col-md-6">
                                 {[ "type": "text", "label": "'.tr('Codice BIC').'", "name": "bic", "value": "$bic$" ]}
                             </div>
                        </div-->

                        <div class="row">
                            <div class="col-md-3">
                                {[ "type": "checkbox", "label": "'.tr('Abilitare lo split payment').'", "name": "split_payment", "value": "$split_payment$", "help": "'.tr('Lo split payment è disponibile per le anagrafiche di tipologia \"Ente pubblico\" o \"Azienda\" (iscritta al Dipartimento Finanze - Scissione dei pagamenti) ed <strong>&egrave; obbligatorio</strong> per:<ul><li>Stato;</li><li>organi statali ancorch&eacute; dotati di personalit&agrave; giuridica;</li><li>enti pubblici territoriali e dei consorzi tra essi costituiti;</li><li>Camere di Commercio;</li><li>Istituti universitari;</li><li>ASL e degli enti ospedalieri;</li><li>enti pubblici di ricovero e cura aventi prevalente carattere scientifico;</li><li>enti pubblici di assistenza e beneficienza;</li><li>enti di previdenza;</li><li>consorzi tra questi costituiti.</li></ul>').'", "placeholder": "'.tr('Split payment').'", "extra" : "'.($record['tipo'] == 'Ente pubblico' || $record['tipo'] == 'Azienda' ? '' : 'disabled').'" ]}
                            </div>

                            <div class="col-md-9">
                                {[ "type": "text", "label": "'.tr('Dicitura fissa in fattura').'", "name": "diciturafissafattura", "value": "$diciturafissafattura$" ]}
                            </div>
                        </div>';

    $banche = Banca::where('id_anagrafica', $anagrafica->id)->get();
    $banca_predefinita = $banche->first(function ($item) {
        return !empty($item['predefined']);
    });
    $modulo_banche = Modules::get('Banche');
    if (!$banche->isEmpty()) {
        echo '
                        <div class="row">
                            <div class="col-md-6">
                                <a href="'.base_path().'/editor.php?id_module='.$modulo_banche['id'].'&id_record='.$banca_predefinita->id.'">
                                    '.tr("Visualizza la banca predefinita per l'Anagrafica").' <i class="fa fa-external-link"></i>
                                </a>
                            </div>

                            <div class="col-md-6">
                                <a href="'.base_path().'/controller.php?id_module='.$modulo_banche['id'].'&search_Anagrafica='.rawurlencode($anagrafica['ragione_sociale']).'">
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
                                {[ "type": "select", "label": "'.tr('Tipo attività predefinita').'", "name": "idtipointervento_default", "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione ASC", "value": "$idtipointervento_default$" ]}
                            </div>

                            <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Relazione con il cliente').'", "name": "idrelazione", "ajax-source": "relazioni", "value": "$idrelazione$", "icon-after": "add|'.Modules::get('Relazioni')['id'].'" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                {[ "type": "select", "label": "'.tr('Pagamento predefinito').'", "name": "idpagamento_vendite", "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento_vendite$" ]}
                            </div>

                            <div class="col-md-6">
                                {[ "type": "select", "label": "'.tr('Banca predefinita per accrediti').'", "name": "idbanca_vendite", "ajax-source": "banche", "select-options": '.json_encode(['id_anagrafica' => $anagrafica_azienda->id]).', "value": "$idbanca_vendite$", "help": "'.tr("Banca predefinita dell'Azienda su cui accreditare i pagamenti").'" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                {[ "type": "select", "label": "'.tr('Iva predefinita').'", "name": "idiva_vendite", "ajax-source": "iva", "value": "$idiva_vendite$" ]}
                            </div>

                            <div class="col-md-6">
                                {[ "type": "select", "label": "'.tr("Ritenuta d'acconto predefinita").'", "name": "id_ritenuta_acconto_vendite", "values": "query=SELECT id, descrizione FROM co_ritenutaacconto ORDER BY descrizione ASC", "value": "$id_ritenuta_acconto_vendite$" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                {[ "type": "select", "label": "'.tr('Piano di sconto/rincaro su articoli').'", "name": "idlistino_vendite", "values": "query=SELECT id, nome AS descrizione FROM mg_listini ORDER BY nome ASC", "value": "$idlistino_vendite$" ]}
                            </div>

                            <div class="col-md-6">
                                {[ "type": "select", "label": "'.tr('Indirizzo di fatturazione').'", "name": "idsede_fatturazione", "values": "query=SELECT id, IF(citta = \'\', nomesede, CONCAT_WS(\', \', nomesede, citta)) AS descrizione FROM an_sedi WHERE idanagrafica='.prepare($id_record).' UNION SELECT \'0\' AS id, \'Sede legale\' AS descrizione ORDER BY descrizione", "value": "$idsede_fatturazione$"  ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                {[ "type": "select", "label": "'.tr('Agente principale').'", "name": "idagente", "values": "query=SELECT an_anagrafiche.idanagrafica AS id, IF(deleted_at IS NOT NULL, CONCAT(ragione_sociale, \' (Eliminato)\'), ragione_sociale ) AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE (descrizione=\'Agente\' AND deleted_at IS NULL)'.(isset($record['idagente']) ? 'OR (an_anagrafiche.idanagrafica = '.prepare($record['idagente']).' AND deleted_at IS NOT NULL) ' : '').'ORDER BY ragione_sociale", "value": "$idagente$" ]}
                            </div>

                            <div class="col-md-6">
                                {[ "type": "select", "label": "'.tr('Agenti secondari').'", "multiple": "1", "name": "idagenti[]", "values": "query=SELECT an_anagrafiche.idanagrafica AS id, IF(deleted_at IS NOT NULL, CONCAT(ragione_sociale, \' (Eliminato)\'), ragione_sociale ) AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE (descrizione=\'Agente\' AND deleted_at IS NULL AND an_anagrafiche.idanagrafica NOT IN (SELECT idagente FROM an_anagrafiche WHERE  idanagrafica = '.prepare($record['idanagrafica']).')) OR (an_anagrafiche.idanagrafica IN (SELECT idagente FROM an_anagrafiche_agenti WHERE idanagrafica = '.prepare($record['idanagrafica']).') ) ORDER BY ragione_sociale", "value": "$idagenti$" ]}
                            </div>
                        </div>';

    // Collegamento con il conto
    $conto = $dbo->fetchOne('SELECT co_pianodeiconti3.id, co_pianodeiconti2.numero as numero, co_pianodeiconti3.numero as numero_conto, co_pianodeiconti3.descrizione AS descrizione FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE co_pianodeiconti3.id = '.prepare($record['idconto_cliente']));

    echo '
                        <div class="row">
                            <div class="col-md-6">
                                <p><b>'.tr('Piano dei conti cliente').'</b></p>';

    if (!empty($conto['numero_conto'])) {
        $piano_dei_conti_cliente = $conto['numero'].'.'.$conto['numero_conto'].' '.$conto['descrizione'];
        echo Modules::link('Piano dei conti', null, $piano_dei_conti_cliente, null, '', 1, 'movimenti-'.$conto['id']);
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
                                {[ "type": "select", "label": "'.tr('Pagamento predefinito').'", "name": "idpagamento_acquisti", "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento_acquisti$" ]}
                            </div>

                            <div class="col-md-6">
                                {[ "type": "select", "label": "'.tr('Banca predefinita per addebiti').'", "name": "idbanca_acquisti", "ajax-source": "banche", "select-options": '.json_encode(['id_anagrafica' => $anagrafica_azienda->id]).', "value": "$idbanca_acquisti$", "help": "'.tr("Banca predefinita dell'Azienda da cui addebitare i pagamenti").'" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                {[ "type": "select", "label": "'.tr('Iva predefinita').'", "name": "idiva_acquisti", "ajax-source": "iva", "value": "$idiva_acquisti$" ]}
                            </div>

                            <div class="col-md-6">
                                {[ "type": "select", "label": "'.tr("Ritenuta d'acconto predefinita").'", "name": "id_ritenuta_acconto_acquisti", "values": "query=SELECT id, descrizione FROM co_ritenutaacconto ORDER BY descrizione ASC", "value": "$id_ritenuta_acconto_acquisti$" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                {[ "type": "select", "label": "'.tr('Piano di sconto/rincaro su articoli').'", "name": "idlistino_acquisti", "values": "query=SELECT id, nome AS descrizione FROM mg_listini ORDER BY nome ASC", "value": "$idlistino_acquisti$" ]}
                            </div>';

    // Collegamento con il conto
    $conto = $dbo->fetchOne('SELECT co_pianodeiconti3.id, co_pianodeiconti2.numero as numero, co_pianodeiconti3.numero as numero_conto, co_pianodeiconti3.descrizione AS descrizione FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE co_pianodeiconti3.id = '.prepare($record['idconto_fornitore']));

    echo '
                            <div class="col-md-6">
                                <p><b>'.tr('Piano dei conti fornitore').'</b></p>';

    if (!empty($conto['numero_conto'])) {
        $piano_dei_conti_fornitore = $conto['numero'].'.'.$conto['numero_conto'].' '.$conto['descrizione'];
        echo Modules::link('Piano dei conti', null, $piano_dei_conti_fornitore, null, '', 1, 'movimenti-'.$conto['id']);
    } else {
        $piano_dei_conti_fornitore = tr('Nessuno');
    }

    echo '
                            </div>
                        </div>
                    </div>';

    echo '
                    <div class="tab-pane'.(!$is_cliente && !$is_fornitore && $is_tecnico ? ' active' : '').''.(!$is_tecnico ? ' hide' : '').'" id="tecnico">
                        <div class="row">
                            <div class="col-md-6">
                                {[ "type": "text", "label": "'.tr('Colore').'", "name": "colore", "class": "colorpicker text-center", "value": "$colore$", "extra": "maxlength=\'7\'", "icon-after": "<div class=\'img-circle square\'></div>" ]}
                            </div>
                        </div>
                    </div>';

    echo '
                </div>
            </div>
        </div>
    </div>';
}
    ?>

		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo tr('Informazioni aggiuntive'); ?></h3>
			</div>

			<div class="panel-body">
				<div class="row">
                    <div class="col-md-3">
						{[ "type": "text", "label": "<?php echo tr('Numero d\'iscrizione registro imprese'); ?>", "name": "codiceri", "value": "$codiceri$", "help": "<?php echo tr('Il numero registro imprese è il numero di iscrizione attribuito dal Registro Imprese della Camera di Commercio.'); ?>" ]}
                    </div>

					<div class="col-md-3">
						{[ "type": "text", "label": "<?php echo tr('Codice R.E.A.').' <small>('.tr('provincia-C.C.I.A.A.').')</small>'; ?>", "name": "codicerea", "value": "$codicerea$", "class": "rea-mask", "help": "<?php echo tr('Formato: _PATTERN_', [
                            '_PATTERN_' => 'RM-123456',
                        ]); ?>" ]}
                    </div>

                    <div class="col-md-3">
                        {[ "type": "text", "label": "<?php echo tr('Riferimento Amministrazione'); ?>", "name": "riferimento_amministrazione", "value": "$riferimento_amministrazione$", "maxlength": "20" ]}
                    </div>

                    <!-- campi già specificati in Codice R.E.A., da eliminare nelle prossime release -->
                    <!--div class="col-md-3">
						{[ "type": "text", "label": "<?php echo tr('Num. iscr. C.C.I.A.A.'); ?>", "name": "cciaa", "value": "$cciaa$" ]}
					</div>

					<div class="col-md-3">
						{[ "type": "text", "label": "<?php echo tr('Città iscr. C.C.I.A.A.'); ?>", "name": "cciaa_citta", "value": "$cciaa_citta$" ]}
                    </div-->
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
						{[ "type": "text", "label": "<?php echo tr('Capitale sociale'); ?>", "name": "capitale_sociale", "value": "$capitale_sociale$" ]}
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						{[ "type": "text", "label": "<?php echo tr('Settore merceologico'); ?>", "name": "settore", "value": "$settore$" ]}
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
					<div class="col-md-12">
						{[ "type": "select", "multiple": "1", "label": "<?php echo tr('Tipo di anagrafica'); ?>", "name": "idtipoanagrafica[]", "values": "query=SELECT idtipoanagrafica AS id, descrizione FROM an_tipianagrafiche WHERE idtipoanagrafica NOT IN (SELECT DISTINCT(x.idtipoanagrafica) FROM an_tipianagrafiche_anagrafiche x INNER JOIN an_tipianagrafiche t ON x.idtipoanagrafica = t.idtipoanagrafica INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = x.idanagrafica WHERE t.descrizione = 'Azienda' AND deleted_at IS NULL) ORDER BY descrizione", "value": "$idtipianagrafica$" ]}
						<?php
                        if (in_array($id_azienda, $tipi_anagrafica)) {
                            echo '
						<p class="badge badge-info">'.tr('Questa anagrafica è di tipo "Azienda"').'.</p>';
                        }
                        ?>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
					</div>
				</div>

                <div class="row">
                    <div class="col-md-12">
                        {[ "type": "checkbox", "label": "<?php echo tr('Opt-out per newsletter'); ?>", "name": "disable_newsletter", "value": "<?php echo empty($record['enable_newsletter']); ?>" ]}
                    </div>
                </div>
			</div>
		</div>
	</fieldset>
</form>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<?php

if (setting('Azienda predefinita') == $id_record) {
    echo '
<div class="alert alert-info">'.tr('Per impostare il <b>logo nelle stampe</b>, caricare un\'immagine specificando come nome "<b>Logo stampe</b>" (Risoluzione consigliata 302x111 pixel).<br>Per impostare una <b>filigrana nelle stampe</b>, caricare un\'immagine specificando come nome "<b>Filigrana stampe</b>"').'.</div>';
}

// Collegamenti diretti
// Fatture, ddt, preventivi, contratti, ordini, interventi, utenti collegati a questa anagrafica
$elementi = $dbo->fetchArray('SELECT `co_documenti`.`id`, `co_documenti`.`data`, `co_documenti`.`numero`, `co_documenti`.`numero_esterno`, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir`, NULL AS `deleted_at` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`idanagrafica` = '.prepare($id_record).'

UNION
SELECT `zz_users`.`id`, `zz_users`.`created_at` AS data, `zz_users`.`username` AS numero, 0 AS `numero_esterno`, "Utente" AS tipo_documento, 0 AS `dir`, NULL AS `deleted_at` FROM `zz_users` WHERE `zz_users`.`idanagrafica` = '.prepare($id_record).'

UNION
SELECT `or_ordini`.`id`, `or_ordini`.`data`, `or_ordini`.`numero`, `or_ordini`.`numero_esterno`, `or_tipiordine`.`descrizione` AS tipo_documento, `or_tipiordine`.`dir`, NULL AS `deleted_at` FROM `or_ordini` JOIN `or_tipiordine` ON `or_tipiordine`.`id` = `or_ordini`.`idtipoordine` WHERE `or_ordini`.`idanagrafica` = '.prepare($id_record).'

UNION
SELECT `dt_ddt`.`id`, `dt_ddt`.`data`, `dt_ddt`.`numero`, `dt_ddt`.`numero_esterno`, `dt_tipiddt`.`descrizione` AS tipo_documento, `dt_tipiddt`.`dir`, NULL AS `deleted_at` FROM `dt_ddt` JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt` WHERE `dt_ddt`.`idanagrafica` = '.prepare($id_record).'

UNION
SELECT `in_interventi`.`id`, `in_interventi`.`data_richiesta`, `in_interventi`.`codice` AS numero, 0 AS numero_esterno, "Intervento" AS tipo_documento, 0 AS dir, in_interventi.deleted_at AS `deleted_at` FROM `in_interventi` LEFT JOIN `in_interventi_tecnici` ON `in_interventi`.`id` = `in_interventi_tecnici`.`idintervento` WHERE `in_interventi`.`id` IN (SELECT `idintervento` FROM `in_interventi_tecnici` WHERE `idtecnico` = '.prepare($id_record).') OR `in_interventi`.`idanagrafica` = '.prepare($id_record).'

UNION
SELECT `co_contratti`.`id`, `co_contratti`.`data_bozza`, `co_contratti`.`numero`, 0 AS numero_esterno , "Contratto" AS tipo_documento, 0 AS dir, NULL AS `deleted_at` FROM `co_contratti` WHERE `co_contratti`.`idanagrafica` = '.prepare($id_record).'

UNION
SELECT `co_preventivi`.`id`, `co_preventivi`.`data_bozza`, `co_preventivi`.`numero`, 0 AS numero_esterno , "Preventivo" AS tipo_documento, 0 AS dir, NULL AS `deleted_at` FROM `co_preventivi` WHERE `co_preventivi`.`idanagrafica` = '.prepare($id_record).'

ORDER BY `data`');

if (!empty($elementi)) {
    echo '
<div class="box box-warning collapsable collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-warning"></i> '.tr('Documenti collegati: _NUM_', [
            '_NUM_' => count($elementi),
        ]).'</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <ul>';

    foreach ($elementi as $elemento) {
        $descrizione = tr('_DOC_  _NUM_ del _DATE_ _DELETED_AT_', [
        '_DOC_' => $elemento['tipo_documento'],
        '_NUM_' => !empty($elemento['numero_esterno']) ? $elemento['numero_esterno'] : $elemento['numero'],
        '_DATE_' => Translator::dateToLocale($elemento['data']),
        '_DELETED_AT_' => (!empty($elemento['deleted_at']) ? tr('Eliminato il:').' '.Translator::dateToLocale($elemento['deleted_at']) : ''),
    ]);

        //se non è un preventivo è un ddt o una fattura
        //se non è un ddt è una fattura.
        if (in_array($elemento['tipo_documento'], ['Utente'])) {
            $modulo = 'Utenti e permessi';
        } elseif (in_array($elemento['tipo_documento'], ['Intervento'])) {
            $modulo = 'Interventi';
        } elseif (in_array($elemento['tipo_documento'], ['Preventivo'])) {
            $modulo = 'Preventivi';
        } elseif (in_array($elemento['tipo_documento'], ['Contratto'])) {
            $modulo = 'Contratti';
        } elseif (in_array($elemento['tipo_documento'], ['Ordine cliente', 'Ordine fornitore'])) {
            $modulo = ($elemento['dir'] == 'entrata') ? 'Ordini cliente' : 'Ordini fornitore';
        } elseif (in_array($elemento['tipo_documento'], ['Ddt in uscita', 'Ddt in entrata'])) {
            $modulo = ($elemento['dir'] == 'entrata') ? 'Ddt di vendita' : 'Ddt di acquisto';
        } else {
            $modulo = ($elemento['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto';
        }

        $id = $elemento['id'];

        echo '
            <li>'.Modules::link($modulo, $id, $descrizione).'</li>';
    }

    echo '
        </ul>
    </div>
</div>';
}

if (empty($record['deleted_at'])) {
    if (!in_array($id_azienda, $tipi_anagrafica)) {
        if (!empty($elementi)) {
            echo '
<div class="alert alert-error">
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
	$(document).ready( function() {
		$(".colorpicker").colorpicker().on("changeColor", function() {
			$("#colore").parent().find(".square").css("background", $("#colore").val());
		});

		$("#colore").parent().find(".square").css("background", $("#colore").val());

		// Abilito solo ragione sociale oppure solo cognome-nome in base a cosa compilo
		$('#nome, #cognome').bind("keyup change", function(e) {
			if ($('#nome').val() == '' && $('#cognome').val() == '' ){
                $('#nome, #cognome').prop('disabled', true).prop('required', false);
				$('#ragione_sociale').prop('disabled', false).prop('required', true);
				$('#ragione_sociale').focus();
			}else{
                $('#nome, #cognome').prop('disabled', false).prop('required', true);
				$('#ragione_sociale').prop('disabled', true).prop('required', false);
			}
		});

        $('#ragione_sociale').bind("keyup change", function(e) {
			if ($('#ragione_sociale').val() == '' ){
                $('#nome, #cognome').prop('disabled', false).prop('required', true);
                $('#ragione_sociale').prop('disabled', true).prop('required', false);
				$('#cognome').focus();
			}else{
                $('#nome, #cognome').prop('disabled', true).prop('required', false);
				$('#ragione_sociale').prop('disabled', false).prop('required', true);
				$('#ragione_sociale').focus();
			}
		});

        $('#ragione_sociale, #cognome').trigger('keyup');
	});
</script>

