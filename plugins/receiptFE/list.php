<?php

include_once __DIR__.'/../../core.php';

use Plugins\ReceiptFE\Interaction;
use Plugins\ReceiptFE\Ricevuta;

$list = Interaction::getReceiptList();

$directory = Ricevuta::getImportDirectory();

if (!empty($list)) {
    echo '
<table class="table table-striped table-hover table-condensed table-bordered datatables">
    <thead>
        <tr>
            <th width="80%">'.tr('Nome').'</th>
            <th width="20%" class="text-center">#</th>
        </tr>
    </thead>
    <tbody>';

    foreach ($list as $element) {
        echo '
        <tr>
            <td>'.$element.'</td>
            <td class="text-center">';

        if (file_exists($directory.'/'.$element)) {
            echo '
                <button type="button" class="btn btn-danger" onclick="delete_fe(this, \''.$element.'\')">
                    <i class="fa fa-trash"></i>
                </button>';
        } else {
            echo '
                <button type="button" class="btn btn-info" onclick="process_fe(this, \''.$element.'\')">
                    <i class="fa fa-upload"></i>
                </button>';
        }

        echo '
                <button type="button" class="btn btn-warning" '.((!extension_loaded('openssl') and substr(strtolower($element), -4) == '.p7m') ? 'disabled' : '').' onclick="download(this, \''.$element.'\')">
                    <i class="fa fa-cloud-download"></i> '.tr('Importa').'
                </button>
            </td>
        </tr>';
    }

    echo '
    </tbody>
</table>';
} else {
    echo '
<p>'.tr('Nessuna ricevuta da importare').'.</p>';
}

echo '
<script>
function download(button, file) {
    var restore = buttonLoading(button);

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
            importMessage(data);
            
            buttonRestore(button, restore);
        },
        error: function(xhr) {
            alert("'.tr('Errore').': " + xhr.responseJSON.error.message);

            buttonRestore(button, restore);
        }
    });
}

function delete_fe(button, file) {
    swal({
        title: "'.tr('Rimuovere la ricevuta salvata localmente?').'",
        html: "'.tr('Sarà possibile inserirla nuovamente nel gestionale attraverso il caricamento').'",
        type: "error",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function (result) {
        var restore = buttonLoading(button);
    
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "get",
            data: {
                id_module: globals.id_module,
                id_plugin: '.$id_plugin.',
                op: "delete",
                name: file,
            },
            success: function(data) {
                $("#list").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'", function() {
                    buttonRestore(button, restore);
                });
            }
        });
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
        var restore = buttonLoading(button);
    
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
                $("#list").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'", function() {
                    buttonRestore(button, restore);
                });
            }
        });
    });
}

start_local_datatables();
</script>';
