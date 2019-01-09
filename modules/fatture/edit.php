<?php

include_once __DIR__.'/../../core.php';

$block_edit = !empty($note_accredito) || $record['stato'] == 'Emessa';

$rs = $dbo->fetchArray('SELECT co_tipidocumento.descrizione, dir FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento WHERE co_documenti.id='.prepare($id_record));
$dir = $rs[0]['dir'];
$tipodoc = $rs[0]['descrizione'];

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
					
					if ($rs2[0]['codice_fiscale'] == '' and ($rs2[0]['tipo'] == 'Privato' or $rs2[0]['tipo'] == 'Ente pubblico')) {
                        array_push($campi_mancanti, 'Codice fiscale');
                    }
                    else if ($rs2[0]['piva'] == '') {
                        array_push($campi_mancanti, 'Partita IVA');
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
					{[ "type": "text", "label": "<?php echo $label; ?>", "name": "numero_esterno", "class": "alphanumeric-mask text-center", "value": "$numero_esterno$" ]}
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

				<div class="col-md-3">
					<!-- TODO: Rimuovere possibilità di selezionare lo stato pagato obbligando l'utente ad aggiungere il movimento in prima nota -->
					{[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatodocumento", "required": 1, "values": "query=<?php echo $query; ?>", "value": "$idstatodocumento$", "class": "unblockable", "extra": " onchange = \"if ($('#idstatodocumento option:selected').text()=='Pagato' || $('#idstatodocumento option:selected').text()=='Parzialmente pagato' ){if( confirm('<?php echo tr('Sicuro di voler impostare manualmente la fattura come pagata senza aggiungere il movimento in prima nota?'); ?>') ){ return true; }else{ $('#idstatodocumento').selectSet(<?php echo $record['idstatodocumento']; ?>); }}\" " ]}
				</div>

                <div class="col-md-3">
					<?php
                    if ($dir == 'entrata') {
                        ?>
					{[ "type": "select", "label": "<?php echo tr('Stato FE'); ?>", "name": "codice_stato_fe", "required": 0, "values": "query=SELECT codice as id, descrizione as text FROM fe_stati_documento", "value": "$codice_stato_fe$", "disabled": <?php echo intval(Plugins\ExportFE\Connection::isEnabled()); ?>, "class": "unblockable" ]}
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
						{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "ajax-source": "clienti", "value": "$idanagrafica$" ]}
					<?php
                    } else {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Fornitore'); ?>", "name": "idanagrafica", "required": 1, "ajax-source": "fornitori", "value": "$idanagrafica$" ]}
					<?php
                    }
                    ?>
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Riferimento sede'); ?>", "name": "idsede", "ajax-source": "sedi", "placeholder": "Sede legale", "value": "$idsede$" ]}
				</div>

				<?php if ($dir == 'entrata') {
                        ?>
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Agente di riferimento'); ?>", "name": "idagente", "ajax-source": "agenti", "value": "$idagente_fattura$" ]}
				</div>
				<?php
                    } ?>

                <?php
                if ($record['stato'] != 'Bozza' && $record['stato'] != 'Annullata') {
                    $scadenze = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE iddocumento = '.prepare($id_record));
                    echo '
                <div class="col-md-3">
                    <p><strong>'.tr('Scadenze').'</strong></p>';
                    foreach ($scadenze as $scadenza) {
                        echo '
                    <p>'.Translator::dateToLocale($scadenza['scadenza']).': '.Translator::numberToLocale($scadenza['da_pagare']).'&euro;</p>';
                    }
                    echo '
                </div>';
                }
                ?>
			</div>
			<hr>


			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Tipo fattura'); ?>", "name": "idtipodocumento", "required": 1, "values": "query=SELECT id, descrizione FROM co_tipidocumento WHERE dir='<?php echo $dir; ?>' AND (reversed = 0 OR id = <?php echo $record['idtipodocumento']; ?>)", "value": "$idtipodocumento$", "readonly": <?php echo intval($record['stato'] != 'Bozza' && $record['stato'] != 'Annullata'); ?> ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Pagamento'); ?>", "name": "idpagamento", "required": 1, "values": "query=SELECT id, descrizione, (SELECT id FROM co_banche WHERE id_pianodeiconti3 = co_pagamenti.idconto_<?php echo $conto; ?> LIMIT 0,1) AS idbanca FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento$", "extra": "onchange=\"$('#idbanca').val( $(this).find('option:selected').data('idbanca') ).change(); \" " ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Banca'); ?>", "name": "idbanca", "values": "query=SELECT id, CONCAT (nome, ' - ' , iban) AS descrizione FROM co_banche WHERE deleted_at IS NULL ORDER BY nome ASC", "value": "$idbanca$", "icon-after": "add|<?php echo Modules::get('Banche')['id']; ?>||", "extra": " <?php echo ($record['stato'] == 'Bozza') ? '' : 'disabled'; ?> " ]}
				</div>


				<div class="col-md-3">
					<label>&nbsp;</label><br>
<?php

if (!empty($record['is_fiscale'])) {
    // Aggiunta prima nota solo se non c'è già, se non si è in bozza o se il pagamento non è completo
    $n2 = $dbo->fetchNum('SELECT id FROM co_movimenti WHERE iddocumento='.prepare($id_record).' AND primanota=1');

    $rs3 = $dbo->fetchArray('SELECT SUM(da_pagare-pagato) AS differenza, SUM(da_pagare) FROM co_scadenziario GROUP BY iddocumento HAVING iddocumento='.prepare($id_record));
    $differenza = isset($rs3[0]) ? $rs3[0]['differenza'] : null;
    $da_pagare = isset($rs3[0]) ? $rs3[0]['da_pagare'] : null;

    if (($n2 <= 0 && $record['stato'] == 'Emessa') || $differenza != 0) {
        ?>
					<a class="btn btn-primary btn-block  <?php echo (!empty(Modules::get('Prima nota'))) ? '' : 'disabled'; ?>" href="javascript:;" onclick="launch_modal( 'Aggiungi prima nota', '<?php echo $rootdir; ?>/add.php?id_module=<?php echo Modules::get('Prima nota')['id']; ?>&iddocumento=<?php echo $id_record; ?>&dir=<?php echo $dir; ?>', 1 );"><i class="fa fa-euro"></i> <?php echo tr('Aggiungi prima nota'); ?>...</a><br><br>
<?php
    }

    if ($record['stato'] == 'Pagato') {
        ?>
					<a class="btn btn-primary btn-block" href="javascript:;" onclick="if( confirm('Se riapri questa fattura verrà azzerato lo scadenzario e la prima nota. Continuare?') ){ $.post( '<?php echo $rootdir; ?>/editor.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>', { id_module: '<?php echo $id_module; ?>', id_record: '<?php echo $id_record; ?>', op: 'reopen' }, function(){ location.href='<?php echo $rootdir; ?>/editor.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>'; } ); }" title="Aggiungi prima nota"><i class="fa fa-folder-open"></i> <?php echo tr('Riapri fattura'); ?>...</a>
<?php
    }
}
?>

				</div>
			</div>

<?php

if ($dir == 'uscita') {
    ?>
				<div class="row">
					<div class="col-md-3">
						{[ "type": "number", "label": "<?php echo tr('Marca da bollo'); ?>", "name": "bollo", "value": "$bollo$", "help": "<?php echo tr('Applicato solo se il totale della fattura è maggiore di _TOT_ €', [
                            '_TOT_' => Translator::numberToLocale(setting("Soglia minima per l'applicazione della marca da bollo")),
                        ]),'.'; ?>" ]}
					</div>
				</div>
<?php
}
?>
		

            <div class="row">
			
                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo tr('Sconto incondizionato'); ?>", "name": "sconto_generico", "value": "$sconto_globale$", "help": "<?php echo tr('Sconto complessivo della fattura.'); ?>", "icon-after": "choice|untprc|$tipo_sconto_globale$"<?php echo ($record['stato'] == 'Emessa') ? ', "disabled" : 1' : ''; ?> ]}
                </div>
				
				<div class="col-md-3">
					{[ "type": "checkbox", "label": "<?php echo tr('Split payment'); ?>", "name": "split_payment", "value": "$split_payment$", "help": "<?php echo tr('Abilita lo split payment per questo documento.'); ?>", "placeholder": "<?php echo tr('Split payment'); ?>" ]}
				</div>
				
				
            </div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "help": "<?php echo tr('Note visibili anche in stampa.'); ?>", "value": "$note$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note aggiuntive'); ?>", "name": "note_aggiuntive", "help": "<?php echo tr('Note interne.'); ?>", "value": "$note_aggiuntive$" ]}
				</div>
			</div>
		</div>
	</div>

<?php

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
if ($record['stato'] != 'Pagato' && $record['stato'] != 'Emessa') {
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
            $prev_query = 'SELECT COUNT(*) AS tot FROM co_preventivi WHERE idanagrafica='.prepare($record['idanagrafica'])." AND id NOT IN (SELECT idpreventivo FROM co_righe_documenti WHERE idpreventivo IS NOT NULL) AND id NOT IN (SELECT idpreventivo FROM or_righe_ordini WHERE NOT idpreventivo IS NOT NULL)  AND idstato IN(SELECT id FROM co_statipreventivi WHERE descrizione='Accettato' OR descrizione='In lavorazione' OR descrizione='In attesa di conferma') AND default_revision=1";
            $preventivi = $dbo->fetchArray($prev_query)[0]['tot'];
            echo '
                    <div class="tip" data-toggle="tooltip" title="'.tr('Preventivi accettati, in attesa di conferma o in lavorazione.').'" style="display:inline;">
                        <a class="btn btn-sm btn-primary '.(!empty($preventivi) ? '' : ' disabled').'" data-href="'.$rootdir.'/modules/fatture/add_preventivo.php?id_module='.$id_module.'&id_record='.$id_record.'" data-title="Aggiungi preventivo">
                            <i class="fa fa-plus"></i> Preventivo
                        </a>
                    </div>';

            // Lettura contratti accettati, in attesa di conferma o in lavorazione
            $contr_query = 'SELECT COUNT(*) AS tot FROM co_contratti WHERE idanagrafica='.prepare($record['idanagrafica']).' AND id NOT IN (SELECT idcontratto FROM co_righe_documenti WHERE idcontratto IS NOT NULL) AND idstato IN( SELECT id FROM co_staticontratti WHERE fatturabile = 1) AND NOT EXISTS (SELECT id FROM co_righe_documenti WHERE co_righe_documenti.idcontratto = co_contratti.id)';
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
                        <a class="btn btn-sm btn-primary'.(!empty($articoli) ? '' : ' disabled').'" data-href="'.$rootdir.'/modules/fatture/row-add.php?id_module='.$id_module.'&id_record='.$id_record.'&is_articolo" data-toggle="tooltip" data-title="Aggiungi articolo">
                            <i class="fa fa-plus"></i> '.tr('Articolo').'
                        </a>';

    echo '
                        <a class="btn btn-sm btn-primary" data-href="'.$rootdir.'/modules/fatture/row-add.php?id_module='.$id_module.'&id_record='.$id_record.'&is_riga" data-toggle="tooltip" data-title="Aggiungi riga">
                            <i class="fa fa-plus"></i> '.tr('Riga').'
                        </a>';

    echo '
                        <a class="btn btn-sm btn-primary" data-href="'.$rootdir.'/modules/fatture/row-add.php?id_module='.$id_module.'&id_record='.$id_record.'&is_descrizione" data-toggle="tooltip" data-title="Aggiungi descrizione">
                            <i class="fa fa-plus"></i> '.tr('Descrizione').'
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

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<script type="text/javascript">
	$('#idanagrafica').change( function(){
        session_set('superselect,idanagrafica', $(this).val(), 0);

		$("#idsede").selectReset();
	});
</script>

<?php

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

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<?php
    echo '
<script>

$(".btn-sm[data-toggle=\"tooltip\"]").each(function() {

   $(this).on("click", function() {
        /*if(!content_was_modified) {
            return;
        }*/

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
