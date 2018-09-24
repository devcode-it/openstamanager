<?php

include_once __DIR__.'/../../core.php';

echo '
<script>
    function upload() {
        if ($("#blob").val()) {
            swal({
                title: "'.tr('Avviare la procedura?').'",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "'.tr('Sì').'"
            }).then(function (result) {
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

                        launch_modal("'.tr('Righe fattura').'", globals.rootdir + "/plugins/importPA/rows.php?id_module=" + globals.id_module + "&id_plugin=" + '.$id_plugin.' + "&id=" + data.id + "&filename=" + data.filename + "&id_segment" + data.id_segment)
                    },
                    error: function(data) {
                        alert("'.tr('Errore').': " + data);
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
            '.tr('Carica un XML').'</span>
        </h3>
    </div>
    <div class="box-body" id="upload">

        <div class="row">
            <div class="col-md-9">
                <label><input type="file" name="blob" id="blob"></label>
            </div>

            <div class="col-md-3">
                {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='.$id_module.' ORDER BY name", "value": "'.$_SESSION['module_'.$id_module]['id_segment'].'" ]}
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 text-center">
                <button type="button" class="btn btn-primary btn-lg" onclick="upload()">
                    <i class="fa fa-upload"></i> '.tr('Carica').'...
                </button>
            </div>
        </div>
    </div>
</div>';
