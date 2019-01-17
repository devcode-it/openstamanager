<?php

include_once __DIR__.'/../../core.php';

use Plugins\ImportFE\Interaction;

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
                            launch_modal("'.tr('Righe fattura').'", globals.rootdir + "/actions.php?id_module=" + globals.id_module + "&id_plugin=" + '.$id_plugin.' + "&op=list&filename=" + data.filename);
                        } else {
                            swal({
                                title: "'.tr('Fattura già importata.').'",
                                type: "info",
                            });
							
							$("#blob").val("");
                        }
						buttonRestore(btn, restore);
                    },
                    error: function(data) {
                        alert("'.tr('Errore').': " + data);

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

            <span class="tip" title="'.tr('Formati supportati: XML e P7M').'.">
                <i class="fa fa-question-circle-o"></i>
            </span>

        </h3>
    </div>
    <div class="box-body" id="upload">
        <div class="row">
            <div class="col-md-9">
                <label><input type="file" name="blob" id="blob"></label>
            </div>

            <div class="col-md-3">
                <button type="button" class="btn btn-primary pull-right" onclick="upload(this)">
                    <i class="fa fa-upload"></i> '.tr('Carica documento fornitore').'
                </button>
            </div>
        </div>
    </div>
</div>';

if (Interaction::isEnabled()) {
    echo '
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">
            '.tr('Importazione automatica').'</span>
        </h3>
        <button type="button" class="btn btn-primary pull-right" onclick="search(this)">
            <i class="fa fa-refresh"></i> '.tr('Ricerca fatture di acquisto').'
        </button>
    </div>
    <div class="box-body" id="list">';

    include $structure->filepath('list.php');

    echo '

    </div>
</div>

<script>
function search(button) {
    var restore = buttonLoading(button);

    $("#list").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'", function() {
        buttonRestore(button, restore);
    });
}
</script>';
}
