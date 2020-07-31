<?php

use API\Response;

function serverError()
{
    $error = error_get_last();
    if ($error['type'] == E_ERROR) {
        ob_end_clean();
        echo Response::error('serverError');
    }
}

// Gestione degli errori
set_error_handler('serverError');
register_shutdown_function('serverError');

include_once __DIR__.'/../core.php';

// Disabilita la sessione per l'API
session_write_close();

// Permesso di accesso all'API da ogni dispositivo
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');

try {
    $response = Response::manage();
} catch (Exception $e) {
    // Log dell'errore
    $logger = logger();
    $logger->addRecord(\Monolog\Logger::ERROR, $e);

    $response = Response::error('serverError');
}

// Richiesta OPTIONS (controllo da parte del dispositivo)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    $response = Response::error('ok');
}

json_decode($response);

// Impostazioni di Content-Type e Charset Header
if (json_last_error() == JSON_ERROR_NONE) {
    header('Content-Type: application/json; charset=UTF-8');
} else {
    header('Content-Type: text/plain; charset=UTF-8');
}

// Stampa dei risultati
echo $response;
