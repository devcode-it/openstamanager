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
    var form = $("#custom_fields_top-add").parent().find("form").first();
    
    // Rimozione select inizializzati
    $("#custom_fields_bottom-add").find("select").each(function () {
        $(this).select2().select2("destroy");
    });
    
    $("#custom_fields_bottom-add").find("select").each(function () {
        $(this).select2().select2("destroy");
    });
                    
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
    
    start_superselect();
    start_datepickers();
});
</script>';

if (isAjaxRequest()) {
    echo '
<script>
$(document).ready(function(){
    data = {};';

    foreach (Filter::getGET() as $key => $value) {
        echo '
    data.'.$key.' = "'.$value.'";';
    }

    echo '

    $("#form_'.$id_module.'-'.$id_plugin.'").find("form").submit(function () {
        submitAjax(this, data, function(response) {
            // Selezione automatica nuovo valore per il select
            select = "#'.get('select').'";
            if ($(select).val() !== undefined) {
                $(select).selectSetNew(response.id, response.text);
            }

            $("#bs-popup2").modal("hide");
        });
        
        return false;
    })
});
</script>';
}

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';
