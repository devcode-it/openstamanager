<?php

include_once __DIR__.'/../../core.php';

?>
<form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI ANAGRAFICI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _('Dati anagrafici'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo _('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>

			<div class="row">
				<div class="col-md-8">
					{[ "type": "text", "label": "<?php echo _('Ragione sociale'); ?>", "name": "ragione_sociale", "required": 1, "value": "$ragione_sociale$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo _('Tipologia'); ?>", "name": "tipo", "values": "list=\"\": \"<?php echo _('Non specificato'); ?>\", \"Azienda\": \"<?php echo _('Azienda'); ?>\", \"Privato\": \"<?php echo _('Privato'); ?>\", \"Ente pubblico\": \"<?php echo _('Ente pubblico'); ?>\"", "value": "$tipo$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo _('Partita IVA'); ?>", "maxlength": 13, "name": "piva", "class": "text-center", "value": "$piva$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo _('Codice fiscale'); ?>", "maxlength": 16, "name": "codice_fiscale", "class": "text-center", "value": "$codice_fiscale$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo _('Codice anagrafica'); ?>", "name": "codice", "required": 1, "class": "text-center", "value": "$codice$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo _('Luogo di nascita'); ?>", "name": "luogo_nascita", "value": "$luogo_nascita$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "date", "label": "<?php echo _('Data di nascita'); ?>", "maxlength": 10, "name": "data_nascita", "value": "$data_nascita$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo _('Sesso'); ?>", "name": "sesso", "values": "list=\"\": \"Non specificato\", \"M\": \"<?php echo _('Uomo'); ?>\", \"F\": \"<?php echo _('Donna'); ?>\"", "value": "$sesso$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo _('Indirizzo'); ?>", "name": "indirizzo", "value": "$indirizzo$" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo _('Indirizzo2'); ?>", "name": "indirizzo2", "value": "$indirizzo2$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Nazione'); ?>", "name": "id_nazione", "values": "query=SELECT id AS id, nome AS descrizione FROM an_nazioni ORDER BY nome ASC", "value": "$id_nazione$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo _('C.A.P.'); ?>", "name": "cap", "maxlength": 5, "class": "text-center", "value": "$cap$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('Città'); ?>", "name": "citta", "class": "text-center", "value": "$citta$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo _('Provincia'); ?>", "name": "provincia", "maxlength": 2, "class": "text-center", "value": "$provincia$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "number", "label": "<?php echo _('Km'); ?>", "name": "km", "maxlength": 4, "class": "text-center", "value": "$km$" ]}
				</div>
			</div>
		</div>
	</div>



	<!-- CONTATTI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _('Contatti'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo _('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo _('Telefono'); ?>", "name": "telefono", "class": "text-center", "value": "$telefono$", "icon-before": "<i class='fa fa-phone'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo _('Fax'); ?>", "name": "fax", "class": "text-center", "value": "$fax$", "icon-before": "<i class='fa fa-fax'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo _('Cellulare'); ?>", "name": "cellulare", "class": "text-center", "value": "$cellulare$", "icon-before": "<i class='fa fa-mobile'></i>" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo _('Email'); ?>", "name": "email", "class": "email-mask", "placeholder":"casella@dominio.ext", "value": "$email$", "icon-before": "<i class='fa fa-envelope'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo _('Sito web'); ?>", "name": "sitoweb", "value": "$sitoweb$", "icon-before": "<i class='fa fa-globe'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo _('Zona'); ?>", "name": "idzona", "values": "query=SELECT id, CONCAT_WS( ' - ', nome, descrizione) AS descrizione FROM an_zone ORDER BY descrizione ASC", "value": "$idzona$" ]}
				</div>
			</div>
		</div>
	</div>

<?php
$fornitore = in_array('Fornitore', explode(',', $records[0]['tipianagrafica']));
$cliente = in_array('Cliente', explode(',', $records[0]['tipianagrafica']));
if ($cliente || $fornitore) {
    ?>
	<!-- INTERVENTI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _('Informazioni predefinite'); ?></h3>
		</div>

		<div class="panel-body">

			<div class="row">
<?php
if ($fornitore) {
        ?>
				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo _('Pagamento predefinito per acquisti'); ?>", "name": "idpagamento_acquisti", "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento_acquisti$" ]}
				</div>
<?php

    }

    if ($cliente) {
        ?>
                <div class="col-md-4">
					{[ "type": "select", "label": "<?php echo _('Pagamento predefinito per vendite'); ?>", "name": "idpagamento_vendite", "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento_vendite$" ]}
				</div>
<?php

    } ?>
				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo _('Iva predefinita'); ?>", "name": "idiva", "values": "query=SELECT id, descrizione FROM co_iva ORDER BY descrizione ASC", "value": "$idiva$" ]}
				</div>
			</div>


			<div class="row">
                <div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Listino articoli'); ?>", "name": "idlistino", "values": "query=SELECT id, nome AS descrizione FROM mg_listini ORDER BY nome ASC", "value": "$idlistino$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Indirizzo di fatturazione'); ?>", "name": "idsede_fatturazione", "values": "query=SELECT id, CONCAT_WS(', ', nomesede, citta) AS descrizione FROM an_sedi WHERE idanagrafica='$id_record'  UNION SELECT '0' AS id, 'Sede legale' AS descrizione ORDER BY descrizione", "value": "$idsede_fatturazione$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Tipo attività'); ?>", "name": "idtipointervento_default", "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione ASC", "value": "$idtipointervento_default$" ]}
				</div>

				<!--div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Agente principale'); ?>", "name": "idagente", "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN an_anagrafiche_agenti ON an_anagrafiche.idanagrafica=an_anagrafiche_agenti.idagente WHERE an_anagrafiche_agenti.idanagrafica='<?php echo $id_record ?>' AND deleted=0 ORDER BY ragione_sociale", "value": "$idagente$" ]}
				</div-->
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
			<h3 class="panel-title"><?php echo _('Informazioni aggiuntive'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo _('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>


			<div class="row">
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('Codice registro imprese'); ?>", "name": "codiceri", "value": "$codiceri$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('Codice R.E.A.').'<small>('._('provincia/C.C.I.A.A.').')</small>'; ?>", "name": "codicerea", "value": "$codicerea$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('N<sup>o</sup> iscr. C.C.I.A.A.'); ?>", "name": "cciaa", "value": "$cciaa$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('Città iscr. C.C.I.A.A.'); ?>", "name": "cciaa_citta", "value": "$cciaa_citta$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('Appoggio bancario'); ?>", "name": "appoggiobancario", "value": "$appoggiobancario$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo _('Filiale banca'); ?>", "name": "filiale", "value": "$filiale$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('Codice IBAN'); ?>", "name": "codiceiban", "value": "$codiceiban$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo _('Codice BIC'); ?>", "name": "bic", "value": "$bic$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo _('Dicitura fissa fattura'); ?>", "name": "diciturafissafattura", "value": "$diciturafissafattura$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('Foro di competenza'); ?>", "name": "foro_competenza", "value": "$foro_competenza$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('Settore merceologico'); ?>", "name": "settore", "value": "$settore$" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo _('Marche trattate'); ?>", "name": "marche", "value": "$marche$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('N<sup>o</sup> dipendenti'); ?>", "name": "dipendenti", "value": "$dipendenti$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('N<sup>o</sup> macchine'); ?>", "name": "macchine", "value": "$macchine$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('N<sup>o</sup> iscr. tribunale'); ?>", "name": "iscrizione_tribunale", "value": "$iscrizione_tribunale$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('N<sup>o</sup> iscr. albo artigiani'); ?>", "name": "n_alboartigiani", "value": "$n_alboartigiani$" ]}
				</div>
			</div>


			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "multiple": "1", "label": "<?php echo _('Tipo di anagrafica'); ?>", "name": "idtipoanagrafica[]", "values": "query=SELECT idtipoanagrafica AS id, descrizione FROM an_tipianagrafiche WHERE idtipoanagrafica NOT IN (SELECT DISTINCT(x.idtipoanagrafica) FROM an_tipianagrafiche_anagrafiche x INNER JOIN an_tipianagrafiche t ON x.idtipoanagrafica = t.idtipoanagrafica INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = x.idanagrafica  WHERE t.descrizione = 'Azienda'  AND deleted = 0) OR idtipoanagrafica IN (SELECT DISTINCT(z.idtipoanagrafica) FROM an_tipianagrafiche_anagrafiche z WHERE idanagrafica = <?php echo $records[0]['idanagrafica']; ?>) ORDER BY descrizione", "value": "$idtipianagrafica$"<?php if (strpos($records[0]['idtipianagrafica'], $id_azienda) !== false) {
    echo ', "readonly": 1';
} ?> ]}
				</div>
				<?php
                if (in_array('Tecnico', explode(',', $records[0]['tipianagrafica']))) {
                    ?>
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('Colore'); ?>", "name": "colore", "class": "colorpicker text-center", "value": "$colore$", "extra": "maxlength='7'", "icon-after": "<div class='img-circle square'></div>" ]}
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
						{[ "type": "select", "label": "<?php echo _('Relazione con il cliente'); ?>", "name": "idrelazione", "values": "query=SELECT id, descrizione, colore AS _bgcolor_ FROM an_relazioni ORDER BY descrizione", "value": "$idrelazione$" ]}
					</div>
				<?php

                } ?>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('Capitale sociale'); ?>", "name": "capitale_sociale", "value": "$capitale_sociale$" ]}
				</div>


			</div>


			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo _('Note'); ?>", "name": "note", "value": "$note$" ]}
				</div>
			</div>
		</div>
	</div>
</form>
<?php
if (strpos($records[0]['idtipianagrafica'], $id_azienda) === false) {
                    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '._('Elimina').'
</a>';
                }
?>

<script>
	$(document).ready( function(){
		$('.colorpicker').colorpicker().on('changeColor', function(){
			$('#colore').parent().find('.square').css( 'background', $('#colore').val() );
		});

		$('#colore').parent().find('.square').css( 'background', $('#colore').val() );
	});
</script>

