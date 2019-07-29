<?php

include_once __DIR__.'/../../../core.php';

$manager_id = filter('manager_id');

$checks = $structure->checks($id_record);
$list = [];
foreach ($checks as $check) {
    $list[] = [
        'id' => $check->id,
        'text' => $check->content,
    ];
}

echo '
<form action="" method="post" id="check-form">
    <div class="row">
        <div class="col-md-12">
            {[ "type": "text", "label": "'.tr('Contenuto').'", "name": "content", "class": "unblockable", "required": 1 ]}
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Genitore').'", "name": "parent", "class": "unblockable", "values": '.json_encode($list).' ]}
        </div>
        
         <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Utente').'", "name": "assigned_user", "class": "unblockable", "ajax-source": "utenti", "required": 1 ]}
        </div>
    </div>
    
    <!-- PULSANTI -->
	<div class="row">
        <div class="col-md-12 text-right">
            <button type="button" class="btn btn-primary" id="check-add">
                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
        </div>
    </div>
</form>

<script>$(document).ready(init)</script>

<script type="module">
import Checklist from "./modules/checklists/js/checklist.js";

$(document).ready(function() {
    $("#check-add").click(function(event){
        addCheck(this);
    });
});

function addCheck(btn) {
    var $form = $(btn).closest("form");
    
    var continua = true;
    $form.find(":input:not(:button)").each(function (index, value) { 
        continua &= $(this).parsley().validate();
    });

    if (!continua) {
        swal({
            type: "error",
            title: "'.tr('Errore').'",
            text: "'.tr('Alcuni campi obbligatori non sono stati compilati correttamente.').'",
        });

        return;
    }
    
    var checklist = new Checklist({
        id_module: "'.$id_module.'",
        id_plugin: "'.$id_plugin.'",
        id_record: "'.$id_record.'",
    }, "'.$manager_id.'");
   
    checklist.addCheck({
        content: $form.find("#content").val(),
        parent: $form.find("#parent").val(),
        assigned_user: $form.find("#assigned_user").val(),
    });
    
    $form.closest(".modal").modal("hide");
}
</script>';
