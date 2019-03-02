<?php

function serverError()
{
    $error = error_get_last();
    if ($error['type'] == E_ERROR) {
        ob_end_clean();
        echo API::error('serverError');
    }
}

// Gestione degli errori
set_error_handler('serverError');
register_shutdown_function('serverError');

// Disabilta la sessione per l'API
session_write_close();

// Permesso di accesso all'API da ogni dispositivo
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');

// Attenzione: al momento l'API permette la lettura di tutte le tabelle presenti nel database (non limitate a quelle del progetto)

try {
    // Controlli sulla chiave di accesso
    $api = new API();

    // Lettura delle informazioni
    $request = API::getRequest();

    // Gestione della richiesta
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        // Richiesta PUT (modifica elementi)
        case 'PUT':
            $response = $api->update($request);
            break;

        // Richiesta POST (creazione elementi)
        case 'POST':
            $response = $api->create($request);
            break;

        // Richiesta GET (ottenimento elementi)
        case 'GET':
            // Risorsa specificata
            if (count($request) > 1) {
                $response = $api->retrieve($request);
            }

            // Risorsa non specificata (lista delle risorse disponibili)
            else {
                $response = API::response([
                    'resources' => array_keys(API::getResources()['retrieve']),
                ]);
            }
            break;

        // Richiesta DELETE (eliminazione elementi)
        case 'DELETE':
            $response = $api->delete($request);
            break;
    }
} catch (InvalidArgumentException $e) {
    $response = API::error('unauthorized');
} catch (Exception $e) {
    // Log dell'errore
    $logger = logger();
    $logger->addRecord(\Monolog\Logger::ERROR, $e);

    $response = API::error('serverError');
}

// Richiesta OPTIONS (controllo da parte del dispositivo)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    $response = API::error('ok');
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
