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

use Modules\Emails\Account;

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">

	<!-- DATI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title">'.tr('Dati').'</h3>
		</div>

		<div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Nome account').'", "name": "name", "value": "$name$", "required": 1 ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Indirizzo PEC').'", "name": "pec", "value": "$pec$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Indirizzo predefinito').'", "name": "predefined", "value": "$predefined$", "help": "'.tr('Account da utilizzare per l\'invio di tutte le email dal gestionale.').'" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Nome visualizzato').'", "name": "from_name", "value": "$from_name$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "text", "class": "email-mask", "label": "'.tr('Email mittente').'", "name": "from_address", "value": "$from_address$", "required": 1 ]}
                </div>

				<div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Non verificare il certificato SSL').'", "name": "ssl_no_verify", "value": "$ssl_no_verify$" ]}
                </div>


            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Server SMTP').'", "name": "server", "required": 1, "value": "$server$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "number", "label": "'.tr('Porta SMTP').'", "name": "port", "required": 1, "class": "text-center", "decimals":"0", "max-value":"65535", "value": "$port$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Sicurezza SMTP').'", "name": "encryption", "values": "list=\"\": \"'.tr('Nessuna').'\", \"tls\": \"TLS\", \"ssl\": \"SSL\"", "value": "$encryption$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Username SMTP').'", "name": "username", "value": "$username$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "password", "label": "'.tr('Password SMTP').'", "name": "password", "value": "$password$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "number", "label": "'.tr('Timeout coda di invio (millisecondi)').'", "name": "timeout", "value": "$timeout$", "decimals": 0, "min-value": 100 ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "textarea", "label": "'.tr('Note').'", "name": "note", "value": "$note$" ]}
                </div>
            </div>

        </div>
    </div>';

// Elenco provider disponibili
$providers = Account::$providers;
$elenco_provider = [];
foreach ($providers as $key => $provider) {
    $elenco_provider[] = [
        'id' => $provider['class'],
        'short' => $key,
        'text' => $provider['name'],
        'help' => $provider['help'],
    ];
}

$oauth2 = $account->oauth2;
echo '
    <!-- OAuth2 -->
    <div class="card card-info">
        <div class="card-header">
            <h3 class="card-title">'.tr('OAuth2').'</h3>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <span class="badge badge-warning pull-right hidden" id="guida-configurazione"></span>
                    {[ "type": "select", "label": "'.tr('Provider account').'", "name": "provider", "value": '.json_encode($oauth2->class).', "values": '.json_encode($elenco_provider).', "disabled": "'.intval(empty($oauth2)).'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Abilita OAuth2').'", "name": "abilita_oauth2", "value": "'.intval(!empty($oauth2)).'" ]}
                </div>

                <div class="col-md-3 oauth2-config">
                    <a type="button" class="btn btn-success btn-block '.(empty($oauth2->class) || empty($oauth2->client_id) || empty($oauth2->client_secret) ? 'disabled' : '').'" style="margin-top: 25px" href="'.base_url().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=oauth2" target="_blank">
                        <i class="fa fa-refresh"></i> '.(empty($oauth2->access_token) ? tr('Completa configurazione') : tr('Ripeti configurazione')).'
                    </a>
                </div>
            </div>

            <div class="row oauth2-config">
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Client ID').'", "name": "client_id", "value": "'.$oauth2->client_id.'", "disabled": "'.intval(empty($oauth2)).'" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Client Secret').'", "name": "client_secret", "value": "'.$oauth2->client_secret.'", "disabled": "'.intval(empty($oauth2)).'" ]}
                </div>

                <div id="provider-config"></div>
            </div>

            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> '.tr('Durante la procedura di configurazione verrà effettuato il logout dal gestionale').'.
            </div>
        </div>
    </div>
</form>';

// Inizializzazione dei form per campi personalizzati
foreach ($providers as $key => $provider) {
    echo '
<div class="hidden" id="provider-'.$key.'">';

    $config = $provider['class']::getConfigInputs();
    foreach ($config as $name => $field) {
        $field['name'] = 'config['.$name.']';
        $field['value'] = $oauth2->config ? $oauth2->config[$name] : null;

        echo '
    <div class="col-md-6">'.input($field).'</div>';
    }

    echo '
</div>';
}

echo '
<script>
var abilita_oauth2 = input("abilita_oauth2");
var provider = input("provider");
var client_id = input("client_id");
var client_secret = input("client_secret");
var guida = $("#guida-configurazione");
var config = $("#provider-config");

abilita_oauth2.change(function() {
    const disable = !abilita_oauth2.get();
    provider.setDisabled(disable);

    const inputs = $(".oauth2-config .openstamanager-input");
    for (i of inputs) {
        input(i).setDisabled(disable);
    }
});

provider.change(function() {
    const data = provider.getData();

    if (data.id) {
        guida.removeClass("hidden");
        guida.html(`<a href="${data.help}">'.tr('Istruzioni di configurazione').' <i class="fa fa-external-link"></i></a>`);
    } else {
        guida.addClass("hidden");
    }

    // Impostazione dei dati aggiuntivi da configurare
    config.html("")
    aggiungiContenuto(config, "#provider-" + data.short, {}, true);
})

$(document).ready(function() {
    provider.trigger("change");
})
</script>';

// Collegamenti diretti
// Template email collegati a questo account
$elementi = $dbo->fetchArray('SELECT `em_templates`.`id`, `em_templates_lang`.`title` FROM `em_templates` LEFT JOIN `em_templates_lang` ON (`em_templates`.`id` = `em_templates_lang`.`id_record` AND `em_templates_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `id_account` = '.prepare($id_record));

if (!empty($elementi)) {
    echo '
<div class="card card-warning collapsable collapsed-card">
    <div class="card-header with-border">
        <h3 class="card-title"><i class="fa fa-warning"></i> '.tr('Template email collegati: _NUM_', [
        '_NUM_' => count($elementi),
    ]).'</h3>
        <div class="card-tools pull-right">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <ul>';

    foreach ($elementi as $elemento) {
        echo '
            <li>'.Modules::link('Template email', $elemento['id'], $elemento['name']).'</li>';
    }

    echo '
        </ul>
    </div>
</div>';
} else {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
}
