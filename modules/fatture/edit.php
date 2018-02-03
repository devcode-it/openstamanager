<?php

include_once __DIR__.'/../../core.php';

$rs = $dbo->fetchArray('SELECT co_tipidocumento.descrizione, dir FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento WHERE co_documenti.id='.prepare($id_record));
$dir = $rs[0]['dir'];
$tipodoc = $rs[0]['descrizione'];

$_SESSION['superselect']['idanagrafica'] = $records[0]['idanagrafica'];

?>
<form action="" class="text-right" method="post" id="form-copy">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="copy">
</form>

<form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record ?>">

	<!-- INTESTAZIONE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Intestazione') ?></h3>
		</div>

		<div class="panel-body">
            <div class="pull-right">
                <button type="button" class="btn btn-primary" onclick="if( confirm('Duplicare questa fattura?') ){ $('#form-copy').submit(); }"><i class="fa fa-copy"></i> <?php echo tr('Duplica fattura'); ?></button>

				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>

			<div class="row">
                <?php
                if ($dir == 'uscita') {
                    echo '
                				<div class="col-md-3">
                					{[ "type": "span", "label": "'.tr('Numero fattura').'", "name": "numero","class": "text-center", "value": "$numero$" ]}
                                </div>';
                    $label = tr('Numero secondario');
                } else {
                        $label = tr('Numero fattura');
                }
                ?>
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo $label; ?>", "name": "numero_esterno", "class": "text-center", "value": "$numero_esterno$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo tr('Data emissione'); ?>", "maxlength": 10, "name": "data", "required": 1, "value": "$data$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatodocumento", "required": 1, "values": "query=SELECT * FROM co_statidocumento", "value": "$idstatodocumento$", "extra": " onchange = \"if ($('#idstatodocumento option:selected').text()=='Pagato'){if( confirm('Sicuri di voler impostare manualmente la fattura come pagata senza aggiungerla in prima nota?') ){ return true; }else{ $('#idstatodocumento').selectSet(<?php echo $records[0]['idstatodocumento']; ?>); }}\" " ]}
				</div>

			</div>

			<div class="row">
				<div class="col-md-3">
					<?php

                    echo Modules::link('Anagrafiche', $records[0]['idanagrafica'], null, null, 'class="pull-right"');

                    if ($dir == 'entrata') {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "ajax-source": "clienti", "value": "$idanagrafica$" ]}
					<?php
                    } else {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Fornitore'); ?>", "name": "idanagrafica", "required": 1,  "ajax-source": "fornitori", "value": "$idanagrafica$" ]}
					<?php
                    }
                    ?>
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Riferimento sede'); ?>", "name": "idsede", "ajax-source": "sedi", "value": "$idsede$" ]}
				</div>

				<?php if ($dir == 'entrata') {
                        ?>
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Agente di riferimento'); ?>", "name": "idagente", "ajax-source": "agenti", "value": "$idagente_fattura$" ]}
				</div>
				<?php
                    } ?>

                <?php
                if ($records[0]['stato'] != 'Bozza' && $records[0]['stato'] != 'Annullata') {
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
					{[ "type": "select", "label": "<?php echo tr('Tipo fattura'); ?>", "name": "idtipodocumento", "required": 1, "values": "query=SELECT id, descrizione FROM co_tipidocumento WHERE dir='<?php echo $dir ?>'", "value": "$idtipodocumento$" ]}
				</div>

				<div class="col-md-3">
					<?php
                    if ($dir == 'entrata') {
                        $ajaxsource = 'conti-vendite';
                    } else {
                        $ajaxsource = 'conti-acquisti';
                    }
                    ?>
					{[ "type": "select", "label": "<?php echo tr('Conto'); ?>", "name": "idconto", "required": 1, "value": "$idconto$", "ajax-source": "<?php echo $ajaxsource ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Pagamento'); ?>", "name": "idpagamento", "required": 1, "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento$" ]}
				</div>

			</div>


<?php
if ($tipodoc == 'Fattura accompagnatoria di vendita') {
                        ?>
				<div class="row">
					<div class="col-md-3">
						{[ "type": "select", "label": "<?php echo tr('Aspetto beni'); ?>", "name": "idaspettobeni", "placeholder": "-", "values": "query=SELECT id, descrizione FROM dt_aspettobeni ORDER BY descrizione ASC", "value": "$idaspettobeni$" ]}
					</div>

					<div class="col-md-3">
						{[ "type": "select", "label": "<?php echo tr('Causale trasporto'); ?>", "name": "idcausalet", "placeholder": "-", "values": "query=SELECT id, descrizione FROM dt_causalet ORDER BY descrizione ASC", "value": "$idcausalet$" ]}
					</div>

					<div class="col-md-3">
						{[ "type": "select", "label": "<?php echo tr('Porto'); ?>", "name": "idporto", "placeholder": "-", "values": "query=SELECT id, descrizione FROM dt_porto ORDER BY descrizione ASC", "value": "$idporto$" ]}
					</div>

					<div class="col-md-3">
						{[ "type": "text", "label": "<?php echo tr('Num. colli'); ?>", "name": "n_colli", "value": "$n_colli$" ]}
					</div>
				</div>

                <div class="row">
					<div class="col-md-3">
						{[ "type": "select", "label": "<?php echo tr('Tipo di spedizione'); ?>", "name": "idspedizione", "values": "query=SELECT id, descrizione FROM dt_spedizione ORDER BY descrizione ASC", "value": "$idspedizione$" ]}
					</div>

					<div class="col-md-3">
						{[ "type": "select", "label": "<?php echo tr('Vettore'); ?>", "name": "idvettore", "values": "query=SELECT DISTINCT an_anagrafiche.idanagrafica AS id, an_anagrafiche.ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE an_tipianagrafiche_anagrafiche.idtipoanagrafica=(SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Vettore') ORDER BY descrizione ASC", "value": "$idvettore$" ]}
					</div>
				</div>

<?php
                    }

if ($dir == 'uscita') {
    ?>
				<div class="row">
					<div class="col-md-3">
						{[ "type": "number", "label": "<?php echo tr('Marca da bollo'); ?>", "name": "bollo", "value": "$bollo$", "help": "<?php echo tr('Applicato solo se il totale della fattura è maggiore di _TOT_ €', [
                            '_TOT_' => Translator::numberToLocale(get_var("Soglia minima per l'applicazione della marca da bollo")),
                        ]),'.'; ?>" ]}
					</div>
				</div>
<?php
}
?>


			<div class="pull-right">
<?php
// Aggiunta prima nota solo se non c'è già, se non si è in bozza o se il pagamento non è completo
$query2 = 'SELECT id FROM co_movimenti WHERE iddocumento='.$id_record.' AND primanota=1';
$n2 = $dbo->fetchNum($query2);

$query3 = 'SELECT SUM(da_pagare-pagato) AS differenza, SUM(da_pagare) FROM co_scadenziario GROUP BY iddocumento HAVING iddocumento='.$id_record.'';
$rs3 = $dbo->fetchArray($query3);
$differenza = $rs3[0]['differenza'];
$da_pagare = $rs3[0]['da_pagare'];

if (($n2 <= 0 && $records[0]['stato'] == 'Emessa') || $differenza != 0) {
    ?>
					<a class="btn btn-sm btn-primary" href="javascript:;" onclick="launch_modal( 'Aggiungi prima nota', '<?php echo $rootdir ?>/add.php?id_module=<?php echo Modules::get('Prima nota')['id'] ?>&iddocumento=<?php echo $id_record ?>&dir=<?php echo $dir ?>', 1 );"><i class="fa fa-euro"></i> Aggiungi prima nota...</a><br><br>
<?php
}

if ($records[0]['stato'] == 'Pagato') {
    ?>
					<a class="btn btn-sm btn-primary" href="javascript:;" onclick="if( confirm('Se riapri questa fattura verrà azzerato lo scadenzario e la prima nota. Continuare?') ){ $.post( '<?php echo $rootdir ?>/editor.php?id_module=<?php echo Modules::get($name)['id'] ?>&id_record=<?php echo $id_record ?>', { id_module: '<?php echo Modules::get($name)['id'] ?>', id_record: '<?php echo $id_record ?>', op: 'reopen' }, function(){ location.href='<?php echo $rootdir ?>/editor.php?id_module=<?php echo Modules::get($name)['id'] ?>&id_record=<?php echo $id_record ?>'; } ); }" title="Aggiungi prima nota"><i class="fa fa-folder-open"></i> Riapri fattura...</a>
<?php
}
?>
			</div>
			<div class="clearfix"></div>

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo tr('Sconto incondizionato') ?>", "name": "sconto_generico", "value": "$sconto_globale$", "help": "<?php echo tr('Sconto complessivo della fattura.'); ?>", "icon-after": "choice|untprc|$tipo_sconto_globale$"<?php
if ($records[0]['stato'] == 'Emessa') {
    echo ', "disabled" : 1';
}
?> ]}
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


			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
			</div>
		</div>
	</div>
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
if ($records[0]['stato'] != 'Pagato' && $records[0]['stato'] != 'Emessa') {
    if ($dir == 'entrata') {
        // Lettura interventi non rifiutati, non fatturati e non collegati a preventivi o contratti
        $qi = 'SELECT id FROM in_interventi WHERE idanagrafica='.prepare($records[0]['idanagrafica'])." AND NOT idstatointervento='DENY' AND id NOT IN (SELECT idintervento FROM co_righe_documenti WHERE idintervento IS NOT NULL) AND id NOT IN (SELECT idintervento FROM co_preventivi_interventi WHERE idintervento IS NOT NULL) AND id NOT IN (SELECT idintervento FROM co_righe_contratti WHERE idintervento IS NOT NULL)";
        $interventi = $dbo->fetchNum($qi);

        // Se non trovo niente provo a vedere se ce ne sono per clienti terzi
        if (empty($interventi)) {
            // Lettura interventi non rifiutati, non fatturati e non collegati a preventivi o contratti (clienti terzi)
            $qi = 'SELECT id FROM in_interventi WHERE idclientefinale='.prepare($records[0]['idanagrafica'])." AND NOT idstatointervento='DENY' AND id NOT IN (SELECT idintervento FROM co_righe_documenti WHERE idintervento IS NOT NULL) AND id NOT IN (SELECT idintervento FROM co_preventivi_interventi WHERE idintervento IS NOT NULL) AND id NOT IN (SELECT idintervento FROM co_righe_contratti WHERE idintervento IS NOT NULL)";
            $interventi = $dbo->fetchNum($qi);
        }

        echo '
                        <a class="btn btn-sm btn-primary'.(!empty($interventi) ? '' : ' disabled').'" data-href="'.$rootdir.'/modules/fatture/add_intervento.php?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="modal" data-title="Aggiungi intervento" data-target="#bs-popup">
                            <i class="fa fa-plus"></i> Intervento
                        </a>';

        // Lettura preventivi accettati, in attesa di conferma o in lavorazione
        $qp = 'SELECT id FROM co_preventivi WHERE idanagrafica='.prepare($records[0]['idanagrafica'])." AND id NOT IN (SELECT idpreventivo FROM co_righe_documenti WHERE NOT idpreventivo=NULL) AND idstato IN( SELECT id FROM co_statipreventivi WHERE descrizione='Accettato' OR descrizione='In lavorazione' OR descrizione='In attesa di conferma')";
        $preventivi = $dbo->fetchNum($qp);
        echo '
                        <a class="btn btn-sm btn-primary'.(!empty($preventivi) ? '' : ' disabled').'" data-href="'.$rootdir.'/modules/fatture/add_preventivo.php?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="modal" data-title="Aggiungi preventivo" data-target="#bs-popup">
                            <i class="fa fa-plus"></i> Preventivo
                        </a>';

        // Lettura contratti accettati, in attesa di conferma o in lavorazione
        $qc = 'SELECT id FROM co_contratti WHERE idanagrafica='.prepare($records[0]['idanagrafica']).' AND id NOT IN (SELECT idcontratto FROM co_righe_documenti WHERE NOT idcontratto=NULL) AND idstato IN( SELECT id FROM co_staticontratti WHERE fatturabile = 1) AND NOT EXISTS (SELECT id FROM co_righe_documenti WHERE co_righe_documenti.idcontratto = co_contratti.id)';
        $contratti = $dbo->fetchNum($qc);
        echo '

                        <a class="btn btn-sm btn-primary'.(!empty($contratti) ? '' : ' disabled').'" data-href="'.$rootdir.'/modules/fatture/add_contratto.php?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="modal" data-title="Aggiungi contratto" data-target="#bs-popup">
                            <i class="fa fa-plus"></i> Contratto
                        </a>';

        // Lettura ddt
        $qd = 'SELECT id FROM dt_ddt WHERE idanagrafica='.prepare($records[0]['idanagrafica']).' AND idstatoddt IN (SELECT id FROM dt_statiddt WHERE descrizione=\'Bozza\') AND idtipoddt=(SELECT id FROM dt_tipiddt WHERE dir='.prepare($dir).') AND dt_ddt.id IN (SELECT idddt FROM dt_righe_ddt WHERE dt_righe_ddt.idddt = dt_ddt.id AND (qta - qta_evasa) > 0)';
        $ddt = $dbo->fetchNum($qd);
        echo '
                        <a class="btn btn-sm btn-primary'.(!empty($ddt) ? '' : ' disabled').'" data-href="'.$rootdir.'/modules/fatture/add_ddt.php?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="modal" data-title="Aggiungi ddt" data-target="#bs-popup">
                            <i class="fa fa-plus"></i> Ddt
                        </a>';
    }

    // Lettura articoli
    $articoli = $dbo->fetchNum('SELECT * FROM mg_articoli WHERE qta > 0 OR servizio = 1');
    echo '
                        <a class="btn btn-sm btn-primary'.(!empty($articoli) ? '' : ' disabled').'" data-href="'.$rootdir.'/modules/fatture/add_articolo.php?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="modal" data-title="Aggiungi articolo" data-target="#bs-popup">
                            <i class="fa fa-plus"></i> Articolo
                        </a>';

    echo '
                        <a class="btn btn-sm btn-primary" data-href="'.$rootdir.'/modules/fatture/add_riga.php?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="modal" data-title="Aggiungi riga" data-target="#bs-popup">
                            <i class="fa fa-plus"></i> Riga generica
                        </a>';
                        
    echo '
                        <a class="btn btn-sm btn-primary" data-href="'.$rootdir.'/modules/fatture/add_descrizione.php?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="modal" data-title="Aggiungi descrizione" data-target="#bs-popup">
                            <i class="fa fa-plus"></i> Descrizione
                        </a>';
}
?>
				</div>

				<div class="pull-right">
					<!-- Stampe -->
<?php

if ($dir == 'entrata') {
    $rs2 = $dbo->fetchArray('SELECT piva, codice_fiscale, citta, indirizzo, cap, provincia FROM an_anagrafiche WHERE idanagrafica='.prepare($records[0]['idanagrafica']));
    $campi_mancanti = [];

    if ($rs2[0]['piva'] == '') {
        if ($rs2[0]['codice_fiscale'] == '') {
            array_push($campi_mancanti, 'codice fiscale');
        }
    }
    if ($rs2[0]['citta'] == '') {
        array_push($campi_mancanti, 'citta');
    }
    if ($rs2[0]['indirizzo'] == '') {
        array_push($campi_mancanti, 'indirizzo');
    }
    if ($rs2[0]['cap'] == '') {
        array_push($campi_mancanti, 'C.A.P.');
    }

    if ($dir == 'entrata') {
        if (sizeof($campi_mancanti) > 0) {
            echo "<div class='alert alert-warning'><i class='fa fa-warning'></i> Prima di procedere alla stampa completa i seguenti campi dell'anagrafica:<br/><b>".implode(', ', $campi_mancanti).'</b><br/>
            '.Modules::link('Anagrafiche', $records[0]['idanagrafica'], tr('Vai alla scheda anagrafica'), null).'</div>';
        } else {
            if ($records[0]['descrizione_tipodoc'] == 'Fattura accompagnatoria di vendita') {
                ?>
                    <a class="btn btn-info btn-sm pull-right" href="<?php echo $rootdir ?>/pdfgen.php?ptype=fatture_accompagnatorie&iddocumento=<?php echo $id_record ?>" target="_blank"><i class="fa fa-print"></i> Stampa fattura</a>
            <?php
            } else {
                ?>
                    <a class="btn btn-info btn-sm pull-right" href="<?php echo $rootdir ?>/pdfgen.php?ptype=fatture&iddocumento=<?php echo $id_record ?>" target="_blank"><i class="fa fa-print"></i> Stampa fattura</a>
            <?php
            }
        }
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

{( "name": "filelist_and_upload", "id_module": "<?php echo $id_module ?>", "id_record": "<?php echo $id_record ?>" )}

<script type="text/javascript">
	$('#idanagrafica').change( function(){
        session_set('superselect,idanagrafica', $(this).val(), 0);

		$("#idsede").selectReset();
	});
</script>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
