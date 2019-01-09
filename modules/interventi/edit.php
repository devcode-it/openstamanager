<?php

include_once __DIR__.'/../../core.php';

unset($_SESSION['superselect']['idanagrafica']);
$_SESSION['superselect']['idanagrafica'] = $record['idanagrafica'];

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- DATI CLIENTE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati cliente'); ?></h3>
		</div>

		<div class="panel-body">
			<!-- RIGA 1 -->
			<div class="row">
				<div class="col-md-3">
                    <?php
                        echo Modules::link('Anagrafiche', $record['idanagrafica'], null, null, 'class="pull-right"');
                    ?>
					{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Cliente' AND deleted_at IS NULL ORDER BY ragione_sociale", "value": "$idanagrafica$", "ajax-source": "clienti", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede","value": "$idsede$", "ajax-source": "sedi", "placeholder": "Sede legale", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					<?php
                        echo Modules::link('Anagrafiche', $record['idclientefinale'], null, null, 'class="pull-right"');
                    ?>
					{[ "type": "select", "label": "<?php echo tr('Per conto di'); ?>", "name": "idclientefinale", "value": "$idclientefinale$", "ajax-source": "clienti", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Referente'); ?>", "name": "idreferente", "value": "$idreferente$", "ajax-source": "referenti", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>
			</div>



			<!-- RIGA 2 -->
			<div class="row">
				<div class="col-md-6">
					<?php
                    if (($record['idpreventivo'] != '')) {
                        echo '
                        '.Modules::link('Preventivi', $record['idpreventivo'], null, null, 'class="pull-right"');
                    }
                    ?>

					{[ "type": "select", "label": "<?php echo tr('Preventivo'); ?>", "name": "idpreventivo", "value": "$idpreventivo$", "ajax-source": "preventivi", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-6">
					<?php
                        /*$rs = $dbo->fetchArray('SELECT id, idcontratto FROM co_promemoria WHERE idintervento='.prepare($id_record));
                        if (count($rs) == 1) {
                            $idcontratto = $rs[0]['idcontratto'];
                            $idcontratto_riga = $rs[0]['id'];
                        } else {
                            $idcontratto = '';
                            $idcontratto_riga = '';
                        }*/

                        if (($record['idcontratto'] != '')) {
                            echo '
                            '.Modules::link('Contratti', $record['idcontratto'], null, null, 'class="pull-right"');
                        }
                    ?>

					{[ "type": "select", "label": "<?php echo tr('Contratto'); ?>", "name": "idcontratto", "value": "<?php echo $record['idcontratto']; ?>", "ajax-source": "contratti", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
					<input type='hidden' name='idcontratto_riga' value='<?php echo $idcontratto_riga; ?>'>
				</div>
			</div>
		</div>
	</div>

	<!-- DATI INTERVENTO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati intervento'); ?></h3>
		</div>

		<div class="panel-body">
			<!-- RIGA 3 -->
			<div class="row">
				<div class="col-md-3">
					{[ "type": "span", "label": "<?php echo tr('Numero'); ?>", "name": "codice", "value": "$codice$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "timestamp", "label": "<?php echo tr('Data e ora richiesta'); ?>", "name": "data_richiesta", "required": 1, "value": "$data_richiesta$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Zona'); ?>", "name": "idzona", "values": "query=SELECT id, CONCAT_WS( ' - ', nome, descrizione) AS descrizione FROM an_zone ORDER BY nome", "value": "$idzona$" , "placeholder": "<?php echo tr('Nessuna zona'); ?>", "extra": "readonly", "help":"<?php echo 'La zona viene definita automaticamente in base al cliente selezionato'; ?>." ]}
				</div>



			</div>


			<!-- RIGA 4 -->
			<div class="row">
				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Tipo attività'); ?>", "name": "idtipointervento", "required": 1, "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento", "value": "$idtipointervento$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatointervento", "required": 1, "values": "query=SELECT idstatointervento AS id, descrizione, colore AS _bgcolor_ FROM in_statiintervento WHERE deleted_at IS NULL", "value": "$idstatointervento$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Automezzo'); ?>", "name": "idautomezzo", "values": "query=SELECT id, CONCAT_WS( ')', CONCAT_WS( ' (', CONCAT_WS( ', ', nome, descrizione), targa ), '' ) AS descrizione FROM dt_automezzi", "help": "<?php echo tr('Se selezionato i materiali verranno presi prima dall&rsquo;automezzo e poi dal magazzino centrale.'); ?>", "value": "$idautomezzo$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>
			</div>


			<!-- RIGA 5 -->
			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Richiesta'); ?>", "name": "richiesta", "required": 1, "class": "autosize", "value": "$richiesta$", "extra": "rows='5'", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-12">
					{[ "type": "ckeditor", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "class": "autosize", "value": "$descrizione$", "extra": "rows='10'", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note interne'); ?>", "name": "informazioniaggiuntive", "class": "autosize", "value": "$informazioniaggiuntive$", "extra": "rows='5'" ]}
				</div>
			</div>
		</div>
	</div>

<?php
    // Visualizzo solo se l'anagrafica cliente è un ente pubblico
    if (!empty($record['idcontratto'])) {
        $contratto = $dbo->fetchOne('SELECT codice_cig,codice_cup,id_documento_fe FROM co_contratti WHERE id = '.prepare($record['idcontratto']));
        $record['id_documento_fe'] = $contratto['id_documento_fe'];
        $record['codice_cup'] = $contratto['codice_cup'];
        $record['codice_cig'] = $contratto['codice_cig'];
    }

    ?>
    <!-- Fatturazione Elettronica PA-->
    <div class="panel panel-primary <?php echo (($record['tipo_anagrafica']) == 'Ente pubblico') ? 'show' : 'hide'; ?>" >
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo tr('Dati appalto'); ?>
			<?php if (!empty($record['idcontratto'])) {
        ?>
			<span class="tip" title="<?php echo tr('E\' possibile specificare i dati dell\'appalto solo se il cliente è di tipo \'Ente pubblico\' e l\'attività non risulta già collegata ad un contratto.'); ?>" > <i class="fa fa-question-circle-o"></i></span>
			</h3>
			<?php
    } ?>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-4">
					{[ "type": "<?php echo !empty($record['idcontratto']) ? 'span' : 'text'; ?>", "label": "<?php echo tr('Identificatore Documento'); ?>", "name": "id_documento_fe", "required": 0, "value": "<?php echo $record['id_documento_fe']; ?>", "maxlength": 20, "readonly": "<?php echo $record['flag_completato']; ?>", "extra": "" ]}
				</div>

                <div class="col-md-4">
					{[ "type": "<?php echo !empty($record['idcontratto']) ? 'span' : 'text'; ?>", "label": "<?php echo tr('Codice CIG'); ?>", "name": "codice_cig", "required": 0, "value": "<?php echo $record['codice_cig']; ?>", "maxlength": 15, "readonly": "<?php echo $record['flag_completato']; ?>", "extra": "" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "<?php echo !empty($record['idcontratto']) ? 'span' : 'text'; ?>", "label": "<?php echo tr('Codice CUP'); ?>", "name": "codice_cup", "required": 0, "value": "<?php echo $record['codice_cup']; ?>", "maxlength": 15, "readonly": "<?php echo $record['flag_completato']; ?>", "extra": "" ]}
				</div>
            </div>
        </div>
    </div>


	<!-- ORE LAVORO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Ore di lavoro'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<a class='btn btn-default btn-details' onclick="$('.extra').removeClass('hide'); $(this).addClass('hide'); $('#dontshowall_dettagli').removeClass('hide');" id='showall_dettagli'><i class='fa fa-square-o'></i> <?php echo tr('Visualizza dettaglio costi'); ?></a>
				<a class='btn btn-info btn-details hide' onclick="$('.extra').addClass('hide'); $(this).addClass('hide'); $('#showall_dettagli').removeClass('hide');" id='dontshowall_dettagli'><i class='fa fa-check-square-o'></i> <?php echo tr('Visualizza dettaglio costi'); ?></a>
			</div>
			<div class="clearfix"></div>
			<br>

			<div class="row">
				<div class="col-md-12" id="tecnici">
					<?php
                        if (file_exists($docroot.'/modules/interventi/custom/ajax_tecnici.php')) {
                            ?>
						<script>$('#tecnici').load('<?php echo $rootdir; ?>/modules/interventi/custom/ajax_tecnici.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>');</script>
					<?php
                        } else {
                            ?>
						<script>$('#tecnici').load('<?php echo $rootdir; ?>/modules/interventi/ajax_tecnici.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>');</script>
					<?php
                        }
                    ?>
				</div>
			</div>
		</div>
	</div>


    <!-- ARTICOLI -->
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo tr('Materiale utilizzato'); ?></h3>
        </div>

        <div class="panel-body">
            <div id="articoli">
				<?php
                    if (file_exists($docroot.'/modules/interventi/custom/ajax_articoli.php')) {
                        include $docroot.'/modules/interventi/custom/ajax_articoli.php';
                    } else {
                        include $docroot.'/modules/interventi/ajax_articoli.php';
                    }
                ?>
            </div>

            <?php if (!$record['flag_completato']) {
                    ?>
                <button type="button" class="btn btn-primary" onclick="launch_modal( '<?php echo tr('Aggiungi articolo'); ?>', '<?php echo $rootdir; ?>/modules/interventi/add_articolo.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&idriga=0&idautomezzo='+$('#idautomezzo').find(':selected').val(), 1);"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi articolo'); ?>...</button>
            <?php
                } ?>
        </div>
    </div>

    <!-- SPESE AGGIUNTIVE -->
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo tr('Altre spese'); ?></h3>
        </div>

        <div class="panel-body">
            <div id="righe">
                <?php
                    if (file_exists($docroot.'/modules/interventi/custom/ajax_righe.php')) {
                        include $docroot.'/modules/interventi/custom/ajax_righe.php';
                    } else {
                        include $docroot.'/modules/interventi/ajax_righe.php';
                    }
                ?>
            </div>

            <?php if (!$record['flag_completato']) {
                    ?>
                <button type="button" class="btn btn-primary" onclick="launch_modal( '<?php echo tr('Aggiungi altre spese'); ?>', '<?php echo $rootdir; ?>/modules/interventi/add_righe.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>', 1 );"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi altre spese'); ?>...</button>
            <?php
                } ?>
        </div>
    </div>

    <!-- COSTI TOTALI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Costi totali'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-12" id="costi">
					<?php
                        if (file_exists($docroot.'/modules/interventi/custom/ajax_costi.php')) {
                            ?>
						<script>$('#costi').load('<?php echo $rootdir; ?>/modules/interventi/custom/ajax_costi.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>');</script>
					<?php
                        } else {
                            ?>
						<script>$('#costi').load('<?php echo $rootdir; ?>/modules/interventi/ajax_costi.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>');</script>
					<?php
                        }
                    ?>
				</div>
			</div>
		</div>
	</div>
</form>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$", <?php echo ($record['flag_completato']) ? '"readonly": 1' : '"readonly": 0'; ?> )}

<!-- EVENTUALE FIRMA GIA' EFFETTUATA -->
<div class="text-center row">
	<div class="col-md-12" >
	    <?php
        if ($record['firma_file'] == '') {
            echo '
	    <div class="alert alert-warning"><i class="fa fa-warning"></i> '.tr('Questo intervento non è ancora stato firmato dal cliente').'.</div>';
        } else {
            echo '
	    <img src="'.$rootdir.'/files/interventi/'.$record['firma_file'].'" class="img-thumbnail"><div>&nbsp;</div>
	   	<div class="col-md-6 col-md-offset-3 alert alert-success"><i class="fa fa-check"></i> '.tr('Firmato il _DATE_ alle _TIME_ da _PERSON_', [
            '_DATE_' => Translator::dateToLocale($record['firma_data']),
            '_TIME_' => Translator::timeToLocale($record['firma_data']),
            '_PERSON_' => '<b>'.$record['firma_nome'].'</b>',
        ]).'</div>';
        }
        ?>
	</div>
</div>

<script>
	$('#idanagrafica').change( function(){
		session_set('superselect,idanagrafica', $(this).val(), 0);

		$("#idsede").selectReset();
		$("#idpreventivo").selectReset();
		$("#idcontratto").selectReset();

		if (($(this).val())) {
			if (($(this).selectData().idzona)){
				$('#idzona').val($(this).selectData().idzona).change();

			}else{
				$('#idzona').val('').change();
			}
			//session_set('superselect,idzona', $(this).selectData().idzona, 0);
		}

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

	$('#idsede').change( function(){
		if (($(this).val())) {
			if (($(this).selectData().idzona)){
				$('#idzona').val($(this).selectData().idzona).change();
			}else{
				$('#idzona').val('').change();
			}
			//session_set('superselect,idzona', $(this).selectData().idzona, 0);
		}
	});

</script>

{( "name": "log_email", "id_module": "$id_module$", "id_record": "$id_record$" )}

<?php
// Collegamenti diretti
// Fatture collegate a questo intervento
$elementi = $dbo->fetchArray('SELECT `co_documenti`.*, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idintervento` = '.prepare($id_record).') ORDER BY `data`');

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

    foreach ($elementi as $fattura) {
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
    </div>
</div>';
}

if (!empty($elementi)) {
    echo '
<div class="alert alert-error">
    '.tr('Eliminando questo documento si potrebbero verificare problemi nelle altre sezioni del gestionale').'.
</div>';
}

?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
