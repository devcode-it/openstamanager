<?php

include_once __DIR__.'/../../core.php';

use Plugins\ImportFE\Interaction;

$list = Interaction::listToImport();
$directory = Plugins\ImportFE\FatturaElettronica::getImportDirectory();

if (!empty($list)) {
    echo '
<table class="table table-striped table-hover table-condensed table-bordered datatables">
    <thead>
        <tr>
            <th>'.tr('Nome').'</th>
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
                <button type="button" class="btn btn-warning" '.((!extension_loaded('ssl') and strpos($element, 'p7m') !== false) ? 'disabled':'').' onclick="download(this, \''.$element.'\')">
                    <i class="fa fa-download"></i> '.tr('Importa').'
                </button>
            </td>
        </tr>';
    }

    echo '
    </tbody>
</table>';
} else {
    echo '
<p>'.tr('Nessuna fattura da importare').'.</p>';
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
            data = JSON.parse(data);

            if (!data.already) {
                launch_modal("'.tr('Righe fattura').'", globals.rootdir + "/actions.php?id_module=" + globals.id_module + "&id_plugin=" + '.$id_plugin.' + "&op=list&filename=" + data.filename);
				 buttonRestore(button, restore);
            } else {
                swal({
                    title: "'.tr('Fattura già importata.').'",
                    type: "info",
                });
                
				buttonRestore(button, restore);
				$(button).prop("disabled", true);
            }
        },
        error: function(xhr) {
            alert("'.tr('Errore').': " + xhr.responseJSON.error.message);

            buttonRestore(button, restore);
        }
    });
}

function delete_fe(button, file) {
    swal({
        title: "'.tr('Rimuovere la fattura salvata localmente?').'",
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
        title: "'.tr('Segnare la fattura come processata?').'",
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
