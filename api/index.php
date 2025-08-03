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

use API\Response;

function serverError()
{
    $error = error_get_last();
    if (isset($error['type'])) {
        if ($error['type'] == E_ERROR) {
            ob_end_clean();
            echo Response::error('serverError');
        }
    }
}

// Gestione degli errori
set_error_handler('serverError');
register_shutdown_function('serverError');

include_once __DIR__.'/../core.php';

// Permesso di accesso all'API da ogni dispositivo
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');

$dbo->insert('zz_api_log', [
    'level' => 'warning',
    'message' => 'Richiesta API ricevuta',
    'name' => "API ".get('resource'),
    'context' => json_encode([
        'token' => get('token'),
        'resource' => get('resource'),
    ]),
]);
$id_log = $dbo->lastInsertedID();

try {
    $response = Response::manage();
} catch (Exception $e) {
    // Log dell'errore
    $logger = logger();
    $logger->addRecord(Monolog\Logger::ERROR, $e);

    $response = Response::error('serverError');
}

// Richiesta OPTIONS (controllo da parte del dispositivo)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    $response = Response::error('ok');
}

$result = json_decode((string) $response, true);
$level = ($result['status']=='200' ? 'info' : 'error');
$dbo->update('zz_api_log', [
    'level' => $level,
    'message' => $result['message'],
    'context' => json_encode([
        'token' => get('token'),
        'resource' => get('resource'),
        'total-count' => $result['total-count'],
    ]),
],['id' => $id_log]);

json_decode((string) $response);

// Impostazioni di Content-Type e Charset Header
if (json_last_error() == JSON_ERROR_NONE) {
    header('Content-Type: application/json; charset=UTF-8');
} else {
    header('Content-Type: text/plain; charset=UTF-8');
}

// Stampa dei risultati
echo $response;

Auth::logout();
