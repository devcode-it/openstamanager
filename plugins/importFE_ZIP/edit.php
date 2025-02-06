<?php

include_once __DIR__.'/../../core.php';

use Plugins\ImportFE\Interaction;

if (setting('Metodo di importazione XML fatture di vendita') == 'Automatico') {
    echo '
    <script>
        function upload1(btn) {
            if ($("#blob1").val()) {
                swal({
                    title: "'.tr('Avviare la procedura?').'",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: "'.tr('Sì').'"
                }).then(function (result) {
                    var restore = buttonLoading(btn);
    
                    $("#upload1").ajaxSubmit({
                        url: globals.rootdir + "/actions.php",
                        data: {
                            op: "save",
                            id_module: "'.$id_module.'",
                            id_plugin: "'.$id_plugin.'",
                        },
                        type: "post",
                        success: function(data){
    
                            swal("Caricamento completato!", "", "success");
    
                            $("#blob1").val("");
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
    </script>';
} else {
    if (!empty($record)) {
        include $structure->filepath('generate.php');

        return;
    }
    echo '
    <script>
        function upload1(btn) {
            if ($("#blob1").val()) {
                swal({
                    title: "'.tr('Avviare la procedura?').'",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: "'.tr('Sì').'"
                }).then(function (result) {
                    var restore = buttonLoading(btn);
    
                    $("#upload1").ajaxSubmit({
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
    
                                $("#blob1").val("");
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
    </script>';
}

echo '
<div class="card card-success">
    <div class="card-header with-border">
        <h3 class="card-title">
            '.tr('Carica un file ZIP contenente i file XML').'

            <span class="tip" title="'.tr('Formati supportati: ZIP').'.">
                <i class="fa fa-question-circle-o"></i>
            </span>

        </h3>
    </div>
    <div class="card-body" id="upload1">
        <div class="row">
            <div class="col-md-9">
                {[ "type": "file", "name": "blob1", "id":"blob1", "required": 1 ]}
            </div>

            <div class="col-md-3">
                <button type="button" class="btn btn-primary pull-right" onclick="upload1(this)">
                    <i class="fa fa-upload"></i> '.tr('Carica documenti').'
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

        <div class="float-right d-none d-sm-inline">
            <button type="button" class="btn btn-warning" onclick="importAll(this)">
                <i class="fa fa-cloud-download"></i> '.tr('Importa tutte').'
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
    <div class="card-body" id="list">';

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
