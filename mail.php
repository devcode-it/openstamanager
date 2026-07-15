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

use Models\Module;
use Modules\Emails\Template;

include_once __DIR__.'/core.php';

$template = Template::find(get('id'));
$module = $template->module;
$smtp = $template->account;

$placeholder_options = [
    'is_pec' => intval($smtp['pec']),
];

$body = $module->replacePlaceholders($id_record, $template->getTranslation('body'), $placeholder_options);
$subject = $module->replacePlaceholders($id_record, $template->getTranslation('subject'), $placeholder_options);

$emails = [];
if ($module->replacePlaceholders($id_record, '{email}')) {
    $emails = explode(';', $module->replacePlaceholders($id_record, '{email}', $placeholder_options));
}

$id_anagrafica = $module->replacePlaceholders($id_record, '{id_anagrafica}', $placeholder_options);

// Calcolo ReplyTo
$reply_to = '';
if (!empty($template['tipo_reply_to'])) {
    if ($template['tipo_reply_to'] == 'email_fissa') {
        $reply_to = $module->replacePlaceholders($id_record, $template['reply_to'], $placeholder_options);
    } elseif ($template['tipo_reply_to'] == 'email_user') {
        $user = auth_osm()->getUser();
        $reply_to = $user->email;
    }
}

// Aggiungo email referenti in base alla mansione impostata nel template
$mansioni = $dbo->select('em_mansioni_template', 'id_mansione', [], ['id_template' => $template->id]);
foreach ($mansioni as $mansione) {
    $referenti = $dbo->table('an_referenti')->where('id_mansione', $mansione['id_mansione'])->where('id_anagrafica', $id_anagrafica)->where('email', '!=', '')->get();
    foreach ($referenti as $referente) {
        if (!in_array($referente->email, $emails)) {
            $emails[] = $referente->email;
        }
    }
}

// Aggiungo email tecnici assegnati quando sono sul template Notifica intervento
if ($template->name == 'Notifica intervento') {
    $tecnici = database()->table('in_interventi_tecnici_assegnati')->where('id_intervento', $id_record)->pluck('id_tecnico')->toArray();
    foreach ($tecnici as $id_tecnico) {
        $anagrafica = database()->table('an_anagrafiche')->where('id', $id_tecnico)->where('email', '!=', '')->first();
        if (!empty($anagrafica) && !in_array($anagrafica->email, $emails)) {
            $emails[] = $anagrafica->email;
        }
}
}

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

// Verifica se l'utente può modificare CC e CCN
$allowed_groups = setting('Gruppi abilitati alla modifica CC e CCN');
$allowed_groups_array = !empty($allowed_groups) ? explode(',', $allowed_groups) : [];
$user = auth_osm()->getUser();
$user_group = $user->id_gruppo;
$can_edit_cc_bcc = in_array($user_group, $allowed_groups_array);

// Mostra CC e CCN
if (!empty($template['cc'])) {
    if ($can_edit_cc_bcc) {
        echo '
        <div class="row">
            <div class="col-md-12">
                {[ "type": "text", "label": "'.tr('CC').'", "name": "cc", "value": "'.$template['cc'].'", "help": "'.tr('Copia carbone').'" ]}
            </div>
        </div>';
    } else {
        echo '
        <p><b>'.tr('CC').'</b>: '.$template['cc'].'</p>';
    }
}

if (!empty($template['bcc'])) {
    if ($can_edit_cc_bcc) {
        echo '
        <div class="row">
            <div class="col-md-12">
                {[ "type": "text", "label": "'.tr('CCN').'", "name": "bcc", "value": "'.$template['bcc'].'", "help": "'.tr('Copia carbone nascosta').'" ]}
            </div>
        </div>';
    } else {
        echo '
        <p><b>'.tr('CCN').'</b>: '.$template['bcc'].'</p>';
    }
}

if (!empty($reply_to)) {
    echo '
    <p><b>'.tr('Rispondi a').'</b>: '.$reply_to.'</p>';
}

echo '

    <b>'.tr('Destinatari').' <span class="tip" title="'.tr('Email delle sedi, dei referenti o agente collegato all\'anagrafica.').'"><i class="fa fa-question-circle-o"></i></span></b>
    <div class="row" id="lista-destinatari">';

$idx = 0;

foreach ($emails as $email) {
    echo '
        <div class="col-md-12">
            {[ "type": "text", "name": "destinatari['.$idx++.']", "value": "'.$email.'", "icon-before": "choice|email|'.$template['type'].'", "extra": "onkeyup=\'aggiungiDestinatario();\'", "class": "destinatari email-mask", "required": 0 ]}
        </div>';
}

if (empty($emails)) {
    echo '
        <div class="col-md-12">
            {[ "type": "text", "name": "destinatari['.$idx++.']", "value": "", "icon-before": "choice|email|'.$template['type'].'", "extra": "onkeyup=\'aggiungiDestinatario();\'", "class": "destinatari email-mask", "required": 0 ]}
        </div>';
}
echo '
    </div>
    <div class="row">
        <div class="col-md-8">
            {[ "type": "text", "label": "'.tr('Oggetto').'", "name": "subject", "value": "'.$subject.'", "required": 1 ]}
        </div>

        <div class="col-md-4">
            {[ "type": "checkbox", "label": "'.tr('Richiedi notifica di lettura').'", "name": "read_notify", "value": "'.$template['read_notify'].'" ]}
        </div>
    </div>';

// Stampe
// Recupera gli id delle stampe selezionate tramite query builder
$selected_prints = database()->table('em_print_template')->where('id_template', $template->id)->pluck('id_print')->toArray();
$selected = $selected_prints;

echo '

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "multiple": "1", "label": "'.tr('Stampe').'", "name": "prints[]", "value": "'.implode(',', $selected).'", "values": "query=SELECT `zz_prints`.`id`, `title` AS text FROM `zz_prints` LEFT JOIN `zz_prints_lang` ON (`zz_prints`.`id` = `zz_prints_lang`.`id_record` AND `zz_prints_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `id_module` = '.prepare($module->id).' AND `enabled`=1 AND `is_record`=1", "link": "stampa" ]}
        </div>';

$uploads = [];

if ($smtp['pec'] == 1 && $module->name == 'Fatture di vendita') {
    $pec_uploads = database()->table('zz_files')
        ->leftJoin('zz_files_categories', 'zz_files.id_category', '=', 'zz_files_categories.id')
        ->where('zz_files.id_module', $module->id)
        ->where('zz_files.id_record', $id_record)
        ->whereIn('zz_files_categories.name', ['Fattura Elettronica', 'Fattura elettronica'])
        ->pluck('zz_files.id')
        ->toArray();
    $uploads = array_merge($uploads, $pec_uploads);
}

$template_uploads = $template->uploads($id_record);
if (!empty($template_uploads)) {
    $uploads = array_merge($uploads, $template_uploads->pluck('id')->toArray());
}

if (empty($template->categories) && empty($uploads)) {
    $all_document_uploads = database()->table('zz_files')->where('id_module', $id_module)->where('id_record', $id_record)->pluck('id')->toArray();
    $uploads = array_merge($uploads, $all_document_uploads);
}

$uploads = array_unique($uploads);

$company_uploads_query = 'SELECT `id`, CONCAT(`name`, \' (Azienda)\') AS text FROM `zz_files` WHERE `id_module` = '.prepare(Module::where('name', 'Anagrafiche')->first()->id).' AND `id_record` = '.prepare(setting('Azienda predefinita'));

$category_ids = $template->categories->pluck('id')->toArray();
if (!empty($category_ids)) {
    $company_uploads_query .= ' AND `id_category` IN ('.implode(',', $category_ids).')';
} else {
    $company_uploads_query .= ' AND 0=1';
}

// Allegati
echo '

        <div class="col-md-6">
            {[ "type": "select", "multiple": "1", "label": "'.tr('Allegati').'", "name": "uploads[]", "value": "'.implode(',', $uploads).'", "help": "'.tr('Allegati del documento o caricati nell\'anagrafica dell\'azienda.').'", "values": "query=SELECT `id`, `name` AS text FROM `zz_files` WHERE `id_module` = '.prepare($id_module).' AND `id_record` = '.prepare($id_record).' UNION '.$company_uploads_query.'", "link": "allegato" ]}
        </div>
    </div>';

echo '
    <div class="row">
        <div class="col-md-12">';
echo input([
    'type' => 'ckeditor',
    'use_full_ckeditor' => 1,
    'label' => tr('Contenuto'),
    'name' => 'body',
    'id' => 'body_'.rand(0, 999),
    'value' => $body,
]);

echo '
        </div>
    </div>';

echo '
    <!-- PULSANTI -->
    <div class="modal-footer">
        <div class="col-md-12 text-right">
            <button type="button" class="btn btn-primary" onclick="inviaEmail()"><i class="fa fa-envelope"></i> '.tr('Invia').'</button>
        </div>
    </div>
</form>';

echo '
<div class="hidden" id="template-destinatario">
    <div class="col-md-12">
        {[ "type": "text", "name": "destinatari[-id-]", "icon-before": "choice|email|'.$template['type'].'", "extra": "onkeyup=\'aggiungiDestinatario();\'", "class": "destinatari email-mask" ]}
    </div>
</div>';

echo '
<script>
    var emails = [];
    var id_anagrafica = "'.$id_anagrafica.'";
    var pec = "'.$smtp['pec'].'";

    var id_record = "'.$id_record.'";
    var id_module = "'.$id_module.'";
    var id_template = "'.$template->id.'";

    $(document).ready(function() {
        // Auto-completamento destinatario
        if (id_anagrafica) {
            $(document).load(globals.rootdir + "/ajax_complete.php?module=Anagrafiche&op=get_email&id_anagrafica=" + id_anagrafica + "&id_record=" + id_record + "&id_module=" + id_module +  "&id_template=" + id_template + (pec ? "&type=pec" : ""), function(response) {
                emails = JSON.parse(response);
                let num = 0;
                $(".destinatari").each(function(){
                    addAutoComplete(this);
                    if (num++==0) {
                        $(this).prop("required", true);
                    }
                });

                aggiungiDestinatario();
            });
        }';

if (!empty($template['indirizzi_proposti'])) {
    echo '
            $(document).load(globals.rootdir + "/modules/emails/ajax/complete.php?op=get_email&indirizzi_proposti='.$template['indirizzi_proposti'].'", function(response) {
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
        const form = $("#email-form");

        if (form.parsley().validate() && confirm("Inviare e-mail?")) {
            form.submit();
        }
    }

    function addAutoComplete(input) {
        autocomplete({
            minLength: 0,
            input: input,
            emptyMsg: globals.translations.noResults,
            fetch: function (text, update) {
                text = text.toLowerCase();
                const suggestions = emails.filter(n => n.value.toLowerCase().startsWith(text));
                update(suggestions);
            },
            onSelect: function (item) {
            
            },
        });
    }

    function aggiungiDestinatario() {
        const last = $("#lista-destinatari input").last();

        if (last.val()) {
            const nuovaRiga = aggiungiContenuto("#lista-destinatari > div:last-of-type", "#template-destinatario", {"-id-": $("#lista-destinatari > div").length});

            nuovaRiga.find(".destinatari").each(function(){
                addAutoComplete(this);
            });
        }
    }
</script>';

echo '
<script>$(document).ready(init)</script>';
