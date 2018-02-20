<?php

include_once __DIR__.'/core.php';

$template = Mail::getTemplate($get['id']);
$module = Modules::get($id_module);

$body = $template['body'];
$subject = $template['subject'];

$variables = Mail::getTemplateVariables($template);
$email = $variables['email'];

echo '
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="send-email">
	<input type="hidden" name="backto" value="record-edit">

    <input type="hidden" name="template" value="'.$template['id'].'">

    <!-- Dati -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Dati').'</h3>
		</div>

		<div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Oggetto').'", "name": "subject", "value": "'.$subject.'", "required": 1 ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "email", "label": "'.tr('Destinatario').'", "name": "email", "value": "'.$email.'", "required": 1 ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "textarea", "label": "'.tr('Contenuto').'", "name": "body", "value": '.json_encode($body).' ]}
                </div>
            </div>
        </div>
    </div>';

echo '
    <div class="row">

        <!-- Stampe -->
        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">'.tr('Stampe').'</h3>
                </div>

                <div class="panel-body">';

$selected_prints = $dbo->fetchArray('SELECT id_print FROM zz_email_print WHERE id_email = '.prepare($template['id']));
$selected = array_column($selected_prints, 'id_print');

$prints = Prints::getModulePrints($id_module);
foreach ($prints as $print) {
    echo '
                    {[ "type": "checkbox", "label": "'.$print['title'].'", "name": "print-'.$print['id'].'", "value": "'.in_array($print['id'], $selected).'" ]}';
}

echo '
                </div>
            </div>
        </div>

        <!-- Allegati -->
        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">'.tr('Allegati').'</h3>
                </div>

                <div class="panel-body">';

$attachments = $dbo->fetchArray('SELECT * FROM zz_files WHERE id_module = '.prepare($id_module).' AND id_record = '.prepare($id_record));

if (empty($attachments)) {
    echo '
                    <p>'.tr('Nessun allegato disponibile').'.</p>';
}

foreach ($attachments as $attachment) {
    echo '
                    {[ "type": "checkbox", "label": "'.$attachment['nome'].'", "name": "attachment-'.$attachment['id'].'" ]}';
}

echo '
                </div>
            </div>
        </div>

        <!-- Anagrafica -->
        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">'.tr('Anagrafica').'</h3>
                </div>

                <div class="panel-body">';

$attachments = $dbo->fetchArray('SELECT * FROM zz_files WHERE id_module = '.prepare(Modules::get('Anagrafiche')['id'])." AND id_record = (SELECT valore FROM zz_settings WHERE nome = 'Azienda predefinita')");

if (empty($attachments)) {
    echo '
                    <p>'.tr('Nessun allegato disponibile').'.</p>';
}

foreach ($attachments as $attachment) {
    echo '
                    {[ "type": "checkbox", "label": "'.$attachment['nome'].'", "name": "default-'.$attachment['id'].'" ]}';
}

echo '
                </div>
            </div>
        </div>

    </div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-envelope"></i> '.tr('Invia').'</button>
		</div>
	</div>
</form>';

echo '
<script src="'.$rootdir.'/assets/dist/js/ckeditor/ckeditor.js"></script>';

echo '
<script>
    $(document).ready(function(){
        CKEDITOR.replace("body");
    });
</script>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';
