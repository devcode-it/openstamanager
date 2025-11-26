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

use Plugins\ImportFE\Interaction;

if (!empty($record)) {
    include $structure->filepath('generate.php');

    return;
}

echo '
<script>
    function upload(btn) {
        if ($("#blob").val()) {
            swal({
                title: "'.tr('Avviare la procedura?').'",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "'.tr('Sì').'"
            }).then(function (result) {
                var restore = buttonLoading(btn);

                $("#upload").ajaxSubmit({
                    url: globals.rootdir + "/actions.php",
                    data: {
                        op: "save",
                        id_module: "'.$id_module.'",
                        id_plugin: "'.$id_plugin.'",
                    },
                    type: "post",
                    success: function(data){
                        data = JSON.parse(data);

                        if (!data.already) {
                            redirect_url(globals.rootdir + "/editor.php?id_module=" + globals.id_module + "&id_plugin=" + '.$id_plugin.' + "&id_record=" + data.id);
                        } else {
                            swal({
                                title: "'.tr('Fattura già importata').'.",
                                type: "info",
                            });

							$("#blob").val("");
                        }

						buttonRestore(btn, restore);
                    },
                    error: function(xhr) {
                        swal("'.tr('Errore').'", xhr.responseJSON.error.message, "error");

                        buttonRestore(btn, restore);
                    }
                });
            })
        } else {
            swal({
                title: "'.tr('Selezionare un file!').'",
                type: "error",
            })
        }
    }
</script>

<div class="card card-success">
    <div class="card-header with-border">
        <h3 class="card-title">
            '.tr('Carica un XML').'

            <span class="tip" title="'.tr('Formati supportati: XML, P7M e ZIP').'.">
                <i class="fa fa-question-circle-o"></i>
            </span>

        </h3>
    </div>
    <div class="card-body" id="upload">
        <div class="row">
            <div class="col-md-9">
                {[ "type": "file", "name": "blob", "required": 1, "placeholder": "'.tr('Seleziona un file XML, P7M o ZIP').'" ]}
            </div>

            <div class="col-md-3">
                <button type="button" class="btn btn-primary pull-right" onclick="upload(this)">
                    <i class="fa fa-upload"></i> '.tr('Carica documento fornitore').'
                </button>
            </div>
        </div>
    </div>
</div>';

echo '
<div class="card card-info">
    <div class="card-header with-border">
        <h3 class="card-title">
            '.tr('Fatture da importare').'</span>
        </h3>

        <div class="card-tools">
            <div class="input-group input-group-sm mr-2 d-inline-flex" style="width: 250px;">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                </div>
                <input type="text" class="form-control" id="search_invoice" placeholder="'.tr('Cerca fattura').'">
            </div>

            <div class="btn-group btn-group-sm d-inline-flex">
                <button type="button" class="btn btn-warning" onclick="importAll(this)">
                    <i class="fa fa-cloud-download"></i> '.tr('Importa in sequenza').'
                </button>';

// Ricerca automatica
if (Interaction::isEnabled()) {
    echo '
            <button type="button" class="btn btn-primary" onclick="searchInvoices(this)">
                <i class="fa fa-refresh"></i> '.tr('Ricerca fatture di acquisto').'
            </button>';
}

echo '
            </div>
        </div>
    </div>
    <div class="card-body" id="list">';

if (Interaction::isEnabled()) {
    echo '
        <div class="alert alert-info">
            <i class="fa fa-info-circle mr-2"></i>'.tr('Per vedere le fatture da importare utilizza il pulsante _BUTTON_', [
        '_BUTTON_' => '<b>"'.tr('Ricerca fatture').'"</b>',
    ]).'
        </div>';
} else {
    include $structure->filepath('list.php');
}

echo '

    </div>
</div>

<script>
$(document).ready(function() {
    $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
    $($.fn.dataTable.tables(true)).DataTable().scroller.measure();

    // Search functionality for invoice list
    $("#search_invoice").on("keyup", function() {
        applySearchFilter();
    });

    // Personalizzazione animazione di caricamento
    $.fn.dataTable.ext.pager.numbers_length = 5;
    $.extend(true, $.fn.dataTable.defaults, {
        language: {
            processing: "<div class=\"text-center\"><i class=\"fa fa-refresh fa-spin fa-2x fa-fw text-primary\"></i><div class=\"mt-2\">"+globals.translations.processing+"</div></div>"
        }
    });
});';

if (Interaction::isEnabled()) {
    echo '
function importAll(btn) {
    swal({
        title: "'.tr('Importare tutte le fatture in sequenza?').'",
        html: "'.tr('Verranno importate manualmente tutte le fatture presenti. Continuare?').'",
        showCancelButton: true,
        confirmButtonText: "'.tr('Procedi').'",
        type: "info",
    }).then(function (result) {
        var restore = buttonLoading(btn);
        $("#main_loading").show();

        $.ajax({
            url: globals.rootdir + "/actions.php",
            data: {
                op: "list",
                id_module: "'.$id_module.'",
                id_plugin: "'.$id_plugin.'",
            },
            type: "post",
            success: function(data){
                data = JSON.parse(data);

                count = data.length;
                counter = 0;
                data.forEach(function(element) {
                    $.ajax({
                        url: globals.rootdir + "/actions.php",
                        type: "get",
                        data: {
                            id_module: "'.$id_module.'",
                            id_plugin: "'.$id_plugin.'",
                            op: "prepare",
                            name: element.name,
                        },
                        success: function(data) {
                            counter ++;

                            importComplete(count, counter, btn, restore);
                        },
                        error: function(data) {
                            counter ++;

                            importComplete(count, counter, btn, restore);
                        }
                    });
                });

                importComplete(count, counter, btn, restore);
            },
            error: function(data) {
                alert("'.tr('Errore').': " + data);

				$("#main_loading").fadeOut();
                buttonRestore(btn, restore);
            }
        });
    });
}

function importComplete(count, counter, btn, restore) {
    if(counter == count){
        $("#main_loading").fadeOut();
        buttonRestore(btn, restore);

        redirect_url(globals.rootdir + "/editor.php?id_module=" + globals.id_module + "&id_plugin=" + '.$id_plugin.' + "&id_record=1&sequence=1");
    }
}';
} else {
    echo '
function importAll(btn) {
    redirect_url(globals.rootdir + "/editor.php?id_module=" + globals.id_module + "&id_plugin=" + '.$id_plugin.' + "&id_record=1&sequence=1");
}';
}
echo '

function searchInvoices(btn) {
    var restore = buttonLoading(btn);

    // Mostra un\'animazione di caricamento nella lista
    $("#list").html("<div class=\"text-center py-5\"><i class=\"fa fa-refresh fa-spin fa-3x fa-fw text-primary\"></i><div class=\"mt-3\">"+globals.translations.searching+"</div></div>");

    // Carica la lista delle fatture
    $("#list").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'", function() {
        buttonRestore(btn, restore);

        // Reinizializza le tabelle DataTables dopo il caricamento dinamico
        start_local_datatables();

        // Applica il filtro di ricerca se presente
        applySearchFilter();

        // Inizializza i tooltip
        $(".tip").tooltip();
    });
}

function applySearchFilter() {
    var searchValue = $("#search_invoice").val().toLowerCase();

    // Mostra tutte le righe se il campo di ricerca è vuoto
    if(searchValue.length === 0) {
        $("table tbody tr").show();
        return;
    }

    // Aggiungi un effetto di evidenziazione durante la ricerca
    $("table tbody tr").each(function() {
        var rowText = $(this).text().toLowerCase();
        var match = rowText.indexOf(searchValue) > -1;

        if(match) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });

    // Mostra un messaggio se non ci sono risultati
    if($("table tbody tr:visible").length === 0) {
        if($("#no-results-message").length === 0) {
            $("table tbody").append("<tr id=\"no-results-message\"><td colspan=\"5\" class=\"text-center text-muted py-3\"><i class=\"fa fa-search mr-2\"></i>"+globals.translations.no_results_found+" \"" + searchValue + "\"</td></tr>");
        } else {
            $("#no-results-message td").html("<i class=\"fa fa-search mr-2\"></i>"+globals.translations.no_results_found+" \"" + searchValue + "\"");
        }
    } else {
        $("#no-results-message").remove();
    }
}

</script>';
