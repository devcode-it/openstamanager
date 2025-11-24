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
use Models\OperationLog;

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
set_error_handler(serverError(...));
register_shutdown_function(serverError(...));

include_once __DIR__.'/../core.php';

// Rate limiting per API (se abilitato)
if ($config['rate_limiting']['enabled'] ?? false) {
    [$ok] = Security\LaravelRateLimiter::enforce('api', $config);
    if (!$ok) {
        http_response_code(429);
        exit('Too Many Requests');
    }
}

// Permesso di accesso all'API da ogni dispositivo
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');

try {
    $response = Response::manage();
    $info = Response::getInfo();
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
$level = ($result['status'] == '200' ? 'info' : 'error');
$type = ['GET' => 'retrieve', 'POST' => 'create', 'PUT' => 'update', 'DELETE' => 'delete'];

// Ricavo l'id della richiesta API
$api = $dbo->table('zz_api_resources')
    ->where('resource', $info['resource'])
    ->where('type', $type[$_SERVER['REQUEST_METHOD']])
    ->first();

// Salvataggio del log dell'operazione
OperationLog::setInfo('id_module', $info['id_module']);
OperationLog::setInfo('id_api', $api->id);
OperationLog::setInfo('level', $level);

// Aggiungo il contenuto della richiesta
$context = [
    'token' => get('token'),
    'resource' => get('resource'),
    'filter' => get('filter'),
    'total-count' => $result['total-count'],
];
OperationLog::setInfo('context', json_encode($context));

// Aggiungo al log il messaggio completo di risposta
if (($result['status'] == '200' && setting('Log risposte API') == 'debug') || $result['status'] != '200') {
    $message = json_encode($result);
    OperationLog::setInfo('message', $message);
}

// Salvo l'id_record se presente nella risposta
if (!empty($result['id'])) {
    OperationLog::setInfo('id_record', $result['id']);
}

$op = ($result['op'] ?: $type[$_SERVER['REQUEST_METHOD']]);
$result['op'] = ($op == 'create' ? 'add' : ($op == 'retrieve' ? 'read' : $op));
OperationLog::build($result['op']);

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
