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
session_write_close();

// Authorization information
$state = $_GET['state'];
$code = $_GET['code'];

// Account individuato via state
if (!empty($state)) {
    $account = OAuth2::where('state', '=', $state)
        ->first();
} else {
    $account = OAuth2::find(get('id'));

    // Impostazione access token a null per reimpostare la configurazione
    $account->access_token = null;
    $account->refresh_token = null;
    $account->save();
}

if (empty($account)) {
    echo tr('Errore durante il completamento della configurazione: account non trovato');

    return;
}

// Redirect all'URL di autorizzazione del servizio esterno
$redirect = $account->configure($code, $state);

// Redirect automatico al record
if (empty($redirect)) {
    $redirect = $account->after_configuration;
}

if (empty($_GET['error'])) {
    redirect_legacy($redirect);
    throw new \LegacyExitException();;
} else {
    echo $_GET['error'].'<br>'.$_GET['error_description'].'
<br><br>
<a href="'.$redirect.'">'.tr('Riprova').'</a>';
}
