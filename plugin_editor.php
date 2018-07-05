<?php

include_once __DIR__.'/core.php';

// Lettura parametri iniziali
$info = Plugins::get($id_plugin);

if (empty($info) || empty($info['enabled'])) {
    die(tr('Accesso negato'));
}

// Inclusione di eventuale plugin personalizzato
if (!empty($info['script'])) {
    include App::filepath('modules/'.$info['module_dir'].'/plugins|custom|', $info['script']);

    return;
}

// Plugin standard
else {
    // Caricamento helper  plugin (verifico se ci sono helper personalizzati)
    include_once Plugins::filepath($info['id'], 'modutil.php');

    // Lettura risultato query del plugin
    include_once Plugins::filepath($info['id'], 'init.php');

    // Esecuzione delle operazioni del plugin
    include $docroot.'/actions.php';

    if (empty($records)) {
        echo '
		<p>'.tr('Record non trovato').'.</p>';
    } else {
        // Lettura template  plugin (verifico se ci sono template personalizzati, altrimenti uso quello base)
        if (file_exists($docroot.'/plugins/'.$info['directory'].'/custom/edit.php')) {
            include $docroot.'/plugins/'.$info['directory'].'/custom/edit.php';
        } elseif (file_exists($docroot.'/plugins/'.$info['directory'].'/custom/edit.html')) {
            include $docroot.'/plugins/'.$info['directory'].'/custom/edit.html';
        } elseif (file_exists($docroot.'/plugins/'.$info['directory'].'/edit.php')) {
            include $docroot.'/plugins/'.$info['directory'].'/edit.php';
        } elseif (file_exists($docroot.'/plugins/'.$info['directory'].'/edit.html')) {
            include $docroot.'/plugins/'.$info['directory'].'/edit.html';
        }

        // Campi personalizzati
        echo '

    <div class="hide" id="custom_fields_top-plugin">
        {( "name": "custom_fields", "id_plugin": "'.$id_plugin.'", "id_record": "'.$id_record.'", "position": "top" )}
    </div>

    <div class="hide" id="custom_fields_bottom-plugin">
        {( "name": "custom_fields", "id_plugin": "'.$id_plugin.'", "id_record": "'.$id_record.'" )}
    </div>

    <script>
    $(document).ready(function(){
        var form = $("#custom_fields_top-plugin").parent().find("form").first();

        // Campi a inizio form
        form.prepend($("#custom_fields_top-plugin").html());

        // Campi a fine form
        var last = form.find(".panel").last();
        if (!last.length) {
            last = form.find(".row").eq(-2);
        }

        last.after($("#custom_fields_bottom-plugin").html());
    });
    </script>';
    }

    redirectOperation($id_module, $id_parent);

    $module = Modules::get($info['idmodule_to']);

    if ($module['permessi'] != 'rw') {
        ?>
<script>

    $(document).ready( function(){
        $('input, textarea, select', 'section.content').attr('readonly', 'true');
        $('select.chzn-done').prop('disabled', true).trigger('liszt:updated');
        $('a.btn, button, input[type=button], input[type=submit]', 'section.content').hide();
        $('a.btn-info, button.btn-info, input[type=button].btn-info', 'section.content').show();
    });
</script>

<?php
    }
}

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';
