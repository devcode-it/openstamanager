<?php

include_once __DIR__.'/../../core.php';

$block_edit = $record['flag_completato'];
$module_anagrafiche = Modules::get('Anagrafiche');

unset($_SESSION['superselect']['idanagrafica']);
unset($_SESSION['superselect']['idsede_partenza']);
unset($_SESSION['superselect']['idsede_destinazione']);
unset($_SESSION['superselect']['idintervento']);
$_SESSION['superselect']['idanagrafica'] = $record['idanagrafica'];
$_SESSION['superselect']['idsede_partenza'] = $record['idsede_partenza'];
$_SESSION['superselect']['idsede_partenza'];
$_SESSION['superselect']['idintervento'] = $id_record;
$_SESSION['superselect']['idsede_destinazione'] = $record['idsede_destinazione'];
$_SESSION['superselect']['permetti_movimento_a_zero'] = false;

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
<?php

                echo '
				<div class="col-md-3">
					{[ "type": "select", "label": "'.tr('Sede destinazione').'", "name": "idsede_destinazione","value": "$idsede_destinazione$", "ajax-source": "sedi", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "placeholder": "'.tr('Sede legale').'", "readonly": "'.$record['flag_completato'].'" ]}
				</div>';
                ?>

				<div class="col-md-3">
					<?php
                        echo Modules::link('Anagrafiche', $record['idclientefinale'], null, null, 'class="pull-right"');
                    ?>
					{[ "type": "select", "label": "<?php echo tr('Per conto di'); ?>", "name": "idclientefinale", "value": "$idclientefinale$", "ajax-source": "clienti", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

                <?php
                echo '
				<div class="col-md-3">
					{[ "type": "select", "label": "'.tr('Referente').'", "name": "idreferente", "value": "$idreferente$", "ajax-source": "referenti", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "readonly": "'.intval($record['flag_completato']).'" ]}
				</div>';
                ?>
			</div>

			<!-- RIGA 2 -->
			<div class="row">
				<div class="col-md-6">
					<?php
                    if (!empty($record['idpreventivo'])) {
                        echo '
                        '.Modules::link('Preventivi', $record['idpreventivo'], null, null, 'class="pull-right"');
                    }
                    ?>

					{[ "type": "select", "label": "<?php echo tr('Preventivo'); ?>", "name": "idpreventivo", "value": "$idpreventivo$", "ajax-source": "preventivi", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-6">
					<?php
                        $idcontratto_riga = $dbo->fetchOne('SELECT id FROM co_promemoria WHERE idintervento='.prepare($id_record))['id'];

                        if (!empty($record['idcontratto'])) {
                            echo '
                            '.Modules::link('Contratti', $record['idcontratto'], null, null, 'class="pull-right"');
                        }
                    ?>

					{[ "type": "select", "label": "<?php echo tr('Contratto'); ?>", "name": "idcontratto", "value": "<?php echo $record['id_contratto']; ?>", "ajax-source": "contratti", "readonly": "<?php echo $record['flag_completato']; ?>" ]}

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
					{[ "type": "timestamp", "label": "<?php echo tr('Data/ora richiesta'); ?>", "name": "data_richiesta", "required": 1, "value": "$data_richiesta$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "timestamp", "label": "<?php echo tr('Data/ora scadenza'); ?>", "name": "data_scadenza", "required": 0, "value": "$data_scadenza$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
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
					{[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatointervento", "required": 1, "values": "query=SELECT idstatointervento AS id, descrizione, colore AS _bgcolor_ FROM in_statiintervento WHERE deleted_at IS NULL", "value": "$idstatointervento$", "class": "unblockable" ]}
				</div>
<?php

$tecnici_assegnati = $database->fetchArray('SELECT id_tecnico FROM in_interventi_tecnici_assegnati WHERE id_intervento = '.prepare($id_record));
$tecnici_assegnati = array_column($tecnici_assegnati, 'id_tecnico');
echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Tecnici assegnati').'", "multiple": "1", "name": "tecnici_assegnati[]", "ajax-source": "tecnici", "value": "'.implode(',', $tecnici_assegnati).'", "icon-after": "add|'.$module_anagrafiche['id'].'|tipoanagrafica=Tecnico" ]}
                </div>';

?>
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
        $contratto = $dbo->fetchOne('SELECT num_item,codice_cig,codice_cup,id_documento_fe FROM co_contratti WHERE id = '.prepare($record['idcontratto']));
        $record['id_documento_fe'] = $contratto['id_documento_fe'];
        $record['codice_cup'] = $contratto['codice_cup'];
        $record['codice_cig'] = $contratto['codice_cig'];
        $record['num_item'] = $contratto['num_item'];
    }

    ?>
    <!-- Fatturazione Elettronica PA-->
    <div class="panel panel-primary <?php echo ($record['tipo_anagrafica'] == 'Ente pubblico' || $record['tipo_anagrafica'] == 'Azienda') ? 'show' : 'hide'; ?>" >
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo tr('Dati appalto'); ?>
			<?php if (!empty($record['idcontratto'])) {
        ?>
			<span class="tip" title="<?php echo tr('E\' possibile specificare i dati dell\'appalto solo se il cliente è di tipo \'Ente pubblico\' o \'Azienda\' e l\'attività non risulta già collegata ad un contratto.'); ?>" > <i class="fa fa-question-circle-o"></i></span>
			</h3>
			<?php
    } ?>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
					{[ "type": "<?php echo !empty($record['idcontratto']) ? 'span' : 'text'; ?>", "label": "<?php echo tr('Identificatore Documento'); ?>", "name": "id_documento_fe", "required": 0, "help": "<?php echo tr('<span>Obbligatorio per valorizzare CIG/CUP. &Egrave; possible inserire: </span><ul><li>N. determina</li><li>RDO</li><li>Ordine MEPA</li></ul>'); ?>", "value": "<?php echo $record['id_documento_fe']; ?>", "maxlength": 20, "readonly": "<?php echo $record['flag_completato']; ?>", "extra": "" ]}
				</div>

                <div class="col-md-6">
					{[ "type": "<?php echo !empty($record['idcontratto']) ? 'span' : 'text'; ?>", "label": "<?php echo tr('Numero Riga'); ?>", "name": "num_item", "required": 0, "value": "<?php echo $record['num_item']; ?>", "maxlength": 15, "readonly": "<?php echo $record['flag_completato']; ?>", "extra": "" ]}
				</div>
			</div>
			<div class="row">
                <div class="col-md-6">
					{[ "type": "<?php echo !empty($record['idcontratto']) ? 'span' : 'text'; ?>", "label": "<?php echo tr('Codice CIG'); ?>", "name": "codice_cig", "required": 0, "value": "<?php echo $record['codice_cig']; ?>", "maxlength": 15, "readonly": "<?php echo $record['flag_completato']; ?>", "extra": "" ]}
				</div>

				<div class="col-md-6">
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

    <!-- RIGHE -->
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo tr('Righe'); ?></h3>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-9">

<?php

if (!$block_edit) {
    echo '
            <button class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi articolo').'" onclick="gestioneArticolo(this)">
                <i class="fa fa-plus"></i> '.tr('Articolo').'
            </button>';

    echo '
            <button class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi articoli tramite barcode').'" onclick="gestioneBarcode(this)">
                <i class="fa fa-plus"></i> '.tr('Barcode').'
            </button>';

    echo '
            <button class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi riga').'" onclick="gestioneRiga(this)">
                <i class="fa fa-plus"></i> '.tr('Riga').'
            </button>';

    /*
    echo '
            <button class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi descrizione').'" onclick="gestioneDescrizione(this)">
                <i class="fa fa-plus"></i> '.tr('Descrizione').'
            </button>';*/

    echo '
            <button class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi sconto/maggiorazione').'" onclick="gestioneSconto(this)">
                <i class="fa fa-plus"></i> '.tr('Sconto/maggiorazione').'
            </button>';
}

// Conteggio numero articoli intervento per eventuale blocco della sede di partenza
$articoli = $intervento->articoli;

?>
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Partenza merce'); ?>", "name": "idsede_partenza", "ajax-source": "sedi_azienda", "value": "$idsede_partenza$", "readonly": "<?php echo ($record['flag_completato'] || !$articoli->isEmpty()) ? 1 : 0; ?>" ]}
                </div>
            </div>

            <div id="righe">
<?php
include $structure->filepath('row-list.php');
?>
            </div>
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

        echo '
	</div>
</div>

{( "name": "log_email", "id_module": "$id_module$", "id_record": "$id_record$" )}

<script>
function gestioneArticolo(button) {
    gestioneRiga(button, "is_articolo");
}

function gestioneBarcode(button) {
    gestioneRiga(button, "is_barcode");
}

function gestioneSconto(button) {
    gestioneRiga(button, "is_sconto");
}

function gestioneDescrizione(button) {
    gestioneRiga(button, "is_descrizione");
}

async function gestioneRiga(button, options) {
    // Salvataggio via AJAX
    let valid = await salvaForm(button, $("#edit-form"));

    // Apertura modal
    if (valid) {
        // Lettura titolo e chiusura tooltip
        let title = $(button).tooltipster("content");
        $(button).tooltipster("close");

        // Apertura modal
        options = options ? options : "is_riga";
        openModal(title, "'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&" + options);
    }
}

$("#idanagrafica").change(function () {
    updateSelectOption("idanagrafica", $(this).val());
    session_set("superselect,idanagrafica", $(this).val(), 0);

    $("#idsede_destinazione").selectReset();
    $("#idpreventivo").selectReset();
    $("#idcontratto").selectReset();

    if (($(this).val())) {
        if (($(this).selectData().idzona)) {
            $("#idzona").val($(this).selectData().idzona).change();

        } else {
            $("#idzona").val("").change();
        }
    }
});

$("#idpreventivo").change(function () {
    if ($("#idcontratto").val() && $(this).val()) {
        $("#idcontratto").val("").trigger("change");
    }
});

$("#idcontratto").change(function () {
    if ($("#idpreventivo").val() && $(this).val()) {
        $("#idpreventivo").val("").trigger("change");
        $("input[name=idcontratto_riga]").val("");
    }
});

$("#matricola").change(function () {
    session_set("superselect,marticola", $(this).val(), 0);
});

$("#idsede").change(function () {
    if (($(this).val())) {
        if (($(this).selectData().idzona)) {
            $("#idzona").val($(this).selectData().idzona).change();
        } else {
            $("#idzona").val("").change();
        }
        //session_set("superselect,idzona", $(this).selectData().idzona, 0);
    }
});

$("#codice_cig, #codice_cup").bind("keyup change", function (e) {
    if ($("#codice_cig").val() == "" && $("#codice_cup").val() == "") {
        $("#id_documento_fe").prop("required", false);
    } else {
        $("#id_documento_fe").prop("required", true);
    }
});
</script>';

// Collegamenti diretti
// Fatture collegate a questo intervento
$elementi = $dbo->fetchArray('SELECT `co_documenti`.*, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_statidocumento`.`descrizione` AS stato_documento, `co_tipidocumento`.`dir` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idintervento` = '.prepare($id_record).') ORDER BY `data`');

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
        $descrizione = tr('_DOC_ num. _NUM_ del _DATE_ [_STATE_]', [
            '_DOC_' => $fattura['tipo_documento'],
            '_NUM_' => !empty($fattura['numero_esterno']) ? $fattura['numero_esterno'] : $fattura['numero'],
            '_DATE_' => Translator::dateToLocale($fattura['data']),
            '_STATE_' => $fattura['stato_documento'],
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
