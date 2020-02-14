<?php

include_once __DIR__.'/core.php';

// Inclusione elementi fondamentali del modulo
include $docroot.'/actions.php';

// Controllo dei permessi
if (empty($id_plugin)) {
    Permissions::check('rw');
}

// Caricamento template
echo '
<div id="form_'.$id_module.'-'.$id_plugin.'">
';

include !empty(get('edit')) ? $structure->getEditFile() : $structure->getAddFile();

echo '
</div>';

// Campi personalizzati
echo '

<div class="hide" id="custom_fields_top-add">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">

    {( "name": "custom_fields", "id_module": "'.$id_module.'", "id_plugin": "'.$id_plugin.'", "position": "top", "place": "add" )}
</div>

<div class="hide" id="custom_fields_bottom-add">
    {( "name": "custom_fields", "id_module": "'.$id_module.'", "id_plugin": "'.$id_plugin.'", "position": "bottom", "place": "add" )}
</div>

<script>
$(document).ready(function(){
    cleanup_inputs();

    var form = $("#custom_fields_top-add").parent().find("form").first();
                        
    // Campi a inizio form
    form.prepend($("#custom_fields_top-add").html());

    // Campi a fine form
    var last = form.find(".panel").last();

    if (!last.length) {
        last = form.find(".box").last();
    }

    if (!last.length) {
        last = form.find(".row").eq(-2);
    }
    
    last.after($("#custom_fields_bottom-add").html());
    restart_inputs();
});
</script>';

if (isAjaxRequest()) {
    echo '
<script>
$(document).ready(function(){
    $("#form_'.$id_module.'-'.$id_plugin.'").find("form").submit(function () {
        $form = $(this);
        $form.variables = new Object();
        $form.variables.id_module = \''.$id_module.'\';
        $form.variables.id_plugin = \''.$id_plugin.'\';

        submitAjax(this, $form.variables, function(response) {
            // Selezione automatica nuovo valore per il select
            select = "#'.get('select').'";
            console.log($(select).val());
            if ($(select).val() !== undefined) {
                console.log(response.id + " | " + response.text);
                $(select).selectSetNew(response.id, response.text, response.data);
            }

            $form.closest("div[id^=bs-popup").modal("hide");
            
        });
        
        return false;
    })
});
</script>';
}

echo '
<script>$(document).ready(init)</script>';
