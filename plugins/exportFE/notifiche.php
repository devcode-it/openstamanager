<?php

use Modules\Fatture\Fattura;
use Plugins\ExportFE\Interaction;

include_once __DIR__.'/../../core.php';

$result = Interaction::getInvoiceRecepits($id_record);
$recepits = $result['results'];

$documento = Fattura::find($id_record);

if (empty($recepits)) {
    echo '
<p>'.tr('Il documento non ha notifiche disponibili').'.</p>';

    return;
}

echo '
<p>'.tr("Segue l'elenco completo delle notifiche/ricevute relative alla fatture elettronica di questo documento").'.</p>
<p>'.tr('La procedura di importazione prevede di impostare in modo autonomo la notifica più recente come principale, ma si verificano alcune situazioni in cui il comportamento richiesto deve essere distinto').'. '.tr('Qui si può procedere a scaricare una specifica notifica e a impostarla manualmente come principale per il documento').'.</p>
<table class="table">
    <thead>
        <tr>
            <th>'.tr('Nome').'</th>
            <th class="text-center">'.tr('Scaricata').'</th>
            <th class="text-center">'.tr('Opzioni').'</th>
        </tr>
    </thead>

    <tbody>';

foreach ($recepits as $nome) {
    $upload = $documento->uploads()
        ->where('name', 'Fattura Elettronica')
        ->where('original', $nome)
        ->first();

    // Individuazione codice ricevuta
    $filename = explode('.', $nome)[0];
    $pieces = explode('_', $filename);
    $codice_stato = $pieces[2];

    // Informazioni sullo stato indicato
    $stato_fe = $database->fetchOne('SELECT * FROM fe_stati_documento WHERE codice = '.prepare($codice_stato));

    echo '
        <tr data-name="'.$nome.'">
            <td>'.$nome.'</td>

            <td class="text-center">';

    if (empty($upload)) {
        echo tr('No');
    } else {
        echo '
            <a href="'.ROOTDIR.'/view.php?file_id='.$upload->id.'" target="_blank">
                <i class="fa fa-external-link"></i> '.tr('Visualizza').'
            </a>';
    }

    echo '
            <td class="text-center">';

    if (empty($upload)) {
        echo '
                <button type="button" class="btn btn-info btn-sm" onclick="scaricaRicevuta(this)">
                    <i class="fa fa-download"></i>
                </button>';
    }

    if (empty($upload) || $upload->id != $documento->id_ricevuta_principale) {
        echo '
                <button type="button" class="btn btn-warning btn-sm" onclick="impostaRicevuta(this)">
                    <i class="fa fa-check-circle"></i>
                </button>';
    } elseif ($upload->id == $documento->id_ricevuta_principale) {
        echo '
                <button type="button" class="btn btn-success btn-sm disabled">
                    <i class="fa fa-check-circle"></i>
                </button>';
    }

    echo '
            </td>
        </tr>';
}

echo '
    </tbody>
</table>

<script>
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
            buttonRestore(button, restore);

            if(response.fattura) {
                swal({
                    title: "'.tr('Importazione completata!').'",
                    type: "success",
                });
            } else {
                swal({
                    title: "'.tr('Importazione fallita!').'",
                    type: "error",
                });
            }
        },
        error: function() {
            buttonRestore(button, restore);

            swal("'.tr('Errore').'", "'.tr('Errore durante il salvataggio').'", "error");
        }
    });
}
</script>';
