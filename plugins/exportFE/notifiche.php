<?php

use Modules\Fatture\Fattura;
use Modules\Fatture\StatoFE;
use Plugins\ExportFE\Interaction;

include_once __DIR__.'/../../core.php';

$result = Interaction::getInvoiceRecepits($id_record);
$recepits = $result['results'];

$documento = Fattura::find($id_record);

if (empty($recepits)) {
    echo '
<div class="alert alert-warning py-2">
    <i class="fa fa-exclamation-triangle mr-2"></i>'.tr('Il documento non ha notifiche disponibili').'
</div>

<div class="alert alert-light border">
    <i class="fa fa-info-circle mr-2"></i>'.tr("Nota: se la fattura in questione è stata inviata molto tempo fa, il servizio utilizzato non ha reso disponibile l'associazione diretta tra la fattura e le notifiche").'. '.tr("L'importazione delle notifiche in questione procedere comunque regolarmente").'
</div>';

    return;
}

echo '
<div class="row">
    <div class="col-md-8">
        <p>'.tr("Segue l'elenco completo delle notifiche/ricevute relative alla fatture elettronica di questo documento").'.</p>
        <p>'.tr('La procedura di importazione prevede di impostare in modo autonomo la notifica più recente come principale, ma si verificano alcune situazioni in cui il comportamento richiesto deve essere distinto').'. '.tr('Qui si può procedere a scaricare una specifica notifica e a impostarla manualmente come principale per il documento').'.</p>
        <p class="text-small">'.tr('Nota: in caso di fattura scartata per duplicazione, se non sono disponibili notifiche contattare i fornitori del servizio').'.</p>
    </div>
    <div class="col-md-4">
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa fa-search"></i></span>
            </div>
            <input type="text" class="form-control" id="search_receipt" placeholder="'.tr('Cerca ricevuta').'">
        </div>
    </div>
</div>

<table class="table table-striped table-hover table-sm table-bordered">
    <thead>
        <tr>
            <th>'.tr('Nome').'</th>
            <th class="text-center">'.tr('Scaricata').'</th>
            <th width="20%" class="text-center">'.tr('Opzioni').'</th>
        </tr>
    </thead>

    <tbody>';

foreach ($recepits as $nome) {
    $upload = $documento->uploads()
        ->where('original_name', $nome)
        ->first();

    // Individuazione codice ricevuta
    $filename = explode('.', (string) $nome)[0];
    $pieces = explode('_', $filename);
    $codice_stato = $pieces[2];

    // Informazioni sullo stato indicato
    $stato_fe = StatoFE::find($codice_stato)->id_record;

    echo '
        <tr data-name="'.$nome.'">
            <td><i class="fa fa-file-text-o mr-1 text-primary"></i>'.$nome.'</td>

            <td class="text-center">';

    if (empty($upload)) {
        echo tr('No');
    } else {
        echo '
            <a href="'.ROOTDIR.'/view.php?file_id='.$upload->id.'" target="_blank">
                <i class="fa fa-external-link mr-1"></i> '.tr('Visualizza').'
            </a>';
    }

    echo '
            <td class="text-center">
                <div class="btn-group">';

    if (empty($upload)) {
        echo '
                <button type="button" class="btn btn-info btn-sm tip" onclick="scaricaRicevuta(this)" title="'.tr('Scarica la ricevuta e la imposta come principale aggiornando lo stato FE del documento').'">
                    <i class="fa fa-download mr-1"></i>
                </button>';
    }

    if (empty($upload) || $upload->id != $documento->id_ricevuta_principale) {
        echo '
                <button type="button" class="btn btn-warning btn-sm tip" onclick="impostaRicevuta(this)" title="'.tr('Imposta la ricevuta come principale aggiornando lo stato FE del documento').'">
                    <i class="fa fa-check-circle mr-1"></i>
                </button>';
    } elseif ($upload->id == $documento->id_ricevuta_principale) {
        echo '
                <button type="button" class="btn btn-success btn-sm disabled">
                    <i class="fa fa-check-circle mr-1"></i>
                </button>';
    }

    echo '
                </div>
            </td>
        </tr>';
}

echo '
    </tbody>
</table>

<script>
$(document).ready(function() {
    // Search functionality for receipt list
    $("#search_receipt").on("keyup", function() {
        applySearchFilter();
    });
});

function applySearchFilter() {
    var searchValue = $("#search_receipt").val().toLowerCase();

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
            $("table tbody").append("<tr id=\"no-results-message\"><td colspan=\"3\" class=\"text-center text-muted py-3\"><i class=\"fa fa-search mr-2\"></i>"+globals.translations.no_results_found+" \"" + searchValue + "\"</td></tr>");
        } else {
            $("#no-results-message td").html("<i class=\"fa fa-search mr-2\"></i>"+globals.translations.no_results_found+" \"" + searchValue + "\"");
        }
    } else {
        $("#no-results-message").remove();
    }
}

function scaricaRicevuta(button) {
    let riga = $(button).closest("tr");
    let name = riga.data("name");

    gestioneRicevuta(button, name, "download");
}

function impostaRicevuta(button) {
    let riga = $(button).closest("tr");
    let name = riga.data("name");

    gestioneRicevuta(button, name, "imposta");
}

function gestioneRicevuta(button, name, type) {
    let restore = buttonLoading(button);

    // Mostra un\'animazione di caricamento
    $("#main_loading").show();

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "post",
        dataType: "json",
        data: {
            op: "gestione_ricevuta",
            id_module: "'.$id_module.'",
            id_plugin: "'.$id_plugin.'",
            id_record: "'.$id_record.'",
            name: name,
            type: type,
        },
        success: function(response) {
            $("#main_loading").fadeOut();
            buttonRestore(button, restore);

            if (response.fattura) {
                swal({
                    title: type === "download" ? "'.tr('Ricevuta scaricata!').'" : "'.tr('Importazione della ricevuta completata!').'",
                    type: "success",
                });
            } else {
                swal({
                    title: "'.tr('Operazione fallita!').'",
                    type: "error",
                });
            }
        },
        error: function() {
            $("#main_loading").fadeOut();
            buttonRestore(button, restore);

            swal("'.tr('Errore').'", "'.tr('Errore durante il salvataggio').'", "error");
        }
    });
}
</script>';
