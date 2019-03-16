<?php

$template = Mail::getTemplate(get('id'));
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

// Campi mancanti
$campi_mancanti = [];

if (empty($smtp['from_address'])) {
    $campi_mancanti[] = tr('Mittente');
}
if (empty($smtp['server'])) {
    $campi_mancanti[] = tr('Server SMTP');
}
if (empty($smtp['port'])) {
    $campi_mancanti[] = tr('Porta');
}

if (sizeof($campi_mancanti) > 0) {
    echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr("Prima di procedere all'invio completa: _VALUES_", [
            '_VALUES_' => '<b>'.implode(', ', $campi_mancanti).'</b>',
    ]).'<br/>
    '.Modules::link('Account email', $smtp['id'], tr('Vai alla scheda account email'), null).'
</div>';
}

// Form
echo '
<form action="" method="post" id="email-form">
	<input type="hidden" name="op" value="send-email">
	<input type="hidden" name="backto" value="record-edit">

    <input type="hidden" name="template" value="'.$template['id'].'">

    <p><b>'.tr('Mittente').'</b>: '.$smtp['from_name'].' &lt;'.$smtp['from_address'].'&gt;</p>';

if (!empty($template['cc'])) {
    echo '
    <p><b>'.tr('CC').'</b>: '.$template['cc'].'</p>';
}

if (!empty($template['bcc'])) {
    echo '
    <p><b>'.tr('CCN').'</b>: '.$template['bcc'].'</p>';
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
            {[ "type": "checkbox", "label": "'.tr('Richiedi notifica di lettura').'", "name": "read_notify", "value": "'.$template['read_notify'].'" ]}
        </div>
    </div>';

// Stampe
$selected_prints = $dbo->fetchArray('SELECT id_print FROM zz_email_print WHERE id_email = '.prepare($template['id']));
$selected = array_column($selected_prints, 'id_print');

echo '

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "multiple": "1", "label": "'.tr('Stampe').'", "name": "prints[]", "value": "'.implode(',', $selected).'", "values": "query=SELECT id, title AS text FROM zz_prints WHERE id_module = '.prepare($id_module).' AND enabled=1" ]}
        </div>';

$attachments = [];
if ($template['name'] == 'Fattura Elettronica') {
    $attachments = $dbo->fetchArray('SELECT id FROM zz_files WHERE id_module = '.prepare($module['id']).' AND id_record = '.prepare($id_record).' AND category = \'Fattura Elettronica\'');
    $attachments = array_column($attachments, 'id');
}

// Allegati
echo '

        <div class="col-md-6">
            {[ "type": "select", "multiple": "1", "label": "'.tr('Allegati').'", "name": "attachments[]", "value": "'.implode(',', $attachments).'", "values": "query=SELECT id, name AS text FROM zz_files WHERE id_module = '.prepare($id_module).' AND id_record = '.prepare($id_record)." UNION SELECT id, CONCAT(name, ' (Azienda)') AS text FROM zz_files WHERE id_module = ".prepare(Modules::get('Anagrafiche')['id'])." AND id_record = (SELECT valore FROM zz_settings WHERE nome = 'Azienda predefinita')\" ]}
        </div>
    </div>";

echo '

    <div class="row">
        <div class="col-md-12">
            {[ "type": "ckeditor", "label": "'.tr('Contenuto').'", "name": "body", "value": '.json_encode($body).' ]}
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
<script>
    var emails = [];

    $(document).ready(function(){';

        // Autocompletamento destinatario
        if (!empty($variables['id_anagrafica'])) {
            echo '
		$(document).load(globals.rootdir + "/ajax_complete.php?module=Anagrafiche&op=get_email&id_anagrafica='.$variables['id_anagrafica'].(($smtp['pec']) ? '&type=pec' : '').'", function(response) {
            emails = JSON.parse(response);

            $(".destinatari").each(function(){
                $(this).autocomplete({
                    source: emails,
                    minLength: 0
                }).focus(function() {
                    $(this).autocomplete("search", $(this).val())
                });
            });

            aggiungi_destinatario();
        });';
        }

        echo '

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
	<script src="'.$rootdir.'/assets/js/init.min.js"></script>';
