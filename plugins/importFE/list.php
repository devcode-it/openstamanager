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

$list = Interaction::getInvoiceList();

$directory = Plugins\ImportFE\FatturaElettronica::getImportDirectory();

if (!empty($list)) {
    echo '
<table class="table table-striped table-hover table-condensed table-bordered datatables">
    <thead>
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th class="text-center">'.tr('Fornitore').'</th>
            <th class="text-center">'.tr('Data di registrazione').'</th>
            <th class="text-center">'.tr('Totale imponibile').'</th>
            <th width="20%" class="text-center">#</th>
        </tr>
    </thead>
    <tbody>';

    foreach ($list as $element) {
        $name = $element['name'];
        $data = $element['date_sent'] ?: '';

        echo '
        <tr>';

        if (!empty($element['file'])) {
            echo '
            <td>
                <p>'.$name.'</p>
            </td>

            <td class="text-center">-</td>
            <td class="text-center">-</td>
            <td class="text-center">-</td>

            <td class="text-center">
                <button type="button" class="btn btn-danger" onclick="delete_fe(this, \''.$element['id'].'\')">
                    <i class="fa fa-trash"></i>
                </button>';
        } else {
            $date = new DateTime($element['date']);
            $date = $date->format('Y-m-d');

            $descrizione = '';
            if ($element['type'] == 'TD01') {
                $descrizione = tr('Fattura num. _NUM_ del _DATE_', [
                    '_NUM_' => $element['number'],
                    '_DATE_' => dateFormat($date),
                ]);
            } elseif ($element['type'] == 'TD04') {
                $descrizione = tr('Nota di credito num. _NUM_ del _DATE_', [
                    '_NUM_' => $element['number'],
                    '_DATE_' => dateFormat($date),
                ]);
            } elseif ($element['type'] == 'TD05') {
                $descrizione = tr('Nota di debito num. _NUM_ del _DATE_', [
                    '_NUM_' => $element['number'],
                    '_DATE_' => dateFormat($date),
                ]);
            } elseif ($element['type'] == 'TD06') {
                $descrizione = tr('Parcella num. _NUM_ del _DATE_', [
                    '_NUM_' => $element['number'],
                    '_DATE_' => dateFormat($date),
                ]);
            }

            echo '
            <td>
                '.$descrizione.' <small>['.$name.']</small>
            </td>

            <td>'.$element['sender'].'</td>
            <td>'.dateFormat($element['date_sent']).'</td>
            <td class="text-right">'.moneyFormat($element['amount']).'</td>

            <td class="text-center">
                <button type="button" class="btn btn-info tip" onclick="process_fe(this, \''.$name.'\')" title="'.tr('Segna la fattura come processata').'">
                    <i class="fa fa-upload"></i>
                </button>';
        }

        if (file_exists($directory.'/'.$name)) {
            echo '
                <button type="button" class="btn btn-primary tip" onclick="download_fe(this, \''.$element['id'].'\')" title="'.tr('Scarica la fattura').'">
                    <i class="fa fa-download"></i>
                </button>';
        }

        echo '

                <button type="button" class="btn btn-warning tip" '.((!extension_loaded('openssl') && substr(strtolower($name), -4) == '.p7m') ? 'disabled' : '').' onclick="import_fe(this, \''.$name.'\', \''.$data.'\')" title="'.tr('Importa la fattura nel gestionale').'">
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
<p>'.tr('Nessuna fattura da importare').'.</p>';
}

echo '
<script>
function import_fe(button, file, data_registrazione) {
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
                redirect(globals.rootdir + "/editor.php?id_module=" + globals.id_module + "&id_plugin=" + '.$id_plugin.' + "&id_record=" + data.id + "&data_registrazione=" + data_registrazione);
            } else {
                swal({
                    title: "'.tr('Fattura già importata.').'",
                    type: "info",
                });

				$(button).prop("disabled", true);
            }

            buttonRestore(button, restore);
        },
        error: function(xhr) {
            alert("'.tr('Errore').': " + xhr.responseJSON.error.message);

            buttonRestore(button, restore);
        }
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

function delete_fe(button, file_id) {
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
                file_id: file_id,
            },
            success: function(data) {
                $("#list").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'", function() {
                    buttonRestore(button, restore);
                });
            }
        });
    });
}

function download_fe(button, file_id) {
    redirect(globals.rootdir + "/actions.php", {
        id_module: globals.id_module,
        id_plugin: '.$id_plugin.',
        op: "download",
        file_id: file_id,
    }, "get", true);
}

start_local_datatables();
init();
</script>';
