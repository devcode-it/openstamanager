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

$module_dir = $module['directory'];

echo '
<div id="form_'.$id_module.'-'.$id_plugin.'">
';

// Caricamento template popup
if (file_exists($docroot.$directory.'/custom/add.php')) {
    include $docroot.$directory.'/custom/add.php';
} elseif (file_exists($docroot.$directory.'/custom/add.html')) {
    include $docroot.$directory.'/custom/add.html';
} elseif (file_exists($docroot.$directory.'/add.php')) {
    include $docroot.$directory.'/add.php';
} elseif (file_exists($docroot.$directory.'/add.html')) {
    include $docroot.$directory.'/add.html';
}

echo '
</div>';

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
        url: "'.$rootdir.$directory.'/actions.php",
        data: data,
        type: "post",
        success: function(data){
            data = data.trim();

            if(data && !$("#'.$get['select'].'").val()) {
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
