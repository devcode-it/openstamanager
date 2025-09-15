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
            $("#list-receiptfe").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'");
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

start_local_datatables();
</script>';
