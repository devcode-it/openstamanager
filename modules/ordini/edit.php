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
use Models\Module;
use Modules\Ordini\Stato;

$block_edit = $record['flag_completato'];
$module = Module::find($id_module);

if ($module->getTranslation('name', \Models\Locale::getPredefined()->id) == 'Ordini cliente') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

$righe = $ordine->getRighe();
$righe_vuote = false;
foreach ($righe as $riga) {
    if ($riga->qta == 0) {
        $righe_vuote = true;
    }
}
if ($righe_vuote) {
    echo '
    <div class="alert alert-warning" id="righe-vuote">
        <i class="fa fa-warning"></i> '.tr('Nel documento sono presenti delle righe con quantità a 0.').'</b>
    </div>';
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
                        $id_stato_evaso = (new Stato())->getByField('name', 'Evaso', Models\Locale::getPredefined()->id);
                        $id_stato_parz_evaso = (new Stato())->getByField('name', 'Parzialmente evaso', Models\Locale::getPredefined()->id);
                        $id_stato_fatt = (new Stato())->getByField('name', 'Fatturato', Models\Locale::getPredefined()->id);
                        $id_stato_parz_fatt = (new Stato())->getByField('name', 'Parzialmente fatturato', Models\Locale::getPredefined()->id);

                        if ($ordine->stato->id == $id_stato_fatt || $ordine->stato->id == $id_stato_parz_fatt || $ordine->stato->id == $id_stato_evaso || $ordine->stato->id == $id_stato_parz_evaso) {
                            ?>
                            {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatoordine", "required": 1, "values": "query=SELECT `or_statiordine`.*, `or_statiordine_lang`.`name` as descrizione, `colore` AS _bgcolor_ FROM `or_statiordine` LEFT JOIN `or_statiordine_lang` ON (`or_statiordine_lang`.`id_record` = `or_statiordine`.`id` AND `or_statiordine_lang`.`id_lang` = <?php echo prepare(Models\Locale::getDefault()->id); ?>) ORDER BY `name`", "value": "$idstatoordine$", "extra": "readonly", "class": "unblockable" ]}
                    <?php
                        } else {
                            ?>
                            {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatoordine", "required": 1, "values": "query=SELECT `or_statiordine`.*, `or_statiordine_lang`.`name` as descrizione, `colore` AS _bgcolor_ FROM `or_statiordine` LEFT JOIN `or_statiordine_lang` ON (`or_statiordine_lang`.`id_record` = `or_statiordine`.`id` AND `or_statiordine_lang`.`id_lang` = <?php echo prepare(Models\Locale::getDefault()->id); ?>) WHERE (`is_fatturabile` = 0 AND `name` != 'Fatturato' || `name` = 'Accettato') ORDER BY `name`", "value": "$idstatoordine$", "class": "unblockable" ]}
                    <?php
                        }
                    } else {
                        ?>
                    {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatoordine", "required": 1, "values": "query=SELECT `or_statiordine`.*, `colore` AS _bgcolor_, `or_statiordine_lang`.`name` as descrizione FROM `or_statiordine` LEFT JOIN `or_statiordine_lang` ON (`or_statiordine_lang`.`id_record` = `or_statiordine`.`id` AND `or_statiordine_lang`.`id_lang` = <?php echo prepare(Models\Locale::getDefault()->id); ?>) ORDER BY `name`", "value": "$idstatoordine$", "class": "unblockable" ]}
                    <?php
                    }
?>
				</div>
<?php
            if ($dir == 'entrata') {
                echo '
                <div class="col-md-3">';
                if ($record['idagente'] != 0) {
                    echo Modules::link('Anagrafiche', $record['idagente'], null, null, 'class="pull-right"');
                }
                echo '
                    {[ "type": "select", "label": "'.tr('Agente').'", "name": "idagente", "ajax-source": "agenti", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "value": "$idagente$" ]}
                </div>';
            }
?>
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
						{[ "type": "select", "label": "<?php echo tr('Fornitore'); ?>", "name": "idanagrafica", "required": 1, "ajax-source": "fornitori", "value": "$idanagrafica$" ]}
					<?php
}
echo '
                </div>

                <div class="col-md-3">';
if (!empty($record['idreferente'])) {
    echo Plugins::link('Referenti', $record['idanagrafica'], null, null, 'class="pull-right"');
}
echo '
                    {[ "type": "select", "label": "'.tr('Referente').'", "name": "idreferente", "value": "$idreferente$", "ajax-source": "referenti", "select-options": {"idanagrafica": '.$record['idanagrafica'].', "idsede_destinazione": '.$record['idsede'].'} ]}
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

                <div class="col-md-3">
                    {[ "type": "date", "label": "<?php echo tr('Data ordine cliente'); ?>", "name": "data_cliente", "value": "<?php echo $record['data_cliente']; ?>" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo 'Sconto in fattura'; ?>", "name": "sconto_finale", "value": "<?php echo $ordine->sconto_finale_percentuale ?: $ordine->sconto_finale; ?>", "icon-after": "choice|untprc|<?php echo empty($ordine->sconto_finale) ? 'PRC' : 'UNT'; ?>", "help": "<?php echo tr('Sconto in fattura, utilizzabile per applicare sconti sul netto a pagare del documento'); ?>." ]}
                </div>
            </div>
                <?php
}
?>

            <div class="row">
				<div class="col-md-12">
                    <?php echo input([
                        'type' => 'ckeditor',
                        'use_full_ckeditor' => 0,
                        'label' => tr('Condizioni generali di fornitura'),
                        'name' => 'condizioni_fornitura',
                        'value' => $record['condizioni_fornitura'],
                    ]);
?>
                </div>
			</div>
            
			<div class="row">
				<div class="col-md-6">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
				</div>
                <div class="col-md-6">
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
    $prev_query = 'SELECT 
            COUNT(*) AS tot 
        FROM 
            `co_preventivi`
            INNER JOIN `co_statipreventivi` ON `co_statipreventivi`.`id` = `co_preventivi`.`idstato`
            INNER JOIN `co_righe_preventivi` ON `co_preventivi`.`id` = `co_righe_preventivi`.`idpreventivo`
        WHERE 
            `idanagrafica`='.prepare($record['idanagrafica']).' AND `co_statipreventivi`.`is_fatturabile` = 1 AND `default_revision`=1 AND (`co_righe_preventivi`.`qta` - `co_righe_preventivi`.`qta_evasa` > 0)';
    $preventivi = $dbo->fetchArray($prev_query)[0]['tot'];
    echo '
		<div class="clearfix"></div>';

    // Form di inserimento riga documento
    echo '
        <form id="link_form" action="" method="post">
            <input type="hidden" name="op" value="add_articolo">
            <input type="hidden" name="backto" value="record-edit">

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "text", "label": "'.tr('Aggiungi un articolo tramite barcode').'", "name": "barcode", "extra": "autocomplete=\"off\"", "icon-before": "<i class=\"fa fa-barcode\"></i>", "required": 0 ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Articolo').'", "name": "id_articolo", "value": "", "ajax-source": "articoli", "select-options": {"permetti_movimento_a_zero":  1}, "icon-after": "add|'.(new Module())->getByField('name', 'Articoli', Models\Locale::getPredefined()->id).'" ]}
                </div>

                <div class="col-md-4" style="margin-top: 25px">
                    <button title="'.tr('Aggiungi articolo alla vendita').'" class="btn btn-primary tip" type="button" onclick="salvaArticolo()">
                        <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                    </button>
                    
                    <a class="btn btn-primary" onclick="gestioneRiga(this)" data-title="'.tr('Aggiungi riga').'">
                        <i class="fa fa-plus"></i> '.tr('Riga').'
                    </a>

                    <div class="btn-group tip" data-toggle="tooltip">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                            <i class="fa fa-list"></i> '.tr('Altro').'
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            <li>
                                <a style="cursor:pointer" onclick="gestioneDescrizione(this)" data-title="'.tr('Aggiungi descrizione').'">
                                    <i class="fa fa-plus"></i> '.tr('Descrizione').'
                                </a>
                            </li>

                            <li>
                                <a style="cursor:pointer" onclick="gestioneSconto(this)" data-title="'.tr('Aggiungi sconto/maggiorazione').'">
                                    <i class="fa fa-plus"></i> '.tr('Sconto/maggiorazione').'
                                </a>
                            </li>';
    if ($dir == 'entrata') {
        echo '
                            <li>
                                <a class="'.(!empty($preventivi) ? '' : ' disabled').'" style="cursor:pointer" data-href="'.$structure->fileurl('add_preventivo.php').'?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="modal" data-title="'.tr('Aggiungi Preventivo').'" onclick="saveForm()">
                                    <i class="fa fa-plus"></i> '.tr('Preventivo').'
                                </a>
                            </li>';
    }
    echo '
                        </ul>
                    </div>';
    if ($dir == 'entrata') {
        echo '
                    <div class="pull-right">
                        <a class="btn btn-info" data-href="'.$structure->fileurl('quantita_impegnate.php').'?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="tooltip" data-title="'.tr('Controllo sulle quantità impegnate').'" onclick="saveForm()">
                            <i class="fa fa-question-circle"></i> '.tr('Disponibilità').'
                        </a>
                    </div>';
    }
    echo '
                </div>

                <div class="col-md-2">
                    {[ "type": "select", "label": "'.tr('Ordinamento').'", "name": "ordinamento", "class": "no-search", "value": "'.($_SESSION['module_'.$id_module]['order_row_desc'] ? 'desc' : 'manuale').'", "values": "list=\"desc\": \"'.tr('Ultima riga inserita').'\", \"manuale\": \"'.tr('Manuale').'\"" ]}
                </div>
            </div>
        </form>';
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
async function saveForm() {
    // Salvataggio via AJAX
    await salvaForm("#edit-form");
}

function gestioneSconto(button) {
    gestioneRiga(button, "is_sconto");
}

function gestioneDescrizione(button) {
    gestioneRiga(button, "is_descrizione");
}

async function gestioneRiga(button, options) {
    // Salvataggio via AJAX
    await salvaForm("#edit-form", {}, button);

    // Lettura titolo e chiusura tooltip
    let title = $(button).attr("data-title");

    // Apertura modal
    options = options ? options : "is_riga";
    openModal(title, "'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&" + options);
}

/**
 * Funzione dedicata al caricamento dinamico via AJAX delle righe del documento.
 */
function caricaRighe(id_riga) {
    let container = $("#righe");

    localLoading(container, true);
    return $.get("'.$structure->fileurl('row-list.php').'?id_module='.$id_module.'&id_record='.$id_record.'", function(data) {
        container.html(data);
        localLoading(container, false);
        if (id_riga != null) {
            $("tr[data-id="+ id_riga +"]").effect("highlight",1000);
        }
    });
}

$("#idsede").change(function(){
    updateSelectOption("idsede_destinazione", $(this).val());
    $("#idreferente").selectReset();
});

$("#idanagrafica").change(function() {
    updateSelectOption("idanagrafica", $(this).val());
    session_set("superselect,idanagrafica", $(this).val(), 0);

	$("#idsede").selectReset();
    $("#idpagamento").selectReset();
    
    let data = $(this).selectData();
	if (data) {
        // Impostazione del tipo di pagamento da anagrafica
        if (data.id_pagamento) {
            input("idpagamento").getElement()
                .selectSetNew(data.id_pagamento, data.desc_pagamento);
        }
    }
});

$(document).ready(function() {
    caricaRighe(null);

	$("#codice_cig, #codice_cup").bind("keyup change", function(e) {
		if ($("#codice_cig").val() == "" && $("#codice_cup").val() == "" ){
			$("#numero_cliente").prop("required", false);
		} else{
			$("#numero_cliente").prop("required", true);
		}
	});

    $("#id_articolo").on("change", function(e) {
        if ($(this).val()) {
            var data = $(this).selectData();

            if (data.barcode) {
                $("#barcode").val(data.barcode);
            } else {
                $("#barcode").val("");
            }
        }

        e.preventDefault();

        setTimeout(function(){
            $("#barcode").focus();
        }, 100);
    });

    $("#barcode").focus();
});

async function salvaArticolo() {
    // Salvataggio via AJAX
    await salvaForm("#edit-form");
    
    $("#link_form").ajaxSubmit({
        url: globals.rootdir + "/actions.php",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            ajax: true,
        },
        type: "post",
        beforeSubmit: function(arr, $form, options) {
            return $form.parsley().validate();
        },
        success: function(response){
            renderMessages();
            if(response.length > 0){
                response = JSON.parse(response);
                swal({
                    type: "error",
                    title: "'.tr('Errore').'",
                    text: response.error,
                });
            }

            $("#barcode").val("");
            $("#id_articolo").selectReset();
            content_was_modified = false;
            caricaRighe(null);
        }
    });
}

$("#link_form").bind("keypress", function(e) {
    if (e.keyCode == 13) {
        e.preventDefault();
        salvaArticolo();
        return false;
    }
});

input("ordinamento").on("change", function(){
    if (input(this).get() == "desc") {
        session_set("module_'.$id_module.',order_row_desc", 1, "").then(function () {
            caricaRighe(null);
        });
    } else {
        session_set("module_'.$id_module.',order_row_desc").then(function () {
            caricaRighe(null);
        });
    }
});
</script>';

// Collegamenti diretti
// Fatture o ddt collegati a questo ordine
$elementi = $dbo->fetchArray('SELECT `co_documenti`.`id`, `co_documenti`.`data`, `co_documenti`.`numero`, `co_documenti`.`numero_esterno`, `co_tipidocumento_lang`.`name` AS tipo_documento, `co_tipidocumento`.`dir` FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id` WHERE `co_righe_documenti`.`idordine` = '.prepare($id_record).')

UNION SELECT `dt_ddt`.`id`, `dt_ddt`.`data`, `dt_ddt`.`numero`, `dt_ddt`.`numero_esterno`, `dt_tipiddt_lang`.`name` AS tipo_documento, `dt_tipiddt`.`dir` FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt` LEFT JOIN `dt_tipiddt_lang` ON (`dt_tipiddt_lang`.`id_record` = `dt_tipiddt`.`id` AND `dt_tipiddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `dt_righe_ddt` ON `dt_righe_ddt`.`idddt` = `dt_ddt`.`id` WHERE `dt_righe_ddt`.`idordine` = '.prepare($id_record).') ORDER BY `data`');

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
    <i id ="elimina" class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

