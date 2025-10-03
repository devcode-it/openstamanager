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
use Plugins\ReceiptFE\Interaction;
use Plugins\ReceiptFE\Ricevuta;

$list = Interaction::getReceiptList();

$directory = Ricevuta::getImportDirectory();

if (!empty($list)) {
    echo '
<table class="table table-striped table-hover table-sm table-bordered datatables">
    <thead>
        <tr>
            <th width="60%">'.tr('Nome').'</th>
            <th width="15%" class="text-center">'.tr('Data di caricamento').'</th>
            <th width="20%" class="text-center">'.tr('Azioni').'</th>
        </tr>
    </thead>
    <tbody>';

    foreach ($list as $element) {
        $name = $element['name'];
        $file = $directory.'/'.$name;

        $local = file_exists($file);
        $data_modifica = $local ? Carbon::createFromTimestamp(filemtime($file)) : null;

        echo '
        <tr>
            <td><i class="fa fa-file-text-o mr-1 text-primary"></i>'.$name.'</td>
            <td class="text-center"><i class="fa fa-calendar mr-1 text-muted"></i>'.($local ? dateFormat($data_modifica) : '-').'</td>
            <td class="text-center">
                <div class="btn-group">';

        if ($local) {
            echo '
                <button type="button" class="btn btn-danger btn-sm tip" onclick="delete_fe(this, \''.$element['id'].'\')" title="'.tr('Elimina la ricevuta').'">
                    <i class="fa fa-trash mr-1"></i>
                </button>';
        } else {
            echo '
                <button type="button" class="btn btn-info btn-sm tip" onclick="process_fe(this, \''.$name.'\')" title="'.tr('Segna la ricevuta come processata').'">
                    <i class="fa fa-upload mr-1"></i>
                </button>';
        }

        echo '
                <button type="button" class="btn btn-warning btn-sm tip" '.((!extension_loaded('openssl') and str_ends_with(strtolower((string) $element), '.p7m')) ? 'disabled' : '').' onclick="import_fe(this, \''.$name.'\')" title="'.tr('Importa la ricevuta nel gestionale').'">
                    <i class="fa fa-cloud-download mr-1"></i>
                </button>
                <button type="button" class="btn btn-primary btn-sm tip" onclick="manual_associate_fe(this, \''.$name.'\')" title="'.tr('Associa manualmente la ricevuta').'">
                    <i class="fa fa-link mr-1"></i>
                </button>
                </div>
            </td>
        </tr>';
    }

    echo '
    </tbody>
</table>';
} else {
    echo '
<div class="alert alert-warning py-2">
    <i class="fa fa-exclamation-triangle mr-2"></i>'.tr('Nessuna ricevuta da importare').'
</div>';
}

echo '
<script>
function import_fe(button, file) {
    var restore = buttonLoading(button);

    // Mostra un\'animazione di caricamento
    $("#main_loading").show();

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "get",
        data: {
            id_module: globals.id_module,
            id_plugin: '.$id_plugin.',
            op: "prepare",
            name: file,
        },
        success: function(data) {
            $("#main_loading").fadeOut();
            importMessage(data);
            buttonRestore(button, restore);
            $("#list-receiptfe").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'", function() {
                // Reinizializza le tabelle DataTables dopo il caricamento dinamico
                start_local_datatables();
            });
        },
        error: function(xhr) {
            $("#main_loading").fadeOut();
            swal("'.tr('Errore').'", xhr.responseJSON.error.message, "error");

            buttonRestore(button, restore);
        }
    });
}

function delete_fe(button, file_id) {
    swal({
        title: "'.tr('Rimuovere la ricevuta salvata localmente?').'",
        html: "'.tr('Sarà possibile inserirla nuovamente nel gestionale attraverso il caricamento').'",
        type: "error",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function (result) {
        if (result) {
            var restore = buttonLoading(button);

            // Mostra un\'animazione di caricamento
            $("#main_loading").show();

            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "get",
                data: {
                    id_module: globals.id_module,
                    id_plugin: '.$id_plugin.',
                    op: "delete",
                    file_id: file_id,
                },
                success: function(data) {
                    $("#main_loading").fadeOut();
                    $("#list-receiptfe").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'", function() {
                        buttonRestore(button, restore);
                    });
                },
                error: function(xhr) {
                    $("#main_loading").fadeOut();
                    swal("'.tr('Errore').'", xhr.responseJSON.error.message, "error");
                    buttonRestore(button, restore);
                }
            });
        }
    });
}

function process_fe(button, file) {
    swal({
        title: "'.tr('Segnare la ricevuta come processata?').'",
        html: "'.tr("Non sarà possibile individuarla nuovamente in modo automatico: l'unico modo per recuperarla sarà contattare l'assistenza").'",
        type: "info",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function (result) {
        if (result) {
            var restore = buttonLoading(button);

            // Mostra un\'animazione di caricamento
            $("#main_loading").show();

            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "get",
                data: {
                    id_module: globals.id_module,
                    id_plugin: '.$id_plugin.',
                    op: "process",
                    name: file,
                },
                success: function(data) {
                    $("#main_loading").fadeOut();
                    $("#list-receiptfe").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'", function() {
                        buttonRestore(button, restore);
                    });
                },
                error: function(xhr) {
                    $("#main_loading").fadeOut();
                    swal("'.tr('Errore').'", xhr.responseJSON.error.message, "error");
                    buttonRestore(button, restore);
                }
            });
        }
    });
}

function manual_associate_fe(button, file) {
    var restore = buttonLoading(button);

    // Mostra un\'animazione di caricamento
    $("#main_loading").show();

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "get",
        data: {
            id_module: globals.id_module,
            id_plugin: '.$id_plugin.',
            op: "prepare",
            name: file,
        },
        success: function(data) {
            $("#main_loading").fadeOut();
            var result = JSON.parse(data);
            showInvoiceSelector(file, button, restore, result);
        },
        error: function(xhr) {
            $("#main_loading").fadeOut();
            swal("'.tr('Errore').'", xhr.responseJSON.error.message, "error");
            buttonRestore(button, restore);
        }
    });
}

function showInvoiceSelector(receiptFile, originalButton, originalRestore, receiptData) {
    var receiptInfo = receiptData;

            // Poi cerca le fatture in elaborazione
            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "get",
                data: {
                    id_module: globals.id_module,
                    id_plugin: '.$id_plugin.',
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
                              ".swal2-popup .select2-container { width: 95% !important; margin: 0 auto; display: block; }" +
                              ".swal2-popup .select2-dropdown { z-index: 99999 !important; }" +
                              ".swal2-popup .select2-selection { min-height: 38px !important; }" +
                              ".swal2-popup .select2-results__option { white-space: normal !important; word-wrap: break-word !important; padding: 8px 12px !important; line-height: 1.4 !important; }" +
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
            id_module: globals.id_module,
            id_plugin: '.$id_plugin.',
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
