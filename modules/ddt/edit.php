<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

include_once __DIR__.'/../../core.php';

$block_edit = $record['flag_completato'];

$module = Modules::get($id_module);

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
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
                    $rs2 = $dbo->fetchArray('SELECT piva, codice_fiscale, citta, indirizzo, cap, provincia FROM an_anagrafiche WHERE idanagrafica='.prepare($record['idanagrafica']));
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

                    if (sizeof($campi_mancanti) > 0) {
                        echo "<div class='alert alert-warning'><i class='fa fa-warning'></i> Prima di procedere alla stampa completa i seguenti campi dell'anagrafica:<br/><b>".implode(', ', $campi_mancanti).'</b><br/>
						'.Modules::link('Anagrafiche', $record['idanagrafica'], tr('Vai alla scheda anagrafica'), null).'</div>';
                    }
                }
            ?>


			<div class="row">
				<?php
                    if ($dir == 'uscita') {
                        echo '
                <div class="col-md-3">
                    {[ "type": "span", "label": "'.tr('Numero ddt').'", "class": "text-center", "value": "$numero$" ]}
                </div>';
                    }
                ?>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Numero secondario'); ?>", "name": "numero_esterno", "class": "text-center", "value": "$numero_esterno$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "required": 1, "value": "$data$" ]}
				</div>

				<div class="col-md-3">
                    <?php
                    if (setting('Cambia automaticamente stato ddt fatturati')) {
                        if ($record['stato'] == 'Fatturato' || $record['stato'] == 'Parzialmente fatturato') {
                            ?>
                            {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatoddt", "required": 1, "values": "query=SELECT * FROM dt_statiddt", "value": "$idstatoddt$", "extra": "readonly", "class": "unblockable" ]}
                    <?php
                        } else {
                            ?>
                            {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatoddt", "required": 1, "values": "query=SELECT * FROM dt_statiddt WHERE descrizione IN('Bozza', 'Evaso', 'Parzialmente evaso')", "value": "$idstatoddt$", "class": "unblockable" ]}
                    <?php
                        }
                    } else {
                        ?>
                    {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatoddt", "required": 1, "values": "query=SELECT * FROM dt_statiddt", "value": "$idstatoddt$", "class": "unblockable" ]}
                    <?php
                    }
                    ?>
				</div>
			</div>

            <div class="row">
                <div class="col-md-3">
                    <?php echo Modules::link('Anagrafiche', $record['idanagrafica'], null, null, 'class="pull-right"'); ?>
                    {[ "type": "select", "label": "<?php echo ($dir == 'uscita') ? tr('Mittente') : tr('Destinatario'); ?>", "name": "idanagrafica", "required": 1, "value": "$idanagrafica$", "ajax-source": "clienti_fornitori" ]}
                </div>

                <?php
                // Conteggio numero articoli ddt in uscita
                $articolo = $dbo->fetchArray('SELECT mg_articoli.id FROM ((mg_articoli INNER JOIN dt_righe_ddt ON mg_articoli.id=dt_righe_ddt.idarticolo) INNER JOIN dt_ddt ON dt_ddt.id=dt_righe_ddt.idddt) WHERE dt_ddt.id='.prepare($id_record));
                $id_modulo_anagrafiche = Modules::get('Anagrafiche')['id'];
                $id_plugin_sedi = Plugins::get('Sedi')['id'];
                if ($dir == 'entrata') {
                    echo '
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Partenza merce').'", "name": "idsede_partenza", "ajax-source": "sedi_azienda", "value": "$idsede_partenza$", "readonly": "'.(sizeof($articolo) ? 1 : 0).'", "help": "'.tr("Sedi di partenza dell'azienda").'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Destinazione merce').'", "name": "idsede_destinazione", "ajax-source": "sedi", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "value": "$idsede_destinazione$", "help": "'.tr('Sedi del destinatario').'", "icon-after": "add|'.$id_modulo_anagrafiche.'|id_plugin='.$id_plugin_sedi.'&id_parent='.$record['idanagrafica'].'||'.(intval($block_edit) ? 'disabled' : '').'" ]}
                </div>';
                } else {
                    echo '
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Partenza merce').'", "name": "idsede_partenza", "ajax-source": "sedi", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "value": "$idsede_partenza$", "help": "'.tr('Sedi del mittente').'", "icon-after": "add|'.$id_modulo_anagrafiche.'|id_plugin='.$id_plugin_sedi.'&id_parent='.$record['idanagrafica'].'||'.(intval($block_edit) ? 'disabled' : '').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Destinazione merce').'", "name": "idsede_destinazione", "ajax-source": "sedi_azienda", "value": "$idsede_destinazione$", "help": "'.tr("Sedi di arrivo dell'azienda").'" ]}
                </div>';
                }
                ?>
            </div>
            <hr>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Aspetto beni'); ?>", "name": "idaspettobeni", "value": "$idaspettobeni$", "ajax-source": "aspetto-beni", "icon-after": "add|<?php echo Modules::get('Aspetto beni')['id']; ?>|||<?php echo $block_edit ? 'disabled' : ''; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Causale trasporto'); ?>", "name": "idcausalet", "required": 1, "value": "$idcausalet$", "ajax-source": "causali", "icon-after": "add|<?php echo Modules::get('Causali')['id']; ?>|||<?php echo $block_edit ? 'disabled' : ''; ?>", "help": "<?php echo tr('Definisce la causale del trasporto'); ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Tipo di spedizione'); ?>", "name": "idspedizione", "placeholder": "-", "values": "query=SELECT id, descrizione FROM dt_spedizione ORDER BY descrizione ASC", "value": "$idspedizione$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Num. colli'); ?>", "name": "n_colli", "value": "$n_colli$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Pagamento'); ?>", "name": "idpagamento", "ajax-source": "pagamenti", "value": "$idpagamento$" ]}
				</div>

                <div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Porto'); ?>", "name": "idporto", "placeholder": "-", "help": "<?php echo tr('<ul><li>Franco: pagamento del trasporto a carico del mittente</li> <li>Assegnato: pagamento del trasporto a carico del destinatario</li> </ul>'); ?>", "values": "query=SELECT id, descrizione FROM dt_porto ORDER BY descrizione ASC", "value": "$idporto$" ]}
				</div>

				<div class="col-md-3">
                    <?php 
                        if (!empty($record['idvettore']))
                            echo Modules::link('Anagrafiche', $record['idvettore'], null, null, 'class="pull-right"');
                    ?>
					{[ "type": "select", "label": "<?php echo tr('Vettore'); ?>", "name": "idvettore", "ajax-source": "vettori", "value": "$idvettore$", "disabled": <?php echo intval($record['idspedizione'] == 3); ?>, "required": <?php echo (!empty($record['idspedizione'])) ? intval($record['idspedizione'] != 3) : 0; ?>, "icon-after": "add|<?php echo Modules::get('Anagrafiche')['id']; ?>|tipoanagrafica=Vettore&readonly_tipo=1|btn_idvettore|<?php echo (($record['idspedizione'] != 3 and intval(!$record['flag_completato']))) ? '' : 'disabled'; ?>" ]}
				</div>

                <div class="col-md-3">
					{[ "type": "timestamp", "label": "<?php echo tr('Data ora trasporto'); ?>", "name": "data_ora_trasporto", "required": 0, "value": "$data_ora_trasporto$" ]}
				</div>

                 <script>
                    $("#idspedizione").change(function() {
                        //Per tutti tipi di spedizione, a parte "Espressa" o "Vettore", il campo vettore non deve essere richiesto
                        if ($(this).val() != 1 && $(this).val() != 2 ) {
                            $("#idvettore").attr("required", false);
                            $("#idvettore").attr("disabled", true);
                            $("label[for=idvettore]").text("<?php echo tr('Vettore'); ?>");
                            $("#idvettore").selectReset("<?php echo tr("Seleziona un\'opzione"); ?>");
                            $(".btn_idvettore").prop("disabled", true);
                            $(".btn_idvettore").addClass("disabled");
                        }else{
                            $("#idvettore").attr("required", true);
                            $("#idvettore").attr("disabled", false);
                            $("label[for=idvettore]").text("<?php echo tr('Vettore'); ?>*");
                            $(".btn_idvettore").prop("disabled", false);
                            $(".btn_idvettore").removeClass("disabled");

                        }
                    });

                    $("#idcausalet").change(function() {
                        if ($(this).val() == 3) {
                            $("#tipo_resa").attr("disabled", false);
                        }else{
                            $("#tipo_resa").attr("disabled", true);
                        }
                    });
                </script>
			</div>
<?php

if ($dir == 'entrata') {
    echo '
        <div class="row">
            <div class="col-md-3">
                {[ "type": "number", "label": "'.tr('Peso').'", "name": "peso", "value": "$peso$", "readonly": "'.intval(empty($record['peso'])).'", "help": "'.tr('Il valore del campo Peso viene calcolato in automatico sulla base degli articoli inseriti nel documento, a meno dell\'impostazione di un valore manuale in questo punto').'" ]}
            </div>

            <div class="col-md-3">
                {[ "type": "checkbox", "label": "'.tr('Modifica peso').'", "name": "peso_manuale", "value": '.intval(!empty($record['peso'])).', "help": "'.tr('Seleziona per modificare manualmente il campo Peso').'", "placeholder": "'.tr('Modifica peso').'" ]}

                <script type="text/javascript">
                    $(document).ready(function() {
                        $("#peso_manuale").click(function() {
                            $("#peso").prop("readonly", !$("#peso_manuale").is(":checked"));
                        });
                    });
                </script>
            </div>

            <div class="col-md-3">
                {[ "type": "number", "label": "'.tr('Volume').'", "name": "volume", "value": "$volume$", "readonly": "'.intval(empty($record['volume'])).'", "help": "'.tr('Il valore del campo Volume viene calcolato in automatico sulla base degli articoli inseriti nel documento, a meno dell\'impostazione di un valore manuale in questo punto').'" ]}
            </div>

            <div class="col-md-3">
                {[ "type": "checkbox", "label": "'.tr('Modifica volume').'", "name": "volume_manuale", "value": '.intval(!empty($record['volume'])).', "help": "'.tr('Seleziona per modificare manualmente il campo Volume').'", "placeholder": "'.tr('Modifica volume').'" ]}

                <script type="text/javascript">
                    $(document).ready(function() {
                        $("#volume_manuale").click(function() {
                            $("#volume").prop("readonly", !$("#volume_manuale").is(":checked"));
                        });
                    });
                </script>
            </div>
        </div>';
}

?>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
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
        if (!empty($record['id_documento_fe']) || !empty($record['num_item']) || !empty($record['codice_cig']) || !empty($record['codice_cup'])) {
            $collapsed = 'in';
        } else {
            $collapsed = '';
        }
    ?>

    <!-- Fatturazione Elettronica PA-->
    <div class="panel-group">
        <div class="panel panel-primary <?php echo ($record['tipo_anagrafica'] == 'Ente pubblico' || $record['tipo_anagrafica'] == 'Azienda') ? 'show' : 'hide'; ?>">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <?php echo tr('Dati appalto'); ?>

                    <div class="box-tools pull-right">
                        <a data-toggle="collapse" href="#dati_appalto"><i class="fa fa-plus" style='color:white;margin-top:2px;'></i></a>
                    </div>
                </h4>
            </div>
            <div id="dati_appalto" class="panel-collapse collapse <?php echo $collapsed; ?>">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "text", "label": "<?php echo tr('Identificatore Documento'); ?>", "name": "id_documento_fe", "required": 0, "help": "<?php echo tr('<span>Obbligatorio per valorizzare CIG/CUP. &Egrave; possible inserire: </span><ul><li>N. determina</li><li>RDO</li><li>Ordine MEPA</li></ul>'); ?>", "value": "$id_documento_fe$", "maxlength": 20 ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "text", "label": "<?php echo tr('Numero Riga'); ?>", "name": "num_item", "required": 0, "value": "$num_item$", "maxlength": 15 ]}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "text", "label": "<?php echo tr('Codice CIG'); ?>", "name": "codice_cig", "required": 0, "value": "$codice_cig$", "maxlength": 15 ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "text", "label": "<?php echo tr('Codice CUP'); ?>", "name": "codice_cup", "required": 0, "value": "$codice_cup$", "maxlength": 15 ]}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- RIGHE -->
<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title"><?php echo tr('Righe'); ?></h3>
	</div>

	<div class="panel-body">
		<div class="pull-left">
<?php

if (!$block_edit) {
    // Lettura ordini (cliente o fornitore)
    $ordini_query = 'SELECT COUNT(*) AS tot FROM or_ordini WHERE idanagrafica='.prepare($record['idanagrafica']).' AND idstatoordine IN (SELECT id FROM or_statiordine WHERE descrizione IN(\'Accettato\', \'Evaso\', \'Parzialmente evaso\', \'Parzialmente fatturato\')) AND idtipoordine=(SELECT id FROM or_tipiordine WHERE dir='.prepare($dir).') AND or_ordini.id IN (SELECT idordine FROM or_righe_ordini WHERE or_righe_ordini.idordine = or_ordini.id AND (qta - qta_evasa) > 0)';
    $ordini = $dbo->fetchArray($ordini_query)[0]['tot'];

    echo '
            <a class="btn btn-sm btn-primary'.(!empty($ordini) ? '' : ' disabled').'" data-href="'.base_path().'/modules/ddt/add_ordine.php?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="modal" data-title="Aggiungi ordine">
                <i class="fa fa-plus"></i> '.tr('Ordine').'
            </a>';

    echo '
            <button type="button" class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi articolo').'" onclick="gestioneArticolo(this)">
                <i class="fa fa-plus"></i> '.tr('Articolo').'
            </button>';

    echo '
            <button type="button" class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi articoli tramite barcode').'" onclick="gestioneBarcode(this)">
                <i class="fa fa-plus"></i> '.tr('Barcode').'
            </button>';

    echo '
            <button type="button" class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi riga').'" onclick="gestioneRiga(this)">
                <i class="fa fa-plus"></i> '.tr('Riga').'
            </button>';

    echo '
            <button type="button" class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi descrizione').'" onclick="gestioneDescrizione(this)">
                <i class="fa fa-plus"></i> '.tr('Descrizione').'
            </button>';

    echo '
            <button type="button" class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi sconto/maggiorazione').'" onclick="gestioneSconto(this)">
                <i class="fa fa-plus"></i> '.tr('Sconto/maggiorazione').'
            </button>';
}

echo '
		</div>
		<div class="clearfix"></div>
		<br>

		<div class="row">
			<div class="col-md-12" id="righe"></div>
		</div>
	</div>
</div>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

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
        $(button).tooltipster("close")

        // Apertura modal
        options = options ? options : "is_riga";
        openModal(title, "'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&" + options);
    }
}

/**
 * Funzione dedicata al caricamento dinamico via AJAX delle righe del documento.
 */
function caricaRighe() {
    let container = $("#righe");

    localLoading(container, true);
    return $.get("'.$structure->fileurl('row-list.php').'?id_module='.$id_module.'&id_record='.$id_record.'", function(data) {
        container.html(data);
        localLoading(container, false);
    });
}

$(document).ready(function() {
    caricaRighe();
});

$("#idanagrafica").change(function() {
    updateSelectOption("idanagrafica", $(this).val());
    session_set("superselect,idanagrafica", $(this).val(), 0);

    $("#idsede_'.($dir == 'uscita' ? 'partenza' : 'destinazione').'").selectReset();
});
</script>';

// Collegamenti diretti
// Fatture collegate a questo ddt
$elementi = $dbo->fetchArray('SELECT `co_documenti`.*, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idddt` = '.prepare($id_record).') ORDER BY `data`');

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

<?php
// Eliminazione ddt solo se ho accesso alla sede aziendale
$field_name = ($dir == 'entrata') ? 'idsede_partenza' : 'idsede_destinazione';
if (in_array($record[$field_name], $user->sedi)) {
    ?>
    <a class="btn btn-danger ask" data-backto="record-list">
        <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
    </a>
<?php
}
