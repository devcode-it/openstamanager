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

$block_edit = $record['is_completato'];

// Mostro un avviso se ci sono più revisioni del preventivo
if (count($preventivo->revisioni) > 1) {
    echo '
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        '.tr('Questo preventivo presenta _N_ revisioni',
        [
            '_N_' => count($preventivo->revisioni),
        ]).'
    </div>
    ';
}

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

    <div class="row">
        <div class="col-md-2 offset-md-10">
            {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstato", "required": 1, "values": "query=SELECT `co_statipreventivi`.`id`, `co_statipreventivi_lang`.`title` AS descrizione, `colore` AS _bgcolor_ FROM `co_statipreventivi` LEFT JOIN `co_statipreventivi_lang` ON (`co_statipreventivi_lang`.`id_record` = `co_statipreventivi`.`id` AND `co_statipreventivi_lang`.`id_lang` = <?php echo prepare(Models\Locale::getDefault()->id); ?>) ORDER BY `title`", "value": "$idstato$", "class": "unblockable" ]}
        </div>
    </div>
    <?php echo '
	<!-- DATI INTESTAZIONE -->
    <div class="card card-primary collapsable">
        <div class="card-header with-border">
            <h3 class="card-title">'.tr('Dati cliente').'</h3>
            <div class="card-tools pull-right">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fa fa-minus"></i>
                </button>
            </div>
        </div>

      
        <div class="card-body">
            <!-- RIGA 1 -->
            <div class="row">
                <div class="col-md-4">
                    '.Modules::link('Anagrafiche', $record['idanagrafica'], null, null, 'class="pull-right"').'
                    {[ "type": "select", "label": "'.tr('Cliente').'", "name": "idanagrafica", "required": 1, "value": "$idanagrafica$", "ajax-source": "clienti" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Sede partenza').'", "name": "idsede_partenza", "ajax-source": "sedi_azienda", "value": "$idsede_partenza$", "select-options": '.json_encode(['idsede_partenza' => $record['idsede_partenza']]).', "help": "'.tr("Sedi di partenza dell'azienda").'" ]}
                </div>

                 <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Sede destinazione').'", "name": "idsede_destinazione", "value": "$idsede_destinazione$", "ajax-source": "sedi", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "placeholder": "Sede legale" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">';
if (!empty($record['idreferente'])) {
    echo Plugins::link('Referenti', $record['idanagrafica'], null, null, 'class="pull-right"');
}
echo '
                    {[ "type": "select", "label": "'.tr('Referente').'", "name": "idreferente", "value": "$idreferente$", "ajax-source": "referenti", "select-options": {"idanagrafica": '.$record['idanagrafica'].',"idsede_destinazione": '.$record['idsede_destinazione'].'} ]}
                </div>

                <div class="col-md-4">';
if ($record['idagente'] != 0) {
    echo Modules::link('Anagrafiche', $record['idagente'], null, null, 'class="pull-right"');
}
echo '
                    {[ "type": "select", "label": "'.tr('Agente').'", "name": "idagente", "ajax-source": "agenti", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "value": "$idagente$" ]}
                </div>
            </div>
        </div>
    </div>';
?>

	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Intestazione'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo tr('Numero'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "$numero$", "icon-after": "<?php echo (count($preventivo->revisioni) > 1) ? tr('rev.').' '.$preventivo->numero_revision : ''; ?>" ]}
				</div>

                <div class="col-md-2">
                    {[ "type": "date", "label": "<?php echo tr('Data bozza'); ?>", "name": "data_bozza","required": 1, "value": "$data_bozza$" ]}
                </div>

                <div class="col-md-2">
                    {[ "type": "number", "label": "<?php echo tr('Validità'); ?>", "name": "validita", "decimals": "0", "value": "$validita$", "icon-after": "choice|period|<?php echo $record['tipo_validita']; ?>", "help": "<?php echo tr('Il campo Validità viene utilizzato in modo esclusivamente indicativo se impostato secondo l\'opzione manuale, mentre viene utilizzato per il calcolo della Data di fine validità del documento in caso alternativo'); ?>" ]}
				</div>

                <div class="col-md-2">
                    {[ "type": "date", "label": "<?php echo tr('Data fine validità'); ?>", "name": "data_conclusione", "value": "$data_conclusione$", "disabled": "<?php echo $preventivo->isDataConclusioneAutomatica() ? '1", "help": "'.tr('La data di fine validità è calcolata in automatico in base al valore del campo Validità') : 0; ?>" ]}
                </div>

                <div class="col-md-2">
                    {[ "type": "date", "label": "<?php echo tr('Data accettazione'); ?>", "name": "data_accettazione", "value": "$data_accettazione$" ]}
                </div>

                <div class="col-md-2">
                    {[ "type": "date", "label": "<?php echo tr('Data rifiuto'); ?>", "name": "data_rifiuto", "value": "$data_rifiuto$" ]}
                </div>
			</div>
			
            <div class="row">
                <div class="col-md-3">
                    {[ "type": "text", "label": "<?php echo tr('Nome preventivo'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
                </div>

                <div class="col-md-3">
                    <?php
            if (!empty($record['idpagamento'])) {
                echo Modules::link('Pagamenti', $record['idpagamento'], null, null, 'class="pull-right"');
            }
?>
                    {[ "type": "select", "label": "<?php echo tr('Pagamento'); ?>", "name": "idpagamento", "ajax-source": "pagamenti", "value": "$idpagamento$" ]}
                </div>

				<div class="col-md-2">
					{[ "type": "select", "label": "<?php echo tr('Tipo di attività'); ?>", "name": "idtipointervento", "required": 1, "ajax-source": "tipiintervento", "value": "$idtipointervento$" ]}
				</div>
                <div class="col-md-2">
                    {[ "type": "number", "label": "<?php echo 'Sconto in fattura'; ?>", "name": "sconto_finale", "value": "<?php echo $preventivo->sconto_finale_percentuale ?: $preventivo->sconto_finale; ?>", "icon-after": "choice|untprc|<?php echo empty($preventivo->sconto_finale) ? 'PRC' : 'UNT'; ?>", "help": "<?php echo tr('Sconto in fattura, utilizzabile per applicare sconti sul netto a pagare del documento'); ?>." ]}
                </div>
				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo tr('Tempi di consegna'); ?>", "name": "tempi_consegna", "value": "$tempi_consegna$" ]}
				</div>
            </div>
            <div class="row">
                <div class="col-md-4">
					{[ "type": "textarea", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "class": "autosize", "value": "$descrizione$" ]}
				</div>
				<div class="col-md-4">
					{[ "type": "textarea", "label": "<?php echo tr('Esclusioni'); ?>", "name": "esclusioni", "class": "autosize", "value": "$esclusioni$" ]}
				</div>
				<div class="col-md-4">
					{[ "type": "textarea", "label": "<?php echo tr('Garanzia'); ?>", "name": "garanzia", "class": "autosize", "value": "$garanzia$" ]}
				</div>
			</div>

            <div class="row">
                <div class="col-md-6">
                    <?php echo input([
                        'type' => 'ckeditor',
                        'use_full_ckeditor' => 1,
                        'label' => tr('Condizioni generali di fornitura'),
                        'name' => 'condizioni_fornitura',
                        'value' => $record['condizioni_fornitura'],
                    ]);
echo '
                </div>
                
                <div class="col-md-6">
                    {[ "type": "textarea", "label": "'.tr('Note').'", "name": "note", "class": "autosize", "value": "$note$", "extra": "rows=\'5\'" ]}';
if ($user->gruppo != 'Clienti') {
    echo '
                    {[ "type": "textarea", "label": "'.tr('Note interne').'", "name": "informazioniaggiuntive", "class": "autosize", "value": "$informazioniaggiuntive$", "extra": "rows=\'5\'" ]}';
}
?>
                </div>
			</div>

            <!--div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
			</div-->

		</div>
	</div>

    <?php
        if (!empty($record['id_documento_fe']) || !empty($record['num_item']) || !empty($record['codice_cig']) || !empty($record['codice_cup'])) {
            $collapsed = '';
        } else {
            $collapsed = ' collapsed-card';
        }
?>

    <!-- Fatturazione Elettronica PA-->

    <div class="card card-primary collapsable  <?php echo ($record['tipo_anagrafica'] == 'Ente pubblico' || $record['tipo_anagrafica'] == 'Azienda') ? 'show' : 'hide'; ?> <?php echo $collapsed; ?>">
        <div class=" card-header">
            <h4 class=" card-title">
                
                <?php echo tr('Dati appalto'); ?></h4>

                <div class="card-tools pull-right">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fa fa-plus"></i>
                    </button>
                </div>
            
        </div>
        <div class="card-body">
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
</form>

<?php

echo '
<!-- RIGHE -->
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">'.tr('Righe').'</h3>
    </div>

    <div class="card-body">';

if (!$block_edit) {
    // Form di inserimento riga documento
    echo '
        <form id="link_form" action="" method="post">
            <input type="hidden" name="op" value="add_articolo">
            <input type="hidden" name="backto" value="record-edit">

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "text", "label": "'.tr('Aggiungi un articolo tramite barcode').'", "name": "barcode", "extra": "autocomplete=\"off\"", "icon-before": "<i class=\"fa fa-barcode\"></i>", "required": 0 ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Articolo').'", "name": "id_articolo", "value": "", "ajax-source": "articoli", "select-options": {"permetti_movimento_a_zero": 1}, "icon-after": "add|'.Module::where('name', 'Articoli')->first()->id.'" ]}
                </div>

                <div class="col-md-3" style="margin-top: 25px">
                    <button title="'.tr('Aggiungi articolo alla vendita').'" class="btn btn-primary tip" type="button" onclick="salvaArticolo()">
                        <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                    </button>
                    
                    <a class="btn btn-primary" onclick="gestioneRiga(this)" data-title="'.tr('Aggiungi riga').'">
                        <i class="fa fa-plus"></i> '.tr('Riga').'
                    </a>

                    <div class="btn-group tip" data-card-widget="tooltip">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                            <i class="fa fa-list"></i> '.tr('Altro').'
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">

                            <a class="dropdown-item" style="cursor:pointer" onclick="gestioneDescrizione(this)" data-title="'.tr('Aggiungi descrizione').'">
                                <i class="fa fa-plus"></i> '.tr('Descrizione').'
                            </a>

                            <a class="dropdown-item" style="cursor:pointer" onclick="gestioneSconto(this)" data-title="'.tr('Aggiungi sconto/maggiorazione').'">
                                <i class="fa fa-plus"></i> '.tr('Sconto/maggiorazione').'
                            </a>
                        </ul>
                    </div>
                </div>

                <div class="col-md-2">
                    {[ "type": "select", "label": "'.tr('Ordinamento').'", "name": "ordinamento", "class": "no-search", "value": "'.($_SESSION['module_'.$id_module]['order_row_desc'] ? 'desc' : 'manuale').'", "values": "list=\"desc\": \"'.tr('Ultima riga inserita').'\", \"manuale\": \"'.tr('Manuale').'\"" ]}
                </div>
            </div>
        </form>';
}

echo '

        <div class="clearfix"></div>
        <br>

        <div class="row">
			<div class="col-md-12" id="righe"></div>
		</div>
    </div>
</div>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

{( "name": "log_email", "id_module": "$id_module$", "id_record": "$id_record$" )}

<script type="text/javascript">
function gestioneSconto(button) {
    gestioneRiga(button, "is_sconto=1");
}

function gestioneDescrizione(button) {
    gestioneRiga(button, "is_descrizione=1");
}

async function gestioneRiga(button, options) {
    // Salvataggio via AJAX
    await salvaForm("#edit-form", {}, button);

    // Lettura titolo e chiusura tooltip
    let title = $(button).attr("data-title");

    // Apertura modal
    options = options ? options : "is_riga=1";
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

$(document).ready(function() {
    caricaRighe(null);
    
    $("#idanagrafica").change(function() {
        updateSelectOption("idanagrafica", $(this).val());
        session_set("superselect,idanagrafica", $(this).val(), 0);

        $("#idsede_destinazione").selectReset();
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

    $("#data_accettazione").on("dp.change", function() {
        if($(this).val()){
            $("#data_rifiuto").attr("disabled", true);
        }else{
            $("#data_rifiuto").attr("disabled", false);
        }
    });

    $("#data_rifiuto").on("dp.change", function() {
        if($(this).val()){
            $("#data_accettazione").attr("disabled", true);
        }else{
            $("#data_accettazione").attr("disabled", false);
        }
    });

    $("#data_accettazione").trigger("dp.change");
    $("#data_rifiuto").trigger("dp.change");

    $("#codice_cig, #codice_cup").bind("keyup change", function(e) {

        if ($("#codice_cig").val() == "" && $("#codice_cup").val() == "" ){
            $("#id_documento_fe").prop("required", false);
        }else{
            $("#id_documento_fe").prop("required", true);
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
    content_was_modified = false;
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
</script>';

if (!empty($elementi)) {
    echo '
<div class="card card-warning collapsable collapsed-card">
    <div class="card-header with-border">
        <h3 class="card-title"><i class="fa fa-warning"></i> '.tr('Documenti collegati: _NUM_', [
        '_NUM_' => count($elementi),
    ]).'</h3>
        <div class="card-tools pull-right">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <ul>';

    foreach ($elementi as $elemento) {
        $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
            '_DOC_' => $elemento['tipo_documento'],
            '_NUM_' => !empty($elemento['numero_esterno']) ? $elemento['numero_esterno'] : $elemento['numero'],
            '_DATE_' => Translator::dateToLocale($elemento['data']),
        ]);

        echo '
            <li>'.Modules::link($elemento['modulo'], $elemento['id'], $descrizione).'</li>';
    }

    echo '
        </ul>
    </div>
</div>';
}

if (!empty($elementi)) {
    echo '
<div class="alert alert-danger">
    '.tr('Eliminando questo documento si potrebbero verificare problemi nelle altre sezioni del gestionale').'.
</div>';
}

?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i id="elimina" class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<script>
$("#idsede_destinazione").change(function(){
    updateSelectOption("idsede_destinazione", $(this).val());
    $("#idreferente").selectReset();
});

input("ordinamento").on("change", function(){
    if (input(this).get() == "desc") {
        session_set("module_<?php echo $id_module; ?>,order_row_desc", 1, "").then(function () {
            caricaRighe(null);
        });
    } else {
        session_set("module_<?php echo $id_module; ?>,order_row_desc").then(function () {
            caricaRighe(null);
        });
    }
});
</script>