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

use Models\OAuth2;

$skip_permissions = true;
include_once __DIR__.'/core.php';

// Authorization information
$state = $_GET['state'];
$code = $_GET['code'];

// Account individuato via state
if (!empty($state)) {
    $account = OAuth2::find($_SESSION['oauth2_id'])
        ->first();
} else {
    $account = OAuth2::find(get('id'));

    // Impostazione access token a null per reimpostare la configurazione
    $account->access_token = null;
    $account->refresh_token = null;
    $account->save();
    $_SESSION['oauth2_id'] = $account->id;
}

if (empty($account)) {
    echo tr('Errore durante il completamento della configurazione: account non trovato');

    return;
}

// Redirect all'URL di autorizzazione del servizio esterno
$response = $account->configure($code, $state);

// Redirect automatico al record
if (empty($response['authorization_url'])) {
    $redirect = $account->after_configuration;
} else {
    $redirect = $response['authorization_url'];
}

if (empty($_GET['error'])) {
    if ($response['access_token']) {
        $username = $account->getProvider()->getUser($response['access_token']);

        if (!auth()->attempt($username, null, true)) {
            flash()->error(tr('Autenticazione fallita!'));
        }
        redirect(base_path().'/');
    } else {
        redirect($redirect);
    }

    exit;
} else {
    echo strip_tags($_GET['error']).'<br>'.strip_tags($_GET['error_description']).'
<br><br>
<a href="'.$redirect.'">'.tr('Riprova').'</a>';
}
