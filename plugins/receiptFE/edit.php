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

use Carbon\Carbon;
use Modules\Fatture\Fattura;
use Plugins\ReceiptFE\Interaction;
use Util\XML;

echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle mr-2"></i>'.tr('Le ricevute delle Fatture Elettroniche permettono di individuare se una determinata fattura trasmessa è stata accettata dal Sistema Di Interscambio').'
</div>';

if (Interaction::isEnabled()) {
    echo '
<div class="alert alert-light border">
    <i class="fa fa-refresh mr-2"></i>'.tr('Tramite il pulsante _BTN_ è possibile procedere al recupero delle ricevute, aggiornando automaticamente lo stato delle relative fatture e allegandole ad esse', [
        '_BTN_' => '<b>'.tr('Ricerca ricevute').'</b>',
    ]).'
</div>';
}

// Messaggio informativo su fatture con stato di errore
$data_setting = Carbon::createFromFormat('d/m/Y', setting('Data inizio controlli su stati FE'))->format('Y-m-d');

$fatture_generate_errore = Fattura::vendita()
    ->whereIn('codice_stato_fe', ['NS', 'ERR', 'EC02'])
    ->where('data_stato_fe', '>=', $_SESSION['period_start'])
    ->where('data', '>=', $data_setting)
    ->orderBy('data', 'DESC')
    ->get();

if (!empty($fatture_generate_errore->count())) {
    echo '
        <div class="alert alert-warning push alert-dismissible" role="alert">
            <button class="close" type="button" data-dismiss="alert" aria-hidden="true"><span aria-hidden="true">×</span><span class="sr-only">'.tr('Chiudi').'</span></button>
            <h4><i class="fa fa-warning mr-2"></i>'.tr('Attenzione').'</h4>'.(($fatture_generate_errore->count() > 1) ? tr('Le seguenti fatture hanno ricevuto uno scarto o presentano errori in fase di trasmissione') : tr('La seguente fattura ha ricevuto uno scarto o presenta errori in fase di trasmissione')).':
            <ul class="fa-ul list-unstyled mb-2">';

    foreach ($fatture_generate_errore as $fattura_generata) {
        // Mostra nome stato FE con icona
        $stato_fe = $fattura_generata->statoFE;
        $descrizione = $stato_fe ? '<i class="'.$stato_fe->icon.'"></i> '.$stato_fe->name : $fattura_generata['codice_stato_fe'];
        $tooltip = '';
        $stato_fattura = $fattura_generata->stato; // stato documento (icona/nome)

        $ricevuta_principale = $fattura_generata->getRicevutaPrincipale();
        if (!empty($ricevuta_principale)) {
            $contenuto_ricevuta = XML::readFile(base_dir().'/files/fatture/vendite/'.$ricevuta_principale->filename);
            $data_ts = timestampFormat($fattura_generata['data_stato_fe']);

            // Informazioni aggiuntive per EC02 (per tooltip)
            if (!empty($contenuto_ricevuta['EsitoCommittente'])) {
                $ec02_desc = trim((string) $contenuto_ricevuta['EsitoCommittente']['Descrizione']);
                if ($ec02_desc !== '') {
                    $tipText = 'Stato FE: '.$fattura_generata['codice_stato_fe']."\n".
                               'Descrizione: '.$ec02_desc."\n".
                               'Data: '.$data_ts;
                    $tooltip = ' <span class="tip ml-1" title="'.htmlentities($tipText).'"><i class="fa fa-question-circle-o"></i></span>';
                }
            }

            // Informazioni aggiuntive per NS (per tooltip)
            $lista_errori = $contenuto_ricevuta['ListaErrori'];
            if ($lista_errori) {
                $lista_errori = $lista_errori[0] ? $lista_errori : [$lista_errori];

                $errore = $lista_errori[0]['Errore'];
                $codice = (string) ($errore['Codice'] ?? '');
                $desc = trim((string) ($errore['Descrizione'] ?? ''));
                if ($codice !== '' || $desc !== '') {
                    $tipText = 'Stato FE: '.$fattura_generata['codice_stato_fe']."\n";
                    if ($codice !== '') {
                        $tipText .= 'Codice errore: '.$codice."\n";
                    }
                    if ($desc !== '') {
                        $tipText .= 'Descrizione: '.$desc."\n";
                    }
                    $tipText .= 'Data: '.$data_ts;
                    $tooltip = ' <span class="tip ml-1" title="'.htmlentities($tipText).'"><i class="fa fa-question-circle-o"></i></span>';
                }
            }
        }

        // Testo descrittivo + link "Apri" + icona stato documento
        $icon_title = '';
        if ($stato_fattura) {
            $icon_title = $stato_fattura->getTranslation('title') ?: ($stato_fattura->name ?? '');
        }
        $icon_li = $stato_fattura
            ? '<span class="tip" title="'.htmlentities($icon_title).'"><span class="fa-li"><i class="'.$stato_fattura->icona.'"></i></span></span>'
            : '<span class="fa-li"><i class="fa fa-file-text-o"></i></span>';

        echo '<li class="mb-1">'.$icon_li.'<b>'.$fattura_generata->getReference().'</b> <span class="ml-2">'.Modules::link('Fatture di vendita', $fattura_generata->id, tr('Apri')).'</span> ['.$descrizione.']'.$tooltip.'</li>';
    }

    echo '
            </ul>
        </div>';
}

// Controllo se ci sono fatture in elaborazione da più di 7 giorni per le quali non ho ancora una ricevuta
$data_limite = (new Carbon())->subDays(7);
$fatture_generate = Fattura::vendita()
    ->where('codice_stato_fe', 'WAIT')
    ->where('data_stato_fe', '>=', $_SESSION['period_start'])
    ->where('data_stato_fe', '<', $data_limite)
    ->where('data', '>=', $data_setting)
    ->orderBy('data_stato_fe')
    ->get();

if (!empty($fatture_generate->count())) {
    echo '
    <div class="alert alert-info push info-dismissible" role="alert"><button class="close" type="button" data-dismiss="alert" aria-hidden="true"><span aria-hidden="true">×</span><span class="sr-only">'.tr('Chiudi').'</span></button>
        <h4><i class="fa fa-info mr-2"></i>'.tr('Informazione').'</h4> '.(($fatture_generate->count() > 1) ? tr('Le seguenti fatture sono in attesa di una ricevuta da più di 7 giorni') : tr('La seguente fattura è in attesa di una ricevuta da più di 7 giorni')).':
        <ul>';

    foreach ($fatture_generate as $fattura_generata) {
        echo '<li>'.reference($fattura_generata, $fattura_generata->getReference()).' ['.timestampFormat($fattura_generata['data_stato_fe']).']</li>';
    }

    echo '
        </ul>
    </div>';
}
echo '
<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fa fa-file-text-o mr-2"></i>'.tr('Carica un XML').'

            <span class="tip ml-1" title="'.tr('Formati supportati: XML e P7M').'.">
                <i class="fa fa-question-circle-o"></i>
            </span>

        </h3>
    </div>
    <div class="card-body" id="upload">
        <div class="row">
            <div class="col-md-9">
                {[ "type": "file", "name": "blob", "required": 1, "placeholder": "'.tr('Seleziona un file XML').'" ]}
            </div>

            <div class="col-md-3 align-items-end">
                <div class="btn-group btn-block">
                    <button type="button" class="btn btn-primary" onclick="upload(this)">
                        <i class="fa fa-upload mr-1"></i> '.tr('Carica ricevuta').'
                    </button>
                    <button type="button" class="btn btn-info" onclick="manual_upload(this)" title="'.tr('Carica e associa manualmente').'">
                        <i class="fa fa-link"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>';

echo '
<div class="card card-outline card-info mt-3">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fa fa-file-text-o mr-2"></i>'.tr('Ricevute da importare').'
        </h3>';

// Ricerca automatica
if (Interaction::isEnabled()) {
    echo '
        <div class="card-tools">
            <div class="input-group input-group-sm mr-2 d-inline-flex" style="width: 250px;">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                </div>
                <input type="text" class="form-control" id="search_receipt" placeholder="'.tr('Cerca ricevuta').'">
            </div>

            <div class="btn-group btn-group-sm d-inline-flex">
                <button type="button" class="btn btn-warning" onclick="importAllReceipt(this)">
                    <i class="fa fa-cloud-download mr-1"></i> '.tr('Importa tutte').'
                </button>

                <button type="button" class="btn btn-primary" onclick="searchReceipts(this)">
                    <i class="fa fa-refresh mr-1"></i> '.tr('Ricerca ricevute').'
                </button>
            </div>
        </div>';
}

echo '
    </div>
    <div class="card-body" id="list-receiptfe">';

if (Interaction::isEnabled()) {
    echo '
        <div class="alert alert-info">
            <i class="fa fa-info-circle mr-2"></i>'.tr('Per vedere le ricevute da importare utilizza il pulsante _BUTTON_', [
        '_BUTTON_' => '<b>'.tr('Ricerca ricevute').'</b>',
    ]).'
        </div>';
} else {
    include $structure->filepath('list.php');
}

echo '

    </div>
</div>';

echo '
<script>
$(document).ready(function() {
    // Search functionality for receipt list
    $("#search_receipt").on("keyup", function() {
        applySearchFilter();
    });

    // Personalizzazione animazione di caricamento
    $.fn.dataTable.ext.pager.numbers_length = 5;
    $.extend(true, $.fn.dataTable.defaults, {
        language: {
            processing: "<div class=\"text-center\"><i class=\"fa fa-refresh fa-spin fa-2x fa-fw text-primary\"></i><div class=\"mt-2\">"+globals.translations.processing+"</div></div>"
        }
    });
});

function applySearchFilter() {
    var searchValue = $("#search_receipt").val().toLowerCase();

    // Mostra tutte le righe se il campo di ricerca è vuoto
    if(searchValue.length === 0) {
        $("#list-receiptfe table tbody tr").show();
        return;
    }

    // Aggiungi un effetto di evidenziazione durante la ricerca
    $("#list-receiptfe table tbody tr").each(function() {
        var rowText = $(this).text().toLowerCase();
        var match = rowText.indexOf(searchValue) > -1;

        if(match) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });

    // Mostra un messaggio se non ci sono risultati
    if($("#list-receiptfe table tbody tr:visible").length === 0) {
        if($("#list-receiptfe #no-results-message").length === 0) {
            $("#list-receiptfe table tbody").append("<tr id=\"no-results-message\"><td colspan=\"3\" class=\"text-center text-muted py-3\"><i class=\"fa fa-search mr-2\"></i>"+globals.translations.no_results_found+" \"" + searchValue + "\"</td></tr>");
        } else {
            $("#list-receiptfe #no-results-message td").html("<i class=\"fa fa-search mr-2\"></i>"+globals.translations.no_results_found+" \"" + searchValue + "\"");
        }
    } else {
        $("#list-receiptfe #no-results-message").remove();
    }
}

function searchReceipts(button) {
    var restore = buttonLoading(button);

    // Mostra un\'animazione di caricamento nella lista
    $("#list-receiptfe").html("<div class=\"text-center py-5\"><i class=\"fa fa-refresh fa-spin fa-3x fa-fw text-primary\"></i><div class=\"mt-3\">"+globals.translations.searching+"</div></div>");

    // Carica la lista delle ricevute con parametro per forzare refresh cache
    $("#list-receiptfe").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&refresh_cache=" + Date.now(), function() {
        buttonRestore(button, restore);

        // Reinizializza le tabelle DataTables dopo il caricamento dinamico
        start_local_datatables();

        // Applica il filtro di ricerca se presente
        applySearchFilter();

        // Inizializza i tooltip
        $(".tip").tooltip();
    });
}

function manual_upload(btn) {
    if ($("#blob").val()) {
        var restore = buttonLoading(btn);

        // Mostra un\'animazione di caricamento
        $("#main_loading").show();

        $("#upload").ajaxSubmit({
            url: globals.rootdir + "/actions.php",
            data: {
                op: "save",
                id_module: "'.$id_module.'",
                id_plugin: "'.$id_plugin.'",
            },
            type: "post",
            success: function(data){
                $("#main_loading").fadeOut();
                var result = JSON.parse(data);

                // Mostra sempre il selettore per l\'associazione manuale
                showInvoiceSelector(result.file, btn, restore, result);
            },
            error: function(xhr) {
                $("#main_loading").fadeOut();
                swal("'.tr('Errore').'", xhr.responseJSON.error.message, "error");

                buttonRestore(btn, restore);
            }
        });
    } else {
        swal("'.tr('Attenzione').'", "'.tr('Seleziona un file da caricare').'", "warning");
    }
}

function upload(btn) {
    if ($("#blob").val()) {
        var restore = buttonLoading(btn);

        // Mostra un\'animazione di caricamento
        $("#main_loading").show();

        $("#upload").ajaxSubmit({
            url: globals.rootdir + "/actions.php",
            data: {
                op: "save",
                id_module: "'.$id_module.'",
                id_plugin: "'.$id_plugin.'",
            },
            type: "post",
            success: function(data){
                $("#main_loading").fadeOut();
                importMessage(data);
                buttonRestore(btn, restore);
            },
            error: function(xhr) {
                $("#main_loading").fadeOut();
                swal("'.tr('Errore').'", xhr.responseJSON.error.message, "error");

                buttonRestore(btn, restore);
            }
        });
    } else {
        swal({
            title: "'.tr('Selezionare un file!').'",
            type: "error",
        })
    }
}

function importMessage(data) {
    data = JSON.parse(data);

    var ricevuta = "<br><h5>'.tr('Ricevuta').': " + data.file+ "</h5>";

    if(data.fattura) {
        data_fattura = new Date(data.fattura.data);
        data_fattura = data_fattura.toLocaleDateString("it-IT", {
            year: "numeric",
            month: "2-digit",
            day: "2-digit"
            }).replace(",", "/");
        swal({
            title: "'.tr('Importazione completata!').'",
            html: "'.tr('Fattura aggiornata correttamente').': <h4>" + data.fattura.numero_esterno + " '.tr('del').' " + data_fattura + "</h4>" + ricevuta,
            type: "success",
        });
    } else {
        swal({
            title: "'.tr('Importazione fallita!').'",
            html: "<i>'.tr('Fattura relativa alla ricevuta non rilevata. Controlla che esista una fattura di vendita corrispondente caricata a gestionale.').'</i>" + ricevuta,
            type: "error",
        });
    }
}

function importAllReceipt(btn) {
    swal({
        title: "'.tr('Importare tutte le ricevute?').'",
        html: "'.tr('Importando le ricevute, verranno aggiornati gli stati di invio delle fatture elettroniche. Continuare?').'",
        showCancelButton: true,
        confirmButtonText: "'.tr('Procedi').'",
        type: "info",
    }).then(function (result) {
        if (result) {
            var restore = buttonLoading(btn);
            $("#main_loading").show();

            $.ajax({
                url: globals.rootdir + "/actions.php",
                data: {
                    op: "import",
                    id_module: "'.$id_module.'",
                    id_plugin: "'.$id_plugin.'",
                },
                type: "post",
                success: function(data){
                    data = JSON.parse(data);

                    if(data.length == 0){
                        var html = "'.tr('Non sono state trovate ricevute da importare').'.";
                    } else {
                        var html = "'.tr('Sono state elaborate le seguenti ricevute:').'";
                        var ricevute_non_associate = [];

                        data.forEach(function(element) {
                            var text = "";
                            if(element.fattura) {
                                text += element.fattura;
                            } else {
                                text += "<i>'.tr('Fattura relativa alla ricevuta non rilevata').'</i>";
                                ricevute_non_associate.push(element.file);
                            }

                            text += " (" + element.file + ")";

                            html += "<small><li>" + text + "</li></small>";
                        });

                        if (ricevute_non_associate.length > 0) {
                            html += "<br><div class=\"alert alert-warning\"><strong>'.tr('Attenzione').':</strong> '.tr('Le seguenti ricevute non sono state associate automaticamente').':<br>";
                            ricevute_non_associate.forEach(function(file) {
                                html += "<small>- " + file + "</small><br>";
                            });
                            html += "'.tr('Puoi associarle manualmente utilizzando il pulsante di importazione per ogni singola ricevuta').'</div>";
                        }

                        html += "<br><small>'.tr("Se si sono verificati degli errori durante la procedura e il problema continua a verificarsi, contatta l'assistenza ufficiale").'</small>";
                    }

                    $("#list-receiptfe").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'", function() {
                        swal({
                            title: "'.tr('Operazione completata!').'",
                            html: html,
                            type: "info",
                        });

                        buttonRestore(btn, restore);
                        $("#main_loading").fadeOut();
                    });

                },
                error: function(data) {
                    $("#main_loading").fadeOut();
                    swal("'.tr('Errore').'", data, "error");

                    buttonRestore(btn, restore);
                }
            });
        }
    });
}

function showInvoiceSelector(receiptFile, originalButton, originalRestore, receiptData) {
    // Se receiptData non è fornito, ottieni le informazioni sulla ricevuta
    if (!receiptData) {
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "get",
            data: {
                id_module: "'.$id_module.'",
                id_plugin: "'.$id_plugin.'",
                op: "get_receipt_info",
                name: receiptFile,
            },
            success: function(data) {
                var receiptInfo = JSON.parse(data);
                showInvoiceSelectorInternal(receiptFile, originalButton, originalRestore, receiptInfo);
            },
            error: function(xhr) {
                swal("'.tr('Errore').'", "'.tr('Errore nel caricamento delle informazioni della ricevuta').'", "error");
                buttonRestore(originalButton, originalRestore);
            }
        });
    } else {
        showInvoiceSelectorInternal(receiptFile, originalButton, originalRestore, receiptData);
    }
}

function showInvoiceSelectorInternal(receiptFile, originalButton, originalRestore, receiptInfo) {

            // Poi cerca le fatture in elaborazione
            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "get",
                data: {
                    id_module: "'.$id_module.'",
                    id_plugin: "'.$id_plugin.'",
                    op: "search_fatture_elaborazione",
                },
                success: function(data) {
                    var fatture = JSON.parse(data);

                    if (fatture.length === 0) {
                        swal({
                            title: "'.tr('Nessuna fattura trovata').'",
                            html: "'.tr('Non sono state trovate fatture con stato \"In elaborazione\" da associare alla ricevuta').' <strong>" + receiptFile + "</strong>",
                            type: "warning",
                        });
                        buttonRestore(originalButton, originalRestore);
                        return;
                    }

                    // Crea le opzioni per il select
                    var options = "";
                    var hasMatchingProgressivo = false;

                    fatture.forEach(function(fattura) {
                        var isMatching = (fattura.progressivo_invio === receiptInfo.progressivo_invio && receiptInfo.progressivo_invio);
                        var selected = isMatching ? "selected" : "";
                        if (isMatching) hasMatchingProgressivo = true;

                        var dataFormatted = new Date(fattura.data).toLocaleDateString("it-IT");
                        var totaleFormatted = parseFloat(fattura.totale).toLocaleString("it-IT", {
                            style: "currency",
                            currency: "EUR"
                        });

                        var anagraficaTruncated = fattura.anagrafica.length > 25 ?
                            fattura.anagrafica.substring(0, 25) + "..." : fattura.anagrafica;

                        var optionText = fattura.numero_esterno + " - " + dataFormatted + " - " + anagraficaTruncated + " - " + totaleFormatted;

                        if (fattura.progressivo_invio && fattura.progressivo_invio !== null && fattura.progressivo_invio !== "") {
                            var progressivoShort = fattura.progressivo_invio.toString().slice(-5);
                            optionText += " (" + progressivoShort + ")";
                        }

                        if (isMatching) {
                            optionText = "★ " + optionText + " ★";
                            options += "<option value=\"" + fattura.id + "\" " + selected + " class=\"matching\">" + optionText + "</option>";
                        } else {
                            options += "<option value=\"" + fattura.id + "\" " + selected + ">" + optionText + "</option>";
                        }
                    });

                    swal({
                        title: "'.tr('Seleziona fattura da associare').'",
                        html: "<style>" +
                              "#swal-fattura-select { width: 95% !important; margin: 0 auto; display: block; max-height: 200px; overflow-y: auto; }" +
                              "#swal-fattura-select option { white-space: normal !important; word-wrap: break-word !important; padding: 8px 4px !important; line-height: 1.3 !important; height: auto !important; }" +
                              ".swal2-popup { word-wrap: break-word !important; }" +
                              "</style>" +
                              "<div style=\"background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; margin-bottom: 20px;\">" +
                              "<div style=\"display: flex; justify-content: space-between; margin-bottom: 8px;\"><span style=\"font-weight: 600; color: #495057; font-size: 13px;\">'.tr('Ricevuta').':</span><span style=\"color: #212529; font-size: 13px; font-weight: 500;\">" + receiptFile + "</span></div>" +
                              "<div style=\"display: flex; justify-content: space-between; margin-bottom: 8px;\"><span style=\"font-weight: 600; color: #495057; font-size: 13px;\">'.tr('Progressivo invio').':</span><span style=\"color: #212529; font-size: 13px; font-weight: 500;\">" + (receiptInfo.progressivo_invio || "'.tr('Non disponibile').'") + "</span></div>" +
                              "</div>" +
                              "<div style=\"margin: 15px 0;\">" +
                              "<label style=\"display: block; margin-bottom: 8px; font-weight: 600; color: #495057; font-size: 14px;\">'.tr('Seleziona la fattura da associare').':</label>" +
                              "<select id=\"swal-fattura-select\" class=\"form-control\">" +
                              "<option value=\"\">'.tr('Seleziona una fattura').'...</option>" +
                              options +
                              "</select>" +
                              "<div style=\"font-size: 12px; color: #6c757d; margin-top: 8px; font-style: italic; text-align: center;\">" +
                              (hasMatchingProgressivo ? "'.tr('★ Fattura con progressivo corrispondente trovata e preselezionata').' ★" : "'.tr('Nessuna fattura con progressivo corrispondente trovata. Seleziona manualmente.').'") +
                              "</div>" +
                              "</div>",
                        showCancelButton: true,
                        confirmButtonText: "'.tr('Associa').'",
                        cancelButtonText: "'.tr('Annulla').'",
                        width: 600
                    }).then(function(result) {
                        if (result) {
                            var selectedFattura = $("#swal-fattura-select").val();
                            if (selectedFattura && selectedFattura !== "") {
                                associateReceiptToInvoice(receiptFile, selectedFattura, originalButton, originalRestore);
                            } else {
                                swal("'.tr('Errore').'", "'.tr('Seleziona una fattura').'", "error");
                                buttonRestore(originalButton, originalRestore);
                            }
                        } else {
                            buttonRestore(originalButton, originalRestore);
                        }
                    }).catch(function() {
                        buttonRestore(originalButton, originalRestore);
                    });
                },
                error: function(xhr) {
                    swal("'.tr('Errore').'", "'.tr('Errore nel caricamento delle fatture').'", "error");
                    buttonRestore(originalButton, originalRestore);
                }
            });
}

function associateReceiptToInvoice(receiptFile, fatturaId, originalButton, originalRestore) {
    $("#main_loading").show();

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "get",
        data: {
            id_module: "'.$id_module.'",
            id_plugin: "'.$id_plugin.'",
            op: "associa_ricevuta_fattura",
            name: receiptFile,
            id_fattura: fatturaId,
        },
        success: function(data) {
            $("#main_loading").fadeOut();

            try {
                var result = JSON.parse(data);

                if (result.success) {
                // Mostra messaggio di successo
                if(result.fattura) {
                    var data_fattura = new Date(result.fattura.data);
                    data_fattura = data_fattura.toLocaleDateString("it-IT", {
                        year: "numeric",
                        month: "2-digit",
                        day: "2-digit"
                    });

                    swal({
                        title: "'.tr('Associazione completata!').'",
                        html: "'.tr('Ricevuta associata correttamente alla fattura').': <h4>" + result.fattura.numero_esterno + " '.tr('del').' " + data_fattura + "</h4><br><h5>'.tr('Ricevuta').': " + result.file + "</h5>",
                        type: "success",
                    });
                } else {
                    swal({
                        title: "'.tr('Associazione completata!').'",
                        html: result.message + "<br><h5>'.tr('Ricevuta').': " + result.file + "</h5>",
                        type: "success",
                    });
                }

                // Ricarica la lista
                $("#list-receiptfe").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'", function() {
                    start_local_datatables();
                });
            } else {
                swal("'.tr('Errore').'", result.message || "'.tr('Errore sconosciuto').'", "error");
            }
        } catch (e) {
            swal("'.tr('Errore').'", "'.tr('Errore nel parsing della risposta del server').'", "error");
        }

        buttonRestore(originalButton, originalRestore);
        },
        error: function(xhr) {
            $("#main_loading").fadeOut();
            swal("'.tr('Errore').'", "'.tr('Errore durante l\'associazione').'", "error");
            buttonRestore(originalButton, originalRestore);
        }
    });
}
</script>';
