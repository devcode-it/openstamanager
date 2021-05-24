<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Modules\Emails\Template;

include_once __DIR__.'/core.php';

$template = Template::find(get('id'));
$module = $template->module;
$smtp = $template->account;

$body = $module->replacePlaceholders($id_record, $template['body']);
$subject = $module->replacePlaceholders($id_record, $template['subject']);

$email = $module->replacePlaceholders($id_record, '{email}');
$id_anagrafica = $module->replacePlaceholders($id_record, '{id_anagrafica}');

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
	<input type="hidden" name="backto" value="'.(get('back') ? get('back') : 'record-edit').'">

	<input type="hidden" name="id_module" value="'.$id_module.'">
	<input type="hidden" name="id_record" value="'.$id_record.'">

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

    <b>'.tr('Destinatari').' <span class="tip" title="'.tr('Email delle sedi, dei referenti o agente collegato all\'anagrafica.').'"><i class="fa fa-question-circle-o"></i></span></b>
    <div class="row" id="lista-destinatari">
        <div class="col-md-12">
            {[ "type": "email", "name": "destinatari[0]", "value": "'.$email.'", "icon-before": "choice|email", "extra": "onkeyup=\'aggiungiDestinatario();\'", "class": "destinatari", "required": 1 ]}
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
$selected_prints = $dbo->fetchArray('SELECT id_print FROM em_print_template WHERE id_template = '.prepare($template['id']));
$selected = array_column($selected_prints, 'id_print');

echo '

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "multiple": "1", "label": "'.tr('Stampe').'", "name": "prints[]", "value": "'.implode(',', $selected).'", "values": "query=SELECT id, title AS text FROM zz_prints WHERE id_module = '.prepare($id_module).' AND enabled=1" ]}
        </div>';

$uploads = [];
if ($smtp['pec']==1 && $module['name']=='Fatture di vendita') {
    $uploads = $dbo->fetchArray('SELECT id FROM zz_files WHERE id_module = '.prepare($module['id']).' AND id_record = '.prepare($id_record).' AND category = \'Fattura Elettronica\'');
    $uploads = array_column($uploads, 'id');
}

// Allegati
echo '

        <div class="col-md-6">
            {[ "type": "select", "multiple": "1", "label": "'.tr('Allegati').'", "name": "uploads[]", "value": "'.implode(',', $uploads).'", "help": "'.tr('Allegati del documento o caricati nell\'anagrafica dell\'azienda.').'", "values": "query=SELECT id, name AS text FROM zz_files WHERE id_module = '.prepare($id_module).' AND id_record = '.prepare($id_record)." UNION SELECT id, CONCAT(name, ' (Azienda)') AS text FROM zz_files WHERE id_module = ".prepare(Modules::get('Anagrafiche')['id'])." AND id_record = (SELECT valore FROM zz_settings WHERE nome = 'Azienda predefinita')\"]}
        </div>
    </div>";

echo '

    <div class="row">
        <div class="col-md-12">
            {[ "type": "ckeditor", "label": "'.tr('Contenuto').'", "name": "body", "id": "body_'.rand(0, 999).'", "value": '.json_encode($body).' ]}
        </div>
    </div>';

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
            <button type="button" class="btn btn-primary" onclick="inviaEmail()"><i class="fa fa-envelope"></i> '.tr('Invia').'</button>
		</div>
	</div>
</form>';

echo '
<div class="hidden" id="template-destinatario">
    <div class="col-md-12">
        {[ "type": "email", "name": "destinatari[-id-]", "icon-before": "choice|email", "extra": "onkeyup=\'aggiungiDestinatario();\'", "class": "destinatari" ]}
    </div>
</div>';

echo '
<script>
    var emails = [];

    $(document).ready(function() {';

        // Autocompletamento destinatario
        if (!empty($id_anagrafica)) {
            echo '
		$(document).load(globals.rootdir + "/ajax_complete.php?module=Anagrafiche&op=get_email&id_anagrafica='.$id_anagrafica.(($smtp['pec']) ? '&type=pec' : '').'", function(response) {
            emails = JSON.parse(response);

            $(".destinatari").each(function(){
                addAutoComplete(this);
            });

            aggiungiDestinatario();
        });';
        }

        echo '

    });

    function inviaEmail() {
        if($("#email-form").parsley().validate() && confirm("Inviare e-mail?")) {
            $("#email-form").submit();
        }
    }

    function addAutoComplete(input){
        $(input).autocomplete({
            source: emails,
            minLength: 0,
            close: function() {
                aggiungiDestinatario();
            },
        }).focus(function() {
            $(this).autocomplete("search", $(this).val());
        });
    }

    function aggiungiDestinatario() {
        var last = $("#lista-destinatari input").last();

        if (last.val()) {
            cleanup_inputs();

            aggiungiContenuto("#lista-destinatari", "#template-destinatario", {"-id-": $("#lista-destinatari > div").length});

            $(".destinatari").each(function(){
                addAutoComplete(this);
            });

            restart_inputs();
        }
    }
</script>';

echo '
<script>$(document).ready(init)</script>';
