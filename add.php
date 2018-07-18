<?php

include_once __DIR__.'/core.php';

if (!empty($id_plugin)) {
    $info = Plugins::get($id_plugin);

    $directory = '/plugins/'.$info['directory'];
} else {
    Permissions::check('rw');

    $module = Modules::get($id_module);

    $directory = '/modules/'.$module['directory'];
}

// Inclusione elementi fondamentali del modulo
include $docroot.'/actions.php';

echo '
<div id="form_'.$id_module.'-'.$id_plugin.'">
';

// Caricamento template
$file = !empty(get('edit')) ? 'edit' : 'add';

if (file_exists($docroot.$directory.'/custom/'.$file.'.php')) {
    include $docroot.$directory.'/custom/'.$file.'.php';
} elseif (file_exists($docroot.$directory.'/custom/'.$file.'.html')) {
    include $docroot.$directory.'/custom/'.$file.'.html';
} elseif (file_exists($docroot.$directory.'/'.$file.'.php')) {
    include $docroot.$directory.'/'.$file.'.php';
} elseif (file_exists($docroot.$directory.'/'.$file.'.html')) {
    include $docroot.$directory.'/'.$file.'.html';
}

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

    // Campi a inizio form
    form.prepend($("#custom_fields_top-add").html());

    // Campi a fine form
    var last = form.find(".panel").last();
    if (!last.length) {
        last = form.find(".row").eq(-2);
    }

    last.after($("#custom_fields_bottom-add").html());
});
</script>';

if (isAjaxRequest()) {
    echo '
<script>
$(document).ready(function(){
    data = {};';

    foreach ($get as $key => $value) {
        echo '
    data.'.$key.' = "'.$value.'";';
    }

    echo '

    $("#form_'.$id_module.'-'.$id_plugin.'").find("form").ajaxForm({
        url: globals.rootdir + "/actions.php",
        beforeSubmit: function(arr, $form, options) {
            return $form.parsley().validate();
        },
        data: data,
        type: "post",
        success: function(data){
            data = data.trim();

            if(data && $("#'.$get['select'].'").val() !== undefined ) {
                result = JSON.parse(data);
                $("#'.$get['select'].'").selectSetNew(result.id, result.text);
            }

            $("#bs-popup2").modal("hide");
        },
        error: function(data) {
            alert("'.tr('Errore').': " + data);
        }
    });
});
</script>';
}

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';
