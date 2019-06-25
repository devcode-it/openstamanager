<?php

include_once __DIR__.'/../../core.php';

$block_edit = !empty($note_accredito) || $record['stato'] == 'Emessa' || $record['stato'] == 'Pagato' || $record['stato'] == 'Parzialmente pagato';

$rs = $dbo->fetchArray('SELECT co_tipidocumento.descrizione, dir FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento WHERE co_documenti.id='.prepare($id_record));
$dir = $rs[0]['dir'];
$tipodoc = $rs[0]['descrizione'];

unset($_SESSION['superselect']['idanagrafica']);
unset($_SESSION['superselect']['idsede_partenza']);
unset($_SESSION['superselect']['idsede_destinazione']);
$_SESSION['superselect']['idsede_partenza'] = $record['idsede_partenza'];
$_SESSION['superselect']['idsede_destinazione'] = $record['idsede_destinazione'];
$_SESSION['superselect']['idanagrafica'] = $record['idanagrafica'];
$_SESSION['superselect']['ddt'] = $dir;
$_SESSION['superselect']['split_payment'] = $record['split_payment'];

if ($dir == 'entrata') {
    $conto = 'vendite';
} else {
    $conto = 'acquisti';
}

?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- INTESTAZIONE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Intestazione'); ?></h3>
		</div>

		<div class="panel-body">

			<?php

                if ($dir == 'entrata') {
                    $rs2 = $dbo->fetchArray('SELECT piva, codice_fiscale, citta, indirizzo, cap, provincia, id_nazione, tipo FROM an_anagrafiche WHERE idanagrafica='.prepare($record['idanagrafica']));
                    $campi_mancanti = [];

                    //di default è un azienda e chiedo la partita iva
                    if (empty($rs2[0]['piva']) and (empty($rs2[0]['tipo']) or $rs2[0]['tipo'] == 'Azienda')) {
                        array_push($campi_mancanti, 'Partita IVA');
                    }

                    //se è un privato o un ente pubblico controllo il codice fiscale
                    if (($rs2[0]['tipo'] == 'Privato' or $rs2[0]['tipo'] == 'Ente pubblico') and empty($rs2[0]['codice_fiscale'])) {
                        array_push($campi_mancanti, 'Codice fiscale');
                    }

                    if ($rs2[0]['citta'] == '') {
                        array_push($campi_mancanti, 'Città');
                    }
                    if ($rs2[0]['indirizzo'] == '') {
                        array_push($campi_mancanti, 'Indirizzo');
                    }
                    if ($rs2[0]['cap'] == '') {
                        array_push($campi_mancanti, 'C.A.P.');
                    }
                    if (empty($rs2[0]['id_nazione'])) {
                        array_push($campi_mancanti, 'Nazione');
                    }

                    if (sizeof($campi_mancanti) > 0) {
                        echo "<div class='alert alert-warning'><i class='fa fa-warning'></i> Prima di procedere alla stampa completa i seguenti campi dell'anagrafica Cliente: <b>".implode(', ', $campi_mancanti).'</b><br/>
						'.Modules::link('Anagrafiche', $record['idanagrafica'], tr('Vai alla scheda anagrafica'), null).'</div>';
                    }
                }
            ?>

			<div class="row">
                <?php
                if ($dir == 'uscita') {
                    echo '
                				<div class="col-md-3">
                					{[ "type": "text", "label": "'.tr('Numero fattura/protocollo').'", "required": 1, "name": "numero","class": "text-center alphanumeric-mask", "value": "$numero$" ]}
                                </div>';
                    $label = tr('Numero fattura del fornitore');
                } else {
                    $label = tr('Numero fattura');
                }
                ?>

				<!-- id_segment -->
				{[ "type": "hidden", "label": "Segmento", "name": "id_segment", "class": "text-center", "value": "$id_segment$" ]}

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo $label; ?>", "name": "numero_esterno", "class": "text-center", "value": "$numero_esterno$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo tr('Data emissione'); ?>", "name": "data", "required": 1, "value": "$data$" ]}
				</div>

<?php

$query = 'SELECT * FROM co_statidocumento';
if (empty($record['is_fiscale'])) {
    $query .= " WHERE descrizione = 'Bozza'";

    $plugin = $dbo->fetchArray("SELECT id FROM zz_plugins WHERE name='Fatturazione Elettronica' AND idmodule_to = ".prepare($id_module));
    echo '<script>$("#link-tab_'.$plugin[0]['id'].'").addClass("disabled");</script>';
}

?>
				<?php if ($dir == 'uscita') {
    ?>
					
				<div class="col-md-2">
					{[ "type": "date", "label": "<?php echo tr('Data registrazione'); ?>", "name": "data_registrazione", "required": 0, "value": "$data_registrazione$" ]}
				</div>

                <div class="col-md-2">
                    {[ "type": "date", "label": "<?php echo tr('Data competenza'); ?>", "name": "data_competenza", "required": 0, "value": "$data_competenza$" ]}
                </div>
				
				<?php
} ?>

                <div class="col-md-3">
					<?php
                    if ($dir == 'entrata') {
                        ?>
					{[ "type": "select", "label": "<?php echo tr('Stato FE'); ?>", "name": "codice_stato_fe", "required": 0, "values": "query=SELECT codice as id, CONCAT_WS(' - ',codice,descrizione) as text FROM fe_stati_documento", "value": "$codice_stato_fe$", "disabled": <?php echo intval(Plugins\ExportFE\Connection::isEnabled()); ?>, "class": "unblockable", "help": "<?php echo (!empty($record['data_stato_fe'])) ? Translator::timestampToLocale($record['data_stato_fe']) : ''; ?>" ]}
					<?php
                    }
                    ?>
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					<?php

                    echo Modules::link('Anagrafiche', $record['idanagrafica'], null, null, 'class="pull-right"');

                    if ($dir == 'entrata') {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "ajax-source": "clienti", "help": "<?php echo tr("In caso di autofattura indicare l'azienda: ").stripslashes($database->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica = '.prepare(setting('Azienda predefinita')))['ragione_sociale']); ?>", "value": "$idanagrafica$" ]}
					<?php
                    } else {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Fornitore'); ?>", "name": "idanagrafica", "required": 1, "ajax-source": "fornitori", "value": "$idanagrafica$" ]}
					<?php
                    }
                    ?>
				</div>
                
                <?php
                // Conteggio numero articoli fatture
                $articolo = $dbo->fetchArray('SELECT mg_articoli.id FROM ((mg_articoli INNER JOIN co_righe_documenti ON mg_articoli.id=co_righe_documenti.idarticolo) INNER JOIN co_documenti ON co_documenti.id=co_righe_documenti.iddocumento) WHERE co_documenti.id='.prepare($id_record));
                if ($dir == 'uscita') {
                    ?>
                    <div class="col-md-3">
                        {[ "type": "select", "label": "<?php echo tr('Partenza merce'); ?>", "name": "idsede_partenza", "ajax-source": "sedi", "placeholder": "Sede legale", "value": "$idsede_partenza$", "icon-after": "add|<?php echo Modules::get('Anagrafiche')['id']; ?>|id_plugin=<?php echo Plugins::get('Sedi')['id']; ?>&id_parent=<?php echo $record['idanagrafica']; ?>||<?php echo (intval($block_edit)) ? 'disabled' : ''; ?>" ]}
                    </div>
                    
                    <div class="col-md-3">
                        {[ "type": "select", "label": "<?php echo tr('Destinazione merce'); ?>", "name": "idsede_destinazione", "ajax-source": "sedi_azienda",  "value": "$idsede_destinazione$", "readonly": "<?php echo (sizeof($articolo)) ? 1 : 0; ?>" ]}
                    </div>
                <?php
                } else {
                    ?>
                    <div class="col-md-3">
                        {[ "type": "select", "label": "<?php echo tr('Partenza merce'); ?>", "name": "idsede_partenza", "ajax-source": "sedi_azienda", "placeholder": "Sede legale", "value": "$idsede_partenza$", "readonly": "<?php echo (sizeof($articolo)) ? 1 : 0; ?>"  ]}
                    </div>
                    
                    <div class="col-md-3">
                        {[ "type": "select", "label": "<?php echo tr('Destinazione merce'); ?>", "name": "idsede_destinazione", "ajax-source": "sedi",  "value": "$idsede_destinazione$", "readonly": "", "icon-after": "add|<?php echo Modules::get('Anagrafiche')['id']; ?>|id_plugin=<?php echo Plugins::get('Sedi')['id']; ?>&id_parent=<?php echo $record['idanagrafica']; ?>||<?php echo (intval($block_edit)) ? 'disabled' : ''; ?>" ]}
                    </div>
                <?php
                }
                ?>

				<div class="col-md-3">
					<!-- TODO: Rimuovere possibilità di selezionare lo stato pagato obbligando l'utente ad aggiungere il movimento in prima nota -->
					{[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatodocumento", "required": 1, "values": "query=<?php echo $query; ?>", "value": "$idstatodocumento$", "class": "unblockable", "extra": " onchange = \"if ($('#idstatodocumento option:selected').text()=='Pagato' || $('#idstatodocumento option:selected').text()=='Parzialmente pagato' ){if( confirm('<?php echo tr('Sicuro di voler impostare manualmente la fattura come pagata senza aggiungere il movimento in prima nota?'); ?>') ){ return true; }else{ $('#idstatodocumento').selectSet(<?php echo $record['idstatodocumento']; ?>); }}\" " ]}
				</div>

				<?php if ($dir == 'entrata') {
                        ?>
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Agente di riferimento'); ?>", "name": "idagente", "ajax-source": "agenti", "value": "$idagente_fattura$" ]}
				</div>
				<?php
                    } ?>
			</div>
			<hr>


			<div class="row">
				<div class="col-md-3">
					<!-- Nella realtà la fattura accompagnatoria non può esistere per la fatturazione elettronica, in quanto la risposta dal SDI potrebbe non essere immediata e le merci in viaggio. Dunque si può emettere una documento di viaggio valido per le merci ed eventualmente una fattura pro-forma per l'incasso della stessa, emettendo infine la fattura elettronica differita. -->

					{[ "type": "select", "label": "<?php echo tr('Tipo fattura'); ?>", "name": "idtipodocumento", "required": 1, "values": "query=SELECT id, descrizione FROM co_tipidocumento WHERE dir='<?php echo $dir; ?>' AND (reversed = 0 OR id = <?php echo $record['idtipodocumento']; ?>)", "value": "$idtipodocumento$", "readonly": <?php echo intval($record['stato'] != 'Bozza' && $record['stato'] != 'Annullata'); ?>, "help": "<?php echo ($database->fetchOne('SELECT tipo FROM an_anagrafiche WHERE idanagrafica = '.prepare($record['idanagrafica']))['tipo'] == 'Ente pubblico') ? 'FPA12 - fattura verso PA' : 'FPR12 - fattura verso privati'; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Pagamento'); ?>", "name": "idpagamento", "required": 1, "values": "query=SELECT id, CONCAT_WS(' - ', codice_modalita_pagamento_fe, descrizione) AS descrizione, (SELECT id FROM co_banche WHERE id_pianodeiconti3 = co_pagamenti.idconto_<?php echo $conto; ?> LIMIT 0,1) AS idbanca FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento$", "extra": "onchange=\"$('#idbanca').val( $(this).find('option:selected').data('idbanca') ).change(); \" " ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Banca'); ?>", "name": "idbanca", "values": "query=SELECT id, CONCAT (nome, ' - ' , iban) AS descrizione FROM co_banche WHERE deleted_at IS NULL ORDER BY nome ASC", "value": "$idbanca$", "icon-after": "add|<?php echo Modules::get('Banche')['id']; ?>||", "extra": " <?php echo ($record['stato'] == 'Bozza') ? '' : 'disabled'; ?> " ]}
				</div>


                <?php
                if ($record['stato'] != 'Bozza' && $record['stato'] != 'Annullata') {
                    $ricalcola = true;

                    $scadenze = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE iddocumento = '.prepare($id_record));
                    echo '
                <div class="col-md-3">
                    <p><strong>'.tr('Scadenze').'</strong></p>';
                    foreach ($scadenze as $scadenza) {
                        echo '
                    <p>'.Translator::dateToLocale($scadenza['scadenza']).': ';

                        if ($scadenza['pagato'] == $scadenza['da_pagare']) {
                            echo '
                        <strike>';
                        }

                        echo moneyFormat($scadenza['da_pagare']);

                        if ($scadenza['pagato'] == $scadenza['da_pagare']) {
                            echo '
                        </strike>';
                        }

                        echo '
                    </p>';

                        $ricalcola = empty(floatval($scadenza['pagato'])) && $ricalcola;
                    }

                    if ($fattura->isFE() && $ricalcola && $module['name'] == 'Fatture di acquisto') {
                        echo '
                    <button type="button" class="btn btn-info btn-xs pull-right tip" title="'.tr('Ricalcola le scadenze').'. '.tr('Per ricalcolare correttamente le scadenze, imposta la fattura in stato \'\'Bozza\'\' e correggi il metodo di  come desiderato, poi re-imposta lo stato \'\'Emessa\'\' e utilizza questa funzione').'." id="ricalcola_scadenze">
                        <i class="fa fa-calculator" aria-hidden="true"></i>
                    </button>';
                    }

                    echo '
                </div>';
                }
                ?>
			</div>

            <div class="row">
				<div class="col-md-3">
					{[ "type": "checkbox", "label": "<?php echo tr('Split payment'); ?>", "name": "split_payment", "value": "$split_payment$", "help": "<?php echo tr('Abilita lo split payment per questo documento. Le aliquote iva con natura N6 (reverse charge) non saranno disponibili.'); ?>", "placeholder": "<?php echo tr('Split payment'); ?>" ]}
				</div>

				<?php
                //TODO: Fattura per conto del fornitore (es. cooperative agricole che emettono la fattura per conto dei propri soci produttori agricoli conferenti)
                if ($dir == 'entrata') {
                    ?>
					<div class="col-md-3">
						{[ "type": "checkbox", "label": "<?php echo tr('Fattura per conto terzi'); ?>", "name": "is_fattura_conto_terzi", "value": "$is_fattura_conto_terzi$", "help": "<?php echo tr('Nell\'xml della FE imposta il fornitore ('.stripslashes($database->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica = '.prepare(setting('Azienda predefinita')))['ragione_sociale']).') come cessionario e il cliente come cedente/prestatore.'); ?>", "placeholder": "<?php echo tr('Fattura per conto terzi'); ?>" ]}
					</div>

				<?php
                }
                ?>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Ritenuta contributi'); ?>", "name": "id_ritenuta_contributi", "value": "$id_ritenuta_contributi$", "values": "query=SELECT * FROM co_ritenuta_contributi" ]}
                </div>
            </div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "help": "<?php echo tr('Note visibili anche in fattura.'); ?>", "value": "$note$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note aggiuntive'); ?>", "name": "note_aggiuntive", "help": "<?php echo tr('Note interne.'); ?>", "value": "$note_aggiuntive$", "class": "unblockable" ]}
				</div>
			</div>
		</div>
	</div>

<?php
echo '
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-certificate "></i> '.tr('Marca da bollo').'</h3>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Addebita marca da bollo').'", "name": "addebita_bollo", "value": "$addebita_bollo$" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Marca da bollo automatica').'", "name": "bollo_automatico", "value": "'.intval(!isset($record['bollo'])).'", "help": "'.tr("Seleziona per impostare automaticamente l'importo della marca da bollo").'. '.tr('Applicata solo se il totale della fattura è maggiore di _MONEY_', [
                            '_MONEY_' => moneyFormat(setting("Soglia minima per l'applicazione della marca da bollo")),
                        ]).'.", "placeholder": "'.tr('Bollo automatico').'" ]}
                </div>
                
                <div class="col-md-4">
                    {[ "type": "number", "label": "'.tr('Importo marca da bollo').'", "name": "bollo", "value": "$bollo$", "disabled": '.intval(!isset($record['bollo'])).' ]}
                </div>
  
                <script type="text/javascript">
                    $(document).ready(function() {
                        $("#bollo_automatico").click(function(){
                            $("#bollo").attr("disabled", $(this).is(":checked"));
                        });
                    });
                </script>
            </div>
        </div>
    </div>';

if ($tipodoc == 'Fattura accompagnatoria di vendita') {
    echo '
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-edit"></i> '.tr('Dati Fattura accompagnatoria').'</h3>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Aspetto beni').'", "name": "idaspettobeni", "placeholder": "", "ajax-source": "aspetto-beni", "value": "$idaspettobeni$", "icon-after": "add|'.Modules::get('Aspetto beni')['id'].'||'.(($record['stato'] != 'Bozza') ? 'disabled' : '').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Causale trasporto').'", "name": "idcausalet", "placeholder": "", "ajax-source": "causali", "value": "$idcausalet$", "icon-after": "add|'.Modules::get('Causali')['id'].'||'.(($record['stato'] != 'Bozza') ? 'disabled' : '').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Porto').'", "name": "idporto", "placeholder": "", "values": "query=SELECT id, descrizione FROM dt_porto ORDER BY descrizione ASC", "value": "$idporto$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "text", "label": "'.tr('Num. colli').'", "name": "n_colli", "value": "$n_colli$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Tipo di spedizione').'", "name": "idspedizione", "values": "query=SELECT id, descrizione FROM dt_spedizione ORDER BY descrizione ASC", "value": "$idspedizione$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Vettore').'", "name": "idvettore",  "ajax-source": "vettori",  "value": "$idvettore$", "icon-after": "add|'.Modules::get('Anagrafiche')['id'].'|tipoanagrafica=Vettore|'.((($record['idspedizione'] != 3) and ($record['stato'] == 'Bozza')) ? '' : 'disabled').'", "disabled": '.intval($record['idspedizione'] == 3).', "required": '.intval($record['idspedizione'] != 3).' ]}
                </div>

                <script>
                    $("#idspedizione").change( function(){
                        if ($(this).val() == 3) {
                            $("#idvettore").attr("required", false);
                            $("#idvettore").attr("disabled", true);
                            $("label[for=idvettore]").text("'.tr('Vettore').'");
							$("#idvettore").selectReset("- Seleziona un\'opzione -");
							$("#idvettore").next().next().find("button.bound:nth-child(1)").prop("disabled", true);
                        }else{
                            $("#idvettore").attr("required", true);
                            $("#idvettore").attr("disabled", false);
                            $("label[for=idvettore]").text("'.tr('Vettore').'*");
							$("#idvettore").next().next().find("button.bound:nth-child(1)").prop("disabled", false);
                        }
                    });

					$("#idcausalet").change( function(){
                        if ($(this).val() == 3) {
                            $("#tipo_resa").attr("disabled", false);
                        }else{
							$("#tipo_resa").attr("disabled", true);
                        }
                    });
                </script>';

    $tipo_resa = [
        [
            'id' => 'EXW',
            'text' => 'EXW',
        ],
        [
            'id' => 'FCA',
            'text' => 'FCA',
        ],
        [
            'id' => 'FAS',
            'text' => 'FAS',
        ],
        [
            'id' => 'FOB',
            'text' => 'FOB',
        ],
        [
            'id' => 'CFR',
            'text' => 'CFR',
        ],
        [
            'id' => 'CIF',
            'text' => 'CIF',
        ],
        [
            'id' => 'CPT',
            'text' => 'CPT',
        ],
        [
            'id' => 'CIP',
            'text' => 'CIP',
        ],
        [
            'id' => 'DAF',
            'text' => 'DAF',
        ],
        [
            'id' => 'DES',
            'text' => 'DES',
        ],
        [
            'id' => 'DEQ',
            'text' => 'DEQ',
        ],
        [
            'id' => 'DDU',
            'text' => 'DDU',
        ],
        [
            'id' => 'DDP',
            'text' => 'DDP',
        ],
    ];

    echo '
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Tipo Resa').'", "name": "tipo_resa", "value":"$tipo_resa$", "values": '.json_encode($tipo_resa).', "readonly": '.intval($record['causale_desc'] != 'Reso').' ]}
                </div>

            </div>
        </div>
    </div>';
}
?>
</form>



<!-- RIGHE -->
<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title">Righe</h3>
	</div>

	<div class="panel-body">
		<div class="row">
			<div class="col-md-12">
				<div class="pull-left">
<?php
if (!$block_edit) {
    if (empty($record['ref_documento'])) {
        if ($dir == 'entrata') {
            // Lettura interventi non rifiutati, non fatturati e non collegati a preventivi o contratti
            $int_query = 'SELECT COUNT(*) AS tot FROM in_interventi INNER JOIN in_statiintervento ON in_interventi.idstatointervento=in_statiintervento.idstatointervento WHERE idanagrafica='.prepare($record['idanagrafica']).' AND in_statiintervento.completato=1 AND in_interventi.id NOT IN (SELECT idintervento FROM co_righe_documenti WHERE idintervento IS NOT NULL) AND in_interventi.id_preventivo IS NULL AND in_interventi.id NOT IN (SELECT idintervento FROM co_promemoria WHERE idintervento IS NOT NULL)';
            $interventi = $dbo->fetchArray($int_query)[0]['tot'];

            // Se non trovo niente provo a vedere se ce ne sono per clienti terzi
            if (empty($interventi)) {
                // Lettura interventi non rifiutati, non fatturati e non collegati a preventivi o contratti (clienti terzi)
                $int_query = 'SELECT COUNT(*) AS tot FROM in_interventi INNER JOIN in_statiintervento ON in_interventi.idstatointervento=in_statiintervento.idstatointervento WHERE idclientefinale='.prepare($record['idanagrafica']).' AND in_statiintervento.completato=1 AND in_interventi.id NOT IN (SELECT idintervento FROM co_righe_documenti WHERE idintervento IS NOT NULL) AND in_interventi.id_preventivo IS NULL AND in_interventi.id NOT IN (SELECT idintervento FROM co_promemoria WHERE idintervento IS NOT NULL)';
                $interventi = $dbo->fetchArray($int_query)[0]['tot'];
            }

            echo '
                    <div class="tip" data-toggle="tooltip" title="'.tr('Interventi completati non collegati a preventivi o contratti e che non siano già stati fatturati.').'" style="display:inline;">
                        <a class="btn btn-sm btn-primary '.(!empty($interventi) ? '' : ' disabled').'" data-href="'.$rootdir.'/modules/fatture/add_intervento.php?id_module='.$id_module.'&id_record='.$id_record.'" data-title="Aggiungi intervento">
                            <i class="fa fa-plus"></i> Intervento
                        </a>
                    </div>';

            // Lettura preventivi accettati, in attesa di conferma o in lavorazione
            $prev_query = 'SELECT COUNT(*) AS tot FROM co_preventivi WHERE idanagrafica='.prepare($record['idanagrafica'])." AND idstato IN(SELECT id FROM co_statipreventivi WHERE descrizione='Accettato' OR descrizione='In lavorazione' OR descrizione='In attesa di conferma') AND default_revision=1 AND co_preventivi.id IN (SELECT idpreventivo FROM co_righe_preventivi WHERE co_righe_preventivi.idpreventivo = co_preventivi.id AND (qta - qta_evasa) > 0)";
            $preventivi = $dbo->fetchArray($prev_query)[0]['tot'];
            echo '
                    <div class="tip" data-toggle="tooltip" title="'.tr('Preventivi accettati, in attesa di conferma o in lavorazione.').'" style="display:inline;">
                        <a class="btn btn-sm btn-primary '.(!empty($preventivi) ? '' : ' disabled').'" data-href="'.$rootdir.'/modules/fatture/add_preventivo.php?id_module='.$id_module.'&id_record='.$id_record.'" data-title="Aggiungi preventivo">
                            <i class="fa fa-plus"></i> Preventivo
                        </a>
                    </div>';

            // Lettura contratti accettati, in attesa di conferma o in lavorazione
            $contr_query = 'SELECT COUNT(*) AS tot FROM co_contratti WHERE idanagrafica='.prepare($record['idanagrafica']).' AND idstato IN( SELECT id FROM co_staticontratti WHERE is_fatturabile = 1) AND co_contratti.id IN (SELECT idcontratto FROM co_righe_contratti WHERE co_righe_contratti.idcontratto = co_contratti.id AND (qta - qta_evasa) > 0)';
            $contratti = $dbo->fetchArray($contr_query)[0]['tot'];
            echo '
                    <div class="tip" data-toggle="tooltip" title="'.tr('Contratti accettati, in attesa di conferma o in lavorazione.').'" style="display:inline;">
                        <a class="btn btn-sm btn-primary '.(!empty($contratti) ? '' : ' disabled').'"  data-href="'.$rootdir.'/modules/fatture/add_contratto.php?id_module='.$id_module.'&id_record='.$id_record.'" data-title="Aggiungi contratto">
                            <i class="fa fa-plus"></i> Contratto
                        </a>
                    </div>';
        }

        // Lettura ddt
        $ddt_query = 'SELECT COUNT(*) AS tot FROM dt_ddt WHERE idanagrafica='.prepare($record['idanagrafica']).' AND idstatoddt IN (SELECT id FROM dt_statiddt WHERE descrizione IN(\'Bozza\', \'Evaso\', \'Parzialmente evaso\', \'Parzialmente fatturato\')) AND idtipoddt IN (SELECT id FROM dt_tipiddt WHERE dir='.prepare($dir).') AND dt_ddt.id IN (SELECT idddt FROM dt_righe_ddt WHERE dt_righe_ddt.idddt = dt_ddt.id AND (qta - qta_evasa) > 0)';
        $ddt = $dbo->fetchArray($ddt_query)[0]['tot'];
        echo '
					<a class="btn btn-sm btn-primary'.(!empty($ddt) ? '' : ' disabled').'" data-href="'.$rootdir.'/modules/fatture/add_ddt.php?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="tooltip" data-title="Aggiungi ddt">
						<i class="fa fa-plus"></i> Ddt
					</a>';

        // Lettura ordini
        $ordini_query = 'SELECT COUNT(*) AS tot FROM or_ordini WHERE idanagrafica='.prepare($record['idanagrafica']).' AND idstatoordine IN (SELECT id FROM or_statiordine WHERE descrizione IN(\'Bozza\', \'Evaso\', \'Parzialmente evaso\', \'Parzialmente fatturato\')) AND idtipoordine=(SELECT id FROM or_tipiordine WHERE dir='.prepare($dir).') AND or_ordini.id IN (SELECT idordine FROM or_righe_ordini WHERE or_righe_ordini.idordine = or_ordini.id AND (qta - qta_evasa) > 0)';
        $ordini = $dbo->fetchArray($ordini_query)[0]['tot'];
        echo '
						<a class="btn btn-sm btn-primary'.(!empty($ordini) ? '' : ' disabled').'" data-href="'.$rootdir.'/modules/fatture/add_ordine.php?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="modal" data-title="Aggiungi ordine">
							<i class="fa fa-plus"></i> Ordine
                        </a>';
    }

    // Lettura articoli
    $art_query = 'SELECT COUNT(*) AS tot FROM mg_articoli WHERE attivo = 1';
    if ($dir == 'entrata') {
        $art_query .= ' AND (qta > 0 OR servizio = 1)';
    }

    $articoli = $dbo->fetchArray($art_query)[0]['tot'];
    echo '
                        <a class="btn btn-sm btn-primary'.(!empty($articoli) ? '' : ' disabled').'" data-href="'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&is_articolo" data-toggle="tooltip" data-title="'.tr('Aggiungi articolo').'">
                            <i class="fa fa-plus"></i> '.tr('Articolo').'
                        </a>';

    echo '
                        <a class="btn btn-sm btn-primary" data-href="'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&is_riga" data-toggle="tooltip" data-title="'.tr('Aggiungi riga').'">
                            <i class="fa fa-plus"></i> '.tr('Riga').'
                        </a>';

    echo '
                        <a class="btn btn-sm btn-primary" data-href="'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&is_descrizione" data-toggle="tooltip" data-title="'.tr('Aggiungi descrizione').'">
                            <i class="fa fa-plus"></i> '.tr('Descrizione').'
                        </a>';

    echo '
                        <a class="btn btn-sm btn-primary" data-href="'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&is_sconto" data-toggle="tooltip" data-title="'.tr('Aggiungi sconto/maggiorazione').'">
                            <i class="fa fa-plus"></i> '.tr('Sconto/maggiorazione').'
                        </a>';
}
?>
				</div>

				<div class="pull-right">
					<!-- Stampe -->
<?php
//stampa solo per fatture di vendita
if ($dir == 'entrata') {
    if (sizeof($campi_mancanti) > 0) {
        //echo '{( "name": "button", "type": "print", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'", "class": "btn-info disabled" )}';
    } else {
        //echo '{( "name": "button", "type": "print", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'" )}';
    }
}
?>
				</div>
			</div>
		</div>
		<div class="clearfix"></div>
		<br>

		<div class="row">
			<div class="col-md-12">
<?php
include $docroot.'/modules/fatture/row-list.php';
?>
			</div>
		</div>
	</div>
</div>

<?php
if ($dir == 'uscita' && $fattura->isFE()) {
    echo '
<div class="alert alert-info text-center" id="controlla_totali"><i class="fa fa-spinner fa-spin"></i> '.tr('Controllo sui totali del documento e della fattura elettronica in corso').'...</div>

<script>
    $(document).ready(function() {        
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "post",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "controlla_totali",
            },
            success: function(data){
                data = JSON.parse(data);
             
                var div = $("#controlla_totali");
                div.removeClass("alert-info");

                if (data.stored == null) {
                    div.addClass("alert-info").html("'.tr("Il file XML non contiene il nodo ''ImportoTotaleDocumento'': impossibile controllare corrispondenza dei totali").'.")
                } else if (data.stored == data.calculated){
                    div.addClass("alert-success").html("'.tr('Il totale del file XML corrisponde a quello calcolato dal gestionale').'.")
                } else {
                    div.addClass("alert-warning").html("'.tr('Il totale del file XML non corrisponde a quello calcolato dal gestionale: previsto _XML_, calcolato _CALC_', [
                        '_XML_' => '" + data.stored + " " + globals.currency + "',
                        '_CALC_' => '" + data.calculated + " " + globals.currency + "',
                    ]).'.")
                }
                
            }
        });
    })
</script>';
}
?>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<?php
if ($dir == 'entrata') {
    echo '
<div class="alert alert-info text-center">'.tr('Per allegare un documento alla fattura elettronica caricare il file PDF specificando come categoria "Fattura Elettronica"').'.</div>';
}

echo '
<script type="text/javascript">
	$("#idanagrafica").change(function(){
        session_set("superselect,idanagrafica", $(this).val(), 0);';
        if ($dir == 'entrata') {
            echo '$("#idsede_destinazione").selectReset();';
        }else{
            echo '$("#idsede_partenza").selectReset();';
        }
echo '
	});

    $("#ricalcola_scadenze").click(function(){
        swal({
            title: "'.tr('Desideri ricalcolare le scadenze?').'",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "'.tr('Sì').'"
        }).then(function (result) {
            redirect(globals.rootdir + "/editor.php", {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "ricalcola_scadenze",
                backto: "record-edit",
            }, "post")
        })
    });
</script>';

if (!empty($note_accredito)) {
    echo '
<div class="alert alert-info text-center">'.tr('Note di credito collegate').':';
    foreach ($note_accredito as $nota) {
        $text = tr('Rif. fattura _NUM_ del _DATE_', [
            '_NUM_' => $nota['numero'],
            '_DATE_' => Translator::dateToLocale($nota['data']),
        ]);

        echo '
    <br>'.Modules::link('Fatture di vendita', $nota['id'], $text, $text);
    }
    echo '
</div>';
}

?>

{( "name": "log_email", "id_module": "$id_module$", "id_record": "$id_record$" )}

<?php
// Eliminazione ddt solo se ho accesso alla sede aziendale
$field_name = ( $dir == 'entrata' ) ? 'idsede_partenza' : 'idsede_uscita';
if (in_array($record[$field_name], $user->idsedi)){
?>
    <a class="btn btn-danger ask" data-backto="record-list">
        <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
    </a>
<?php
}

    echo '
<script>

$(".btn-sm[data-toggle=\"tooltip\"]").each(function() {

   $(this).on("click", function() {
        form = $("#edit-form");
        btn = $(this);

        var restore = buttonLoading(btn);

		// Procedo al salvataggio solo se tutti i campi obbligatori sono compilati, altrimenti mostro avviso
	    if (form.parsley().isValid()) {
            content_was_modified = false;

            form.find("input:disabled, select:disabled").removeAttr("disabled");

            $.ajax({
                url: globals.rootdir + "/actions.php?id_module=" + globals.id_module ,
                cache: false,
                type: "POST",
                processData: false,
                dataType : "html",
                data:  form.serialize(),
                success: function(data) {
                    $("#main_loading").fadeOut();

                    buttonRestore(btn, restore);
                },
                error: function(data) {
                    $("#main_loading").fadeOut();

                    swal("'.tr('Errore').'", "'.tr('Errore durante il salvataggio').'", "error");

                    buttonRestore(btn, restore);
                }
            });

	    } else {
			swal({
                type: "error",
                title: "'.tr('Errore').'",
                text:  "'.tr('Alcuni campi obbligatori non sono stati compilati correttamente').'.",
            });

            $("#bs-popup").one("show.bs.modal", function (e) {
                return e.preventDefault();
            });

            buttonRestore(btn, restore);
		}

	});
});
</script>';
?>
