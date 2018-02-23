<?php

include_once __DIR__.'/core.php';

$template = Mail::getTemplate($get['id']);
$module = Modules::get($id_module);
$smtp = Mail::get($template['id_smtp']);

$body = $template['body'];
$subject = $template['subject'];

$variables = Mail::getTemplateVariables($template['id'], $id_record);
$email = $variables['email'];

// Sostituzione delle variabili di base
$replaces = [];
foreach ($variables as $key => $value) {
    $replaces['{'.$key.'}'] = $value;
}

$body = str_replace(array_keys($replaces), array_values($replaces), $body);
$subject = str_replace(array_keys($replaces), array_values($replaces), $subject);

// Form
echo '
<form action="" method="post" id="email-form">
	<input type="hidden" name="op" value="send-email">
	<input type="hidden" name="backto" value="record-edit">

    <input type="hidden" name="template" value="'.$template['id'].'">

    <p><b>'.tr('Mittente').'</b>: '.$smtp['from_name'].' &lt;'.$smtp['from_address'].'&gt;</p>';

if (!empty($smtp['cc'])) {
    echo '
    <p><b>'.tr('CC').'</b>: '.$smtp['cc'].'</p>';
}

if (!empty($smtp['cc'])) {
    echo '
    <p><b>'.tr('CCN').'</b>: '.$smtp['bcc'].'</p>';
}

echo '

    <b>'.tr('Destinatari').'</b>
    <div class="row" id="lista-destinatari">
        <div class="col-md-12">
            {[ "type": "email", "name": "destinatari[]", "value": "'.$email.'", "icon-before": "choice|email", "extra": "onkeyup=\'aggiungi_destinatario();\'", "class": "destinatari", "required": 1 ]}
        </div>
    </div>

    <br>

    <div class="row">
        <div class="col-md-8">
            {[ "type": "text", "label": "'.tr('Oggetto').'", "name": "subject", "value": "'.$subject.'", "required": 1 ]}
        </div>

        <div class="col-md-4">
            {[ "type": "checkbox", "label": "'.tr('Notifica di lettura').'", "name": "read_notify", "value": "'.$template['read_notify'].'" ]}
        </div>
    </div>';

// Stampe
$selected_prints = $dbo->fetchArray('SELECT id_print FROM zz_email_print WHERE id_email = '.prepare($template['id']));
$selected = array_column($selected_prints, 'id_print');

echo '

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "multiple": "1", "label": "'.tr('Stampe').'", "name": "prints[]", "value": "'.implode(',', $selected).'", "values": "query=SELECT id, title AS text FROM zz_prints WHERE id_module = '.prepare($id_module).'" ]}
        </div>';

// Allegati
echo '

        <div class="col-md-6">
            {[ "type": "select", "multiple": "1", "label": "'.tr('Allegati').'", "name": "attachments[]", "values": "query=SELECT id, nome AS text FROM zz_files WHERE id_module = '.prepare($id_module).' AND id_record = '.prepare($id_record)." UNION SELECT id, CONCAT(nome, ' (Azienda)') AS text FROM zz_files WHERE id_module = ".prepare(Modules::get('Anagrafiche')['id'])." AND id_record = (SELECT valore FROM zz_settings WHERE nome = 'Azienda predefinita')\" ]}
        </div>
    </div>";

echo '

    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Contenuto').'", "name": "body", "value": '.json_encode($body).' ]}
        </div>
    </div>';

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
            <button type="button" class="btn btn-primary" onclick="send()"><i class="fa fa-envelope"></i> '.tr('Invia').'</button>
		</div>
	</div>
</form>';

echo '
<div id="destinatari_input" class="hide">
    <div class="col-md-12">
        {[ "type": "email", "name": "destinatari[]", "icon-before": "choice|email|cc", "extra": "onkeyup=\'aggiungi_destinatario();\'", "class": "destinatari" ]}
    </div>
</div>';

echo '
<script src="'.$rootdir.'/assets/dist/js/ckeditor/ckeditor.js"></script>';

echo '
<script>
    var emails = [];

    $(document).ready(function(){
        // Autocompletamento destinatario
		$(document).load(globals.rootdir + "/ajax_complete.php?module=Anagrafiche&op=get_email&id_anagrafica='.$variables['id_anagrafica'].'", function(response) {
            emails = JSON.parse(response);

            $(".destinatari").each(function(){
                $(this).autocomplete({
                    source: emails,
                    minLength: 0
                }).focus(function() {
                    $(this).autocomplete("search", $(this).val())
                });;
            });
        });

        CKEDITOR.replace("body", {
            toolbar: globals.ckeditorToolbar
        });
    });

    function send(){
        if($("#email-form").parsley().validate() && confirm("Inviare e-mail?")) {
            $("#email-form").submit();
        }
    }

    function aggiungi_destinatario(){
        var last = $("#lista-destinatari input").last();

        if(last.val()){
            $("#destinatari_input").find(".select2").remove()

            $("#lista-destinatari").append($("#destinatari_input").html());

            $(".destinatari").each(function(){
                $(this).autocomplete({source: emails});
            });

            start_superselect();
        }
    }
</script>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';
