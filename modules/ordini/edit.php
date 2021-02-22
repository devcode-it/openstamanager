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

include_once __DIR__.'/../../core.php';

$block_edit = $record['flag_completato'];

$module = Modules::get($id_module);

if ($module['name'] == 'Ordini cliente') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
	<!-- INTESTAZIONE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Intestazione'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">

				<div class="col-md-3" <?php echo ($dir == 'entrata') ? 'hidden' : ''; ?>>
					{[ "type": "text", "label": "<?php echo tr('Numero ordine'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "$numero$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo ($dir == 'entrata') ? tr('Numero ordine') : tr('Numero ordine fornitore'); ?>", "name": "numero_esterno", "class": "text-center", "value": "$numero_esterno$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "required": 1, "value": "$data$" ]}
				</div>

				<div class="col-md-3">
                    <?php

                    if (setting('Cambia automaticamente stato ordini fatturati')) {
                        if ($record['stato'] == 'Evaso' || $record['stato'] == 'Parzialmente evaso' || $record['stato'] == 'Fatturato' || $record['stato'] == 'Parzialmente fatturato') {
                            ?>
                            {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatoordine", "required": 1, "values": "query=SELECT * FROM or_statiordine", "value": "$idstatoordine$", "extra": "readonly", "class": "unblockable" ]}
                    <?php
                        } else {
                            ?>
                            {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatoordine", "required": 1, "values": "query=SELECT * FROM or_statiordine WHERE descrizione IN('Bozza', 'Accettato', 'In attesa di conferma', 'Annullato')", "value": "$idstatoordine$", "class": "unblockable" ]}
                    <?php
                        }
                    } else {
                        ?>
                    {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatoordine", "required": 1, "values": "query=SELECT * FROM or_statiordine", "value": "$idstatoordine$", "class": "unblockable" ]}
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
						{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "value": "$idanagrafica$", "ajax-source": "clienti" ]}
					<?php
                    } else {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Fornitore'); ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Fornitore' AND deleted_at IS NULL ORDER BY ragione_sociale", "value": "$idanagrafica$" ]}
					<?php
                    }
                echo '
                </div>
                
                <div class="col-md-3">';
                    if (!empty($record['idreferente'])) {
                        echo Plugins::link('Referenti', $record['idanagrafica'], null, null, 'class="pull-right"');
                    }
                    echo '
                    {[ "type": "select", "label": "'.tr('Referente').'", "name": "idreferente", "value": "$idreferente$", "ajax-source": "referenti", "select-options": {"idanagrafica": '.$record['idanagrafica'].'} ]}
                </div>

                <div class="col-md-3">
					{[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede", "required": 1, "ajax-source": "sedi", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "value": "'.$record['idsede'].'" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "'.tr('Pagamento').'", "name": "idpagamento", "required": 0, "ajax-source": "pagamenti", "value": "$idpagamento$" ]}
				</div>
			</div>';

            if ($dir == 'entrata') {
                ?>
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Numero ordine cliente'); ?>", "name": "numero_cliente", "required":0, "value": "<?php echo $record['numero_cliente']; ?>", "help": "<?php echo tr('<span>Obbligatorio per valorizzare CIG/CUP. &Egrave; possible inserire: </span><ul><li>N. determina</li><li>RDO</li><li>Ordine MEPA</li></ul>'); ?>" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "date", "label": "<?php echo tr('Data ordine cliente'); ?>", "name": "data_cliente", "value": "<?php echo $record['data_cliente']; ?>" ]}
                </div>
            </div>

                <?php
            }
            ?>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
				</div>
			</div>

            <div class="row">
                <div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note interne'); ?>", "name": "note_aggiuntive", "value": "$note_aggiuntive$" ]}
				</div>
			</div>
		</div>
	</div>

    <?php
        if (!empty($record['codice_commessa']) || !empty($record['num_item']) || !empty($record['codice_cig']) || !empty($record['codice_cup'])) {
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
                            {[ "type": "text", "label": "<?php echo tr('Codice Commessa'); ?>", "name": "codice_commessa", "required": 0, "value": "$codice_commessa$", "maxlength": 100 ]}
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

<?php

echo '
<!-- RIGHE -->
<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title">'.tr('Righe').'</h3>
	</div>

	<div class="panel-body">';

if (!$block_edit) {
    echo '
		<div class="pull-left">';

    $prev_query = 'SELECT COUNT(*) AS tot FROM co_preventivi WHERE idanagrafica='.prepare($record['idanagrafica']).' AND idstato IN(SELECT id FROM co_statipreventivi WHERE is_fatturabile = 1) AND default_revision=1 AND co_preventivi.id IN (SELECT idpreventivo FROM co_righe_preventivi WHERE co_righe_preventivi.idpreventivo = co_preventivi.id AND (qta - qta_evasa) > 0)';
    $preventivi = $dbo->fetchArray($prev_query)[0]['tot'];
    if ($dir == 'entrata') {
        echo '
        <div class="tip">
            <a class="btn btn-sm btn-primary '.(!empty($preventivi) ? '' : ' disabled').'" data-href="'.base_path().'/modules/ordini/add_preventivo.php?id_module='.$id_module.'&id_record='.$id_record.'" data-title="'.tr('Aggiungi preventivo').'" data-toggle="tooltip">
                <i class="fa fa-plus"></i> '.tr('Preventivo').'
            </a>
        </div>';
    }
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

    echo '
        </div>';

    if ($dir == 'entrata') {
        echo '
        <div class="pull-right">
            <a class="btn btn-sm btn-info" data-href="'.$structure->fileurl('quantita_impegnate.php').'?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="tooltip" data-title="'.tr('Controllo sulle quantità impegnate').'">
                <i class="fa fa-question-circle"></i> '.tr('Verifica disponibilità').'
            </a>
        </div>';
    }

    echo '
		<div class="clearfix"></div>
		<br>';
}

echo '
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

	$("#idsede").selectReset();
});

$(document).ready(function() {
	$("#codice_cig, #codice_cup").bind("keyup change", function(e) {
		if ($("#codice_cig").val() == "" && $("#codice_cup").val() == "" ){
			$("#numero_cliente").prop("required", false);
		} else{
			$("#numero_cliente").prop("required", true);
		}
	});
});
</script>';

// Collegamenti diretti
// Fatture o ddt collegati a questo ordine
$elementi = $dbo->fetchArray('SELECT `co_documenti`.`id`, `co_documenti`.`data`, `co_documenti`.`numero`, `co_documenti`.`numero_esterno`, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idordine` = '.prepare($id_record).')

UNION SELECT `dt_ddt`.`id`, `dt_ddt`.`data`, `dt_ddt`.`numero`, `dt_ddt`.`numero_esterno`, `dt_tipiddt`.`descrizione` AS tipo_documento, `dt_tipiddt`.`dir` FROM `dt_ddt` JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt` WHERE `dt_ddt`.`id` IN (SELECT `idddt` FROM `dt_righe_ddt` WHERE `idordine` = '.prepare($id_record).') ORDER BY `data`');

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
        $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
            '_DOC_' => $elemento['tipo_documento'],
            '_NUM_' => !empty($elemento['numero_esterno']) ? $elemento['numero_esterno'] : $elemento['numero'],
            '_DATE_' => Translator::dateToLocale($elemento['data']),
        ]);

        if (!in_array($elemento['tipo_documento'], ['Ddt in uscita', 'Ddt in entrata'])) {
            $modulo = ($elemento['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto';
        } else {
            $modulo = ($elemento['dir'] == 'entrata') ? 'Ddt di vendita' : 'Ddt di acquisto';
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

