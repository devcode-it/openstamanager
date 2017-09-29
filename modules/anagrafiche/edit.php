<?php

include_once __DIR__.'/../../core.php';

$fornitore = in_array('Fornitore', explode(',', $records[0]['tipianagrafica']));
$cliente = in_array('Cliente', explode(',', $records[0]['tipianagrafica']));

$google = Settings::get('Google Maps API key');

if (!empty($google)) {
    echo '
<script src="http://maps.googleapis.com/maps/api/js?libraries=places&key='.$google.'"></script>';
}

if (!$cliente) {
    $plugins = $dbo->fetchArray("SELECT id FROM zz_plugins WHERE name='Impianti del cliente' OR name='Ddt del cliente'");

    foreach ($plugins as $plugin) {
        echo '
<script>
    $("#link-tab_'.$plugin['id'].'").addClass("disabled");
</script>';
    }

}

?><form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI ANAGRAFICI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati anagrafici'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>

			<div class="row">
				<div class="col-md-8">
					{[ "type": "text", "label": "<?php echo tr('Ragione sociale'); ?>", "name": "ragione_sociale", "required": 1, "value": "$ragione_sociale$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Tipologia'); ?>", "name": "tipo", "values": "list=\"\": \"<?php echo tr('Non specificato'); ?>\", \"Azienda\": \"<?php echo tr('Azienda'); ?>\", \"Privato\": \"<?php echo tr('Privato'); ?>\", \"Ente pubblico\": \"<?php echo tr('Ente pubblico'); ?>\"", "value": "$tipo$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Partita IVA'); ?>", "maxlength": 13, "name": "piva", "class": "text-center", "value": "$piva$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Codice fiscale'); ?>", "maxlength": 16, "name": "codice_fiscale", "class": "text-center", "value": "$codice_fiscale$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Codice anagrafica'); ?>", "name": "codice", "required": 1, "class": "text-center", "value": "$codice$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Luogo di nascita'); ?>", "name": "luogo_nascita", "value": "$luogo_nascita$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "date", "label": "<?php echo tr('Data di nascita'); ?>", "maxlength": 10, "name": "data_nascita", "value": "$data_nascita$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Sesso'); ?>", "name": "sesso", "values": "list=\"\": \"Non specificato\", \"M\": \"<?php echo tr('Uomo'); ?>\", \"F\": \"<?php echo tr('Donna'); ?>\"", "value": "$sesso$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Indirizzo'); ?>", "name": "indirizzo", "value": "$indirizzo$" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Indirizzo2'); ?>", "name": "indirizzo2", "value": "$indirizzo2$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Nazione'); ?>", "name": "id_nazione", "values": "query=SELECT id AS id, nome AS descrizione FROM an_nazioni ORDER BY nome ASC", "value": "$id_nazione$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo tr('C.A.P.'); ?>", "name": "cap", "maxlength": 5, "class": "text-center", "value": "$cap$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Città'); ?>", "name": "citta", "class": "text-center", "value": "$citta$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo tr('Provincia'); ?>", "name": "provincia", "maxlength": 2, "class": "text-center", "value": "$provincia$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "number", "label": "<?php echo tr('Km'); ?>", "name": "km", "maxlength": 4, "class": "text-center", "value": "$km$" ]}
				</div>
			</div>
		</div>
	</div>



	<!-- CONTATTI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Contatti'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Telefono'); ?>", "name": "telefono", "class": "text-center", "value": "$telefono$", "icon-before": "<i class='fa fa-phone'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Fax'); ?>", "name": "fax", "class": "text-center", "value": "$fax$", "icon-before": "<i class='fa fa-fax'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Cellulare'); ?>", "name": "cellulare", "class": "text-center", "value": "$cellulare$", "icon-before": "<i class='fa fa-mobile'></i>" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Email'); ?>", "name": "email", "class": "email-mask", "placeholder":"casella@dominio.ext", "value": "$email$", "icon-before": "<i class='fa fa-envelope'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Sito web'); ?>", "name": "sitoweb", "value": "$sitoweb$", "icon-before": "<i class='fa fa-globe'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Zona'); ?>", "name": "idzona", "values": "query=SELECT id, CONCAT_WS( ' - ', nome, descrizione) AS descrizione FROM an_zone ORDER BY descrizione ASC", "value": "$idzona$" ]}
				</div>
			</div>
		</div>
	</div>

<?php

if ($cliente || $fornitore) {
    ?>
	<!-- INTERVENTI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Informazioni predefinite'); ?></h3>
		</div>

		<div class="panel-body">

<?php
if ($fornitore) {
        ?>
            <div class="row">
				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Pagamento predefinito (per acquisti)'); ?>", "name": "idpagamento_acquisti", "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento_acquisti$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Listino articoli (per acquisti)'); ?>", "name": "idlistino_acquisti", "values": "query=SELECT id, nome AS descrizione FROM mg_listini ORDER BY nome ASC", "value": "$idlistino_acquisti$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Iva predefinita (per acquisti)'); ?>", "name": "idiva_acquisti", "values": "query=SELECT id, descrizione FROM co_iva ORDER BY descrizione ASC", "value": "$idiva_acquisti$" ]}
                </div>
            </div>
<?php
    }

    if ($cliente) {
        ?>
            <div class="row">
                <div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Pagamento predefinito (per vendite)'); ?>", "name": "idpagamento_vendite", "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento_vendite$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Listino articoli (per vendite)'); ?>", "name": "idlistino_vendite", "values": "query=SELECT id, nome AS descrizione FROM mg_listini ORDER BY nome ASC", "value": "$idlistino_vendite$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Iva predefinita (per vendite)'); ?>", "name": "idiva_vendite", "values": "query=SELECT id, descrizione FROM co_iva ORDER BY descrizione ASC", "value": "$idiva_vendite$" ]}
                </div>
            </div>
<?php
    } ?>

            <div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Indirizzo di fatturazione'); ?>", "name": "idsede_fatturazione", "values": "query=SELECT id, CONCAT_WS(', ', nomesede, citta) AS descrizione FROM an_sedi WHERE idanagrafica='$id_record'  UNION SELECT '0' AS id, 'Sede legale' AS descrizione ORDER BY descrizione", "value": "$idsede_fatturazione$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Tipo attività'); ?>", "name": "idtipointervento_default", "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione ASC", "value": "$idtipointervento_default$" ]}
				</div>

                <div class="col-md-3">
                  {[ "type": "select", "label": "Agente principale", "name": "idagente", "values": "query=SELECT an_anagrafiche.idanagrafica AS id, IF(deleted=1, CONCAT(ragione_sociale, ' (Eliminato)'), ragione_sociale ) AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE (descrizione='Agente' AND deleted=0)<?php echo isset($records[0]['idagente']) ? 'OR (an_anagrafiche.idanagrafica = '.prepare($records[0]['idagente']).'AND deleted=1) ' : ''; ?>ORDER BY ragione_sociale", "value": "$idagente$" ]}
              </div>
			</div>
		</div>
	</div>
<?php
}
?>

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Informazioni aggiuntive'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>


			<div class="row">
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Codice registro imprese'); ?>", "name": "codiceri", "value": "$codiceri$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Codice R.E.A.').'<small>('.tr('provincia/C.C.I.A.A.').')</small>'; ?>", "name": "codicerea", "value": "$codicerea$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Num. iscr. C.C.I.A.A.'); ?>", "name": "cciaa", "value": "$cciaa$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Città iscr. C.C.I.A.A.'); ?>", "name": "cciaa_citta", "value": "$cciaa_citta$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Appoggio bancario'); ?>", "name": "appoggiobancario", "value": "$appoggiobancario$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo tr('Filiale banca'); ?>", "name": "filiale", "value": "$filiale$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Codice IBAN'); ?>", "name": "codiceiban", "value": "$codiceiban$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo tr('Codice BIC'); ?>", "name": "bic", "value": "$bic$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo tr('Dicitura fissa fattura'); ?>", "name": "diciturafissafattura", "value": "$diciturafissafattura$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Foro di competenza'); ?>", "name": "foro_competenza", "value": "$foro_competenza$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Settore merceologico'); ?>", "name": "settore", "value": "$settore$" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Marche trattate'); ?>", "name": "marche", "value": "$marche$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Num. dipendenti'); ?>", "name": "dipendenti", "value": "$dipendenti$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Num. macchine'); ?>", "name": "macchine", "value": "$macchine$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Num. iscr. tribunale'); ?>", "name": "iscrizione_tribunale", "value": "$iscrizione_tribunale$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Num. iscr. albo artigiani'); ?>", "name": "n_alboartigiani", "value": "$n_alboartigiani$" ]}
				</div>
			</div>


			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "multiple": "1", "label": "<?php echo tr('Tipo di anagrafica'); ?>", "name": "idtipoanagrafica[]", "values": "query=SELECT idtipoanagrafica AS id, descrizione FROM an_tipianagrafiche WHERE idtipoanagrafica NOT IN (SELECT DISTINCT(x.idtipoanagrafica) FROM an_tipianagrafiche_anagrafiche x INNER JOIN an_tipianagrafiche t ON x.idtipoanagrafica = t.idtipoanagrafica INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = x.idanagrafica WHERE t.descrizione = 'Azienda'  AND deleted = 0) ORDER BY descrizione", "value": "$idtipianagrafica$" ]}
<?php
if (str_contains($records[0]['idtipianagrafica'], $id_azienda)) {
    echo '
                    <p>'.tr('Questa anagrafica appartiene alla tipologia "Azienda"').'.</p>';
}
?>
				</div>
				<?php
                if (in_array('Tecnico', explode(',', $records[0]['tipianagrafica']))) {
                    ?>
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Colore'); ?>", "name": "colore", "class": "colorpicker text-center", "value": "$colore$", "extra": "maxlength='7'", "icon-after": "<div class='img-circle square'></div>" ]}
				</div>
				<?php
                } ?>
				<?php
                if (in_array('Cliente', explode(',', $records[0]['tipianagrafica']))) {
                    ?>
					<div class="col-md-3">
                        {[ "type": "select", "label": "Agenti secondari", "multiple": "1", "name": "idagenti[]", "values": "query=SELECT an_anagrafiche.idanagrafica AS id,  IF(deleted=1, CONCAT(ragione_sociale, ' (Eliminato)'), ragione_sociale ) AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE (descrizione='Agente' AND deleted=0 AND an_anagrafiche.idanagrafica NOT IN (SELECT idagente FROM an_anagrafiche WHERE  idanagrafica = <?php echo prepare($records[0]['idanagrafica']); ?> )) OR (an_anagrafiche.idanagrafica IN (SELECT idagente FROM an_anagrafiche_agenti WHERE idanagrafica =  <?php echo prepare($records[0]['idanagrafica']); ?> ) ) ORDER BY ragione_sociale", "value": "$idagenti$" ]}
					</div>

					<div class="col-md-3">
						{[ "type": "select", "label": "<?php echo tr('Relazione con il cliente'); ?>", "name": "idrelazione", "values": "query=SELECT id, descrizione, colore AS _bgcolor_ FROM an_relazioni ORDER BY descrizione", "value": "$idrelazione$" ]}
					</div>
				<?php
                } ?>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Capitale sociale'); ?>", "name": "capitale_sociale", "value": "$capitale_sociale$" ]}
				</div>
			</div>


			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
				</div>
            </div>
<?php

if (!empty($google)) {
    echo '
            <div class="row">
				<div class="col-md-9">
					<div class="row">
                        <div class="col-md-4" id="geocomplete">
                            {[ "type": "text", "label": "'.tr('Indirizzo Google').'", "name": "gaddress", "value": "$gaddress$", "extra": "data-geo=\'formatted_address\'" ]}
                        </div>

                        <div class="col-md-4">
                            {[ "type": "text", "label": "'.tr('Latitudine').'", "name": "lat", "value": "$lat$", "extra": "data-geo=\'lat\'", "class": "text-right" ]}
                        </div>

                        <div class="col-md-4">
                            {[ "type": "text", "label": "'.tr('Longitudine').'", "name": "lng", "value": "$lng$", "extra": "data-geo=\'lng\'", "class": "text-right" ]}
                        </div>
                    </div>
                </div>';

    // Calcola percorso
    if (empty($records[0]['gaddress']) || (empty($records[0]['lat']) && empty($records[0]['lng']))) {
        echo '
                <div class="col-md-3">
                    <label>&nbsp;</label><br>
                    <a class="btn btn-info" onclick="window.open(\'https://maps.google.com/maps/search/\'+encodeURI( $(\'#indirizzo\').val() )+\', \'+encodeURI( $(\'#citta\').val() ) );"><i class="fa fa-map-marker"></i> Cerca su Google Maps...</a>
                </div>';
    }

    echo '
            </div>';

    if (!empty($records[0]['gaddress']) || (!empty($records[0]['lat']) && !empty($records[0]['lng']))) {
        echo '
            <div id="map" style="height:400px; width:100%"></div>';
    }
} else {
    echo '
            <div class="alert alert-info">
                '.Modules::link('Impostazioni', $dbo->fetchArray("SELECT `idimpostazione` FROM `zz_settings` WHERE sezione='Generali'")[0]['idimpostazione'], tr('Per abilitare la visualizzazione delle anagrafiche nella mappa, inserire la Google Maps API Key nella scheda Impostazioni')).'.
            </div>';
}

?>
		</div>
	</div>
</form>

<?php
if (!str_contains($records[0]['idtipianagrafica'], $id_azienda)) {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
}
?>

<script>
	$(document).ready( function(){
		$('.colorpicker').colorpicker().on('changeColor', function(){
			$('#colore').parent().find('.square').css('background', $('#colore').val());
		});

		$('#colore').parent().find('.square').css('background', $('#colore').val());

        $("#geocomplete input").geocomplete({
            map: $('#map').length ? "#map" : false,
            location: $('#gaddress').val() ? $('#gaddress').val() : [$('#lat').val(), $('#lng').val()],
            details: ".details",
            detailsAttribute: "data-geo"
        }).bind("geocode:result", function (event, result) {
			$("#lat").val(result.geometry.location.lat());
			$("#lng").val(result.geometry.location.lng());
        });
	});
</script>

