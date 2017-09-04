<?php

include_once __DIR__.'/core.php';

if (!empty($id_plugin)) {
    $info = Plugins::getPlugin($id_plugin);

    $directory = '/plugins/'.$info['directory'];
} else {
    Permissions::check('rw');

    $module = Modules::getModule($id_module);

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
	$("#form_'.$id_module.'-'.$id_plugin.'").find("form").on("submit", function(){
		if($(this).parsley().validate()){
			var form_data = new FormData();';

    foreach ($get as $key => $value) {
        echo '
			form_data.append("'.$key.'", "'.$value.'");';
    }

    echo '

			$(this).find("input, textarea, select").each(function(){
                var name = $(this).attr("name");

                var data = $(this).val();
                data = (typeof data == "string") ? [data] : data;

                data.forEach(function(item){
                    form_data.append(name, item);
                });
			});

			$.ajax({
				url: "'.$rootdir.$directory.'/actions.php",
				cache: false,
				type: "post",
				processData: false,
				contentType: false,
				dataType : "html",
				data: form_data,
				success: function(data) {
					data = data.trim();

                    if(data && !$("#'.$get['select'].'").val()) {
                        result = JSON.parse(data);
                        $("#'.$get['select'].'").append(\'<option value="\' + result.id +\'">\' + result.text + \'</option>\').val(result.id).trigger("change");
					}

					$("#bs-popup2").modal("hide");
				},
				error: function() {
					alert("'.tr('Errore').': " + form_data);
				}
			});
		}

		return false;
	});
});
</script>';
}

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';
