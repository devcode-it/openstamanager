<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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
                            redirect(globals.rootdir + "/editor.php?id_module=" + globals.id_module + "&id_plugin=" + '.$id_plugin.' + "&id_record=" + data.id);
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
                        alert("'.tr('Errore').': " + xhr.responseJSON.error.message);

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

<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">
            '.tr('Carica un XML').'

            <span class="tip" title="'.tr('Formati supportati: XML, P7M e ZIP').'.">
                <i class="fa fa-question-circle-o"></i>
            </span>

        </h3>
    </div>
    <div class="box-body" id="upload">
        <div class="row">
            <div class="col-md-9">
                {[ "type": "file", "name": "blob", "required": 1 ]}
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
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">
            '.tr('Fatture da importare').'</span>
        </h3>

        <div class="pull-right">
            <button type="button" class="btn btn-warning" onclick="importAll(this)">
                <i class="fa fa-cloud-download"></i> '.tr('Importa in sequenza').'
            </button>';

// Ricerca automatica
if (Interaction::isEnabled()) {
    echo '
            <button type="button" class="btn btn-primary" onclick="search(this)">
                <i class="fa fa-refresh"></i> '.tr('Ricerca fatture di acquisto').'
            </button>';
}

echo '
        </div>
    </div>
    <div class="box-body" id="list">';

if (Interaction::isEnabled()) {
    echo '
        <p>'.tr('Per vedere le fatture da importare utilizza il pulsante _BUTTON_', [
            '_BUTTON_' => '<b>"'.tr('Ricerca fatture di acquisto').'"</b>',
        ]).'.</p>';
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
});';

if (Interaction::isEnabled()) {
    echo '
function importAll(btn) {
    swal({
        title: "'.tr('Importare tutte le fatture?').'",
        html: "'.tr('Verranno scaricate tutte le fatture da importare, e non sarà più possibile visualizzare altre informazioni oltre al nome per le fatture che non verranno importate completamente. Continuare?').'",
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

        redirect(globals.rootdir + "/editor.php?id_module=" + globals.id_module + "&id_plugin=" + '.$id_plugin.' + "&id_record=1&sequence=1");
    }
}';
} else {
    echo '
function importAll(btn) {
    redirect(globals.rootdir + "/editor.php?id_module=" + globals.id_module + "&id_plugin=" + '.$id_plugin.' + "&id_record=1&sequence=1");
}';
}
echo '

function search(btn) {
    var restore = buttonLoading(btn);

    $("#list").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'", function() {
        buttonRestore(btn, restore);
    });
}

</script>';
