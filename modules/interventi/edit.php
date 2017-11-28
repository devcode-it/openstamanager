<?php

include_once __DIR__.'/../../core.php';

unset($_SESSION['superselect']['idanagrafica']);
$_SESSION['superselect']['idanagrafica'] = $records[0]['idanagrafica'];

if (empty($records[0]['firma_file'])) {
    $frase = tr('Anteprima e firma');
    $info_firma = '';
} else {
    $frase = tr('Nuova anteprima e firma');
    $info_firma = '<span class="label label-success"><i class="fa fa-edit"></i> '.tr('Firmato il _DATE_ alle _TIME_ da _PERSON_', [
        '_DATE_' => Translator::dateToLocale($records[0]['firma_data']),
        '_TIME_' => Translator::timeToLocale($records[0]['firma_data']),
        '_PERSON_' => '<b>'.$records[0]['firma_nome'].'</b>',
    ]).'</span>';
}

?><form action="" method="post">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record ?>">

	<!-- DATI CLIENTE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati cliente') ?></h3>
		</div>

		<div class="panel-body">
            <!-- EVENTUALE FIRMA GIA' EFFETTUATA -->
            <?php echo $info_firma ?>
			<div class="pull-right">
				<button type="button" class="btn btn-primary " onclick="launch_modal( '<?php echo tr('Anteprima e firma') ?>', '<?php echo $rootdir ?>/modules/interventi/add_firma.php?id_module=<?php echo $id_module ?>&id_record=<?php echo $id_record ?>&anteprima=1', 1 );"><i class="fa fa-desktop"></i> <?php echo $frase ?>...</button>

				<a class="btn btn-info" target="_blank" href="<?php echo $rootdir ?>/pdfgen.php?ptype=interventi&idintervento=<?php echo $id_record ?>"><i class="fa fa-print"></i> <?php echo tr('Stampa intervento') ?></a>
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
				<div class="clearfix" >&nbsp;</div>
			</div>
			<div class="clearfix"></div>

			<!-- RIGA 1 -->
			<div class="row">
				<div class="col-md-3">
					<?php
                        echo Modules::link('Anagrafiche', $records[0]['idanagrafica'], null, null, 'class="pull-right"');
                    ?>
					{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Cliente' AND deleted=0 ORDER BY ragione_sociale", "value": "$idanagrafica$", "ajax-source": "clienti" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede", "values": "query=SELECT 0 AS id, 'Sede legale' AS descrizione UNION SELECT id, CONCAT_WS( ' - ', nomesede, citta ) AS descrizione FROM an_sedi WHERE idanagrafica='$idanagrafica$'", "value": "$idsede$", "ajax-source": "sedi" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Per conto di'); ?>", "name": "idclientefinale", "value": "$idclientefinale$", "ajax-source": "clienti" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Referente'); ?>", "name": "idreferente", "value": "$idreferente$", "ajax-source": "referenti" ]}
				</div>
			</div>



			<!-- RIGA 2 -->
			<div class="row">
				<div class="col-md-3">
					<?php
                    if (($records[0]['idpreventivo'] != '')) {
                        echo '
                        '.Modules::link('Preventivi', $records[0]['idpreventivo'], null, null, 'class="pull-right"');
                    }
                    ?>

					{[ "type": "select", "label": "<?php echo tr('Preventivo'); ?>", "name": "idpreventivo", "value": "$idpreventivo$", "ajax-source": "preventivi" ]}
				</div>

				<div class="col-md-3">
					<?php
                        $rs = $dbo->fetchArray('SELECT id, idcontratto FROM co_righe_contratti WHERE idintervento='.prepare($id_record));
                        if (count($rs) == 1) {
                            $idcontratto = $rs[0]['idcontratto'];
                            $idcontratto_riga = $rs[0]['id'];
                        } else {
                            $idcontratto = '';
                            $idcontratto_riga = '';
                        }

                        if (($idcontratto != '')) {
                            echo '
                            '.Modules::link('Contratti', $idcontratto, null, null, 'class="pull-right"');
                        }
                    ?>

					{[ "type": "select", "label": "<?php echo tr('Contratto'); ?>", "name": "idcontratto", "value": "<?php echo $idcontratto; ?>", "ajax-source": "contratti" ]}
					<input type='hidden' name='idcontratto_riga' value='<?php echo $idcontratto_riga ?>'>
				</div>
			</div>
		</div>
	</div>



	<!-- DATI INTERVENTO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati intervento') ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>


			<!-- RIGA 3 -->
			<div class="row">
				<div class="col-md-3">
					{[ "type": "span", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "value": "$codice$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo tr('Data richiesta'); ?>", "name": "data_richiesta", "required": 1, "value": "$data_richiesta$" ]}
				</div>
			</div>


			<!-- RIGA 4 -->
			<div class="row">
				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Tipo attività'); ?>", "name": "idtipointervento", "required": 1, "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento", "value": "$idtipointervento$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatointervento", "required": 1, "values": "query=SELECT idstatointervento AS id, descrizione, colore AS _bgcolor_ FROM in_statiintervento", "value": "$idstatointervento$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Automezzo'); ?>", "name": "idautomezzo", "values": "query=SELECT id, CONCAT_WS( ')', CONCAT_WS( ' (', CONCAT_WS( ', ', nome, descrizione), targa ), '' ) AS descrizione FROM dt_automezzi", "help": "<?php echo tr('Se selezionato i materiali verranno presi prima dall&rsquo;automezzo e poi dal magazzino centrale.'); ?>", "value": "$idautomezzo$" ]}
				</div>
			</div>


			<!-- RIGA 5 -->
			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Richiesta'); ?>", "name": "richiesta", "required": 1, "class": "autosize", "value": "$richiesta$", "extra": "rows='5'" ]}
				</div>

				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "class": "autosize", "value": "$descrizione$", "extra": "rows='10'" ]}
				</div>

				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note interne'); ?>", "name": "informazioniaggiuntive", "class": "autosize", "value": "$informazioniaggiuntive$", "extra": "rows='5'" ]}
				</div>
			</div>
		</div>
	</div>

	<!-- ORE LAVORO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Ore di lavoro') ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<a class='btn btn-default' onclick="$('.extra').removeClass('hide'); $(this).addClass('hide'); $('#dontshowall_dettagli').removeClass('hide');" id='showall_dettagli'><i class='fa fa-square-o'></i> <?php echo tr('Mostra dettagli costi') ?></a>
				<a class='btn btn-info hide' onclick="$('.extra').addClass('hide'); $(this).addClass('hide'); $('#showall_dettagli').removeClass('hide');" id='dontshowall_dettagli'><i class='fa fa-check-square-o'></i> <?php echo tr('Mostra dettagli costi') ?></a>
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>
			<br>

			<div class="row">
				<div class="col-md-12" id="tecnici">
					<script>$('#tecnici').load('<?php echo $rootdir ?>/modules/interventi/ajax_tecnici.php?id_module=<?php echo $id_module ?>&id_record=<?php echo $id_record ?>');</script>
				</div>
			</div>
		</div>
	</div>


    <!-- ARTICOLI -->
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo tr('Materiale utilizzato') ?></h3>
        </div>

        <div class="panel-body">
            <div id="articoli">
                <?php include $docroot.'/modules/interventi/ajax_articoli.php'; ?>
            </div>

            <?php if ($records[0]['stato'] != 'Fatturato' && $records[0]['stato'] != 'Completato') {
                        ?>
                <button type="button" class="btn btn-primary" onclick="launch_modal( '<?php echo tr('Aggiungi articolo') ?>', '<?php echo $rootdir ?>/modules/interventi/add_articolo.php?id_module=<?php echo $id_module ?>&id_record=<?php echo $id_record ?>&idriga=0&idautomezzo='+$('#idautomezzo').find(':selected').val(), 1);"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi articolo') ?>...</button>
            <?php
                    } ?>
        </div>
    </div>

    <!-- SPESE AGGIUNTIVE -->
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo tr('Altre spese') ?></h3>
        </div>

        <div class="panel-body">
            <div id="righe">
                <?php include $docroot.'/modules/interventi/ajax_righe.php'; ?>
            </div>

            <?php if ($records[0]['stato'] != 'Fatturato' && $records[0]['stato'] != 'Completato') {
                        ?>
                <button type="button" class="btn btn-primary" onclick="launch_modal( '<?php echo tr('Aggiungi altre spese') ?>', '<?php echo $rootdir ?>/modules/interventi/add_righe.php?id_module=<?php echo $id_module ?>&id_record=<?php echo $id_record ?>', 1 );"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi altre spese') ?>...</button>
            <?php
                    } ?>
        </div>
    </div>

    <!-- COSTI TOTALI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Costi totali') ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>
			<br>

			<div class="row">
				<div class="col-md-12" id="costi">
					<script>$('#costi').load('<?php echo $rootdir ?>/modules/interventi/ajax_costi.php?id_module=<?php echo $id_module ?>&id_record=<?php echo $id_record ?>');</script>
				</div>
			</div>
		</div>
	</div>
</form>

{( "name": "filelist_and_upload", "id_module": "<?php echo $id_module ?>", "id_record": "<?php echo $id_record ?>" )}

<!-- EVENTUALE FIRMA GIA' EFFETTUATA -->
<div class="text-center">
    <?php
    if ($records[0]['firma_file'] == '') {
        echo '
    <p class="alert alert-warning"><i class="fa fa-warning"></i> '.tr('Questo intervento non è ancora stato firmato dal cliente').'.</p>';
    } else {
        echo '
    <img src="'.$rootdir.'/files/interventi/'.$records[0]['firma_file'].'" class="img-thumbnail"><br>
    <div class="alert alert-success"><i class="fa fa-check"></i> '.tr('Firmato il _DATE_ alle _TIME_ da _PERSON_', [
        '_DATE_' => Translator::dateToLocale($records[0]['firma_data']),
        '_TIME_' => Translator::timeToLocale($records[0]['firma_data']),
        '_PERSON_' => '<b>'.$records[0]['firma_nome'].'</b>',
    ]).'</div>';
    }
    ?>
</div>

<script>
	$('#idanagrafica').change( function(){
		session_set('superselect,idanagrafica', $(this).val(), 0);

		$("#idsede").selectReset();
		$("#idpreventivo").selectReset();
		$("#idcontratto").selectReset();
	});

	$('#idpreventivo').change( function(){
		if($('#idcontratto').val() && $(this).val()){
			$('#idcontratto').val('').trigger('change');
		}
	});

	$('#idcontratto').change( function(){
		if($('#idpreventivo').val() && $(this).val()){
			$('#idpreventivo').val('').trigger('change');
			$('input[name=idcontratto_riga]').val('');
		}
	});

	$('#matricola').change( function(){
		session_set('superselect,marticola', $(this).val(), 0);
	});
</script>


<script src="<?php echo $rootdir ?>/modules/interventi/js/interventi_helperjs.js"></script>

<?php

//fatture collegate a questo intervento

$fatture = $dbo->fetchArray('SELECT `co_documenti`.*, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idintervento` = '.prepare($id_record).') ORDER BY `data`');
if (!empty($fatture)) {
    echo '
    <div class="alert alert-warning">
        <p>'.tr('_NUM_ altr_I_ document_I_ collegat_I_', [
            '_NUM_' => count($fatture),
			'_I_' => (count($fatture)>1) ? tr('i') : tr('o')
        ]).':</p>
    <ul>';

    foreach ($fatture as $fattura) {
        $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
            '_DOC_' => $fattura['tipo_documento'],
            '_NUM_' => !empty($fattura['numero_esterno']) ? $fattura['numero_esterno'] : $fattura['numero'],
            '_DATE_' => Translator::dateToLocale($fattura['data']),
        ]);

        $modulo = ($fattura['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto';
        $id = $fattura['id'];

        echo '
        <li>'.Modules::link($modulo, $id, $descrizione).'</li>';
    }

    echo '
        </ul>
        <p>'.tr('Eliminando questo documento si potrebbero verificare problemi nelle altre sezioni del gestionale.').'</p>
    </div>';
}

?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina') ?>
</a>