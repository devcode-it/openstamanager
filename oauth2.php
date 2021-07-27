<?php

use Models\Module;
use Modules\Emails\Account;
use Modules\Emails\OAuth2;

$skip_permissions = true;
include_once __DIR__.'/core.php';
session_write_close();

// Authorization information
$state = $_GET['state'];
$code = $_GET['code'];

// Account individuato via oauth2_state
if (!empty($state)) {
    $account = Account::where('oauth2_state', '=', $state)
        ->first();
} else {
    $account = Account::find(get('id_account'));

    // Impostazione access token a null per reimpostare la configurazione
    $account->access_token = null;
    $account->save();
}

if (empty($account)) {
    echo tr('Errore durante il completamento della configurazione: account non trovato');

    return;
}

// Inizializzazione
$oauth = new OAuth2($account);

// Redirect all'URL di autorizzazione del servizio esterno
$redirect = $oauth->configure($code, $state);

// Redirect automatico al record
if (empty($redirect)) {
    $modulo_account_email = Module::pool('Account email');
    $redirect = base_path().'/editor.php?id_module='.$modulo_account_email->id.'&id_record='.$account->id;
}

if (empty($_GET['error'])) {
    redirect($redirect);
    exit();
} else {
    echo $_GET['error'].'<br>'.$_GET['error_description'].'
<br><br>
<a href="'.$redirect.'">'.tr('Riprova').'</a>';
}
