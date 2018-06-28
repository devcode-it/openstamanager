<?php

function serverError()
{
    $error = error_get_last();
    if ($error['type'] == E_ERROR) {
        ob_end_clean();
        echo API::error('serverError');
    }
}

// Impostazioni del Content-Type
header('Content-Type: application/json; charset=UTF-8');

// Gestione degli errori
set_error_handler('serverError');
register_shutdown_function('serverError');

include_once __DIR__.'/../core.php';

// Disabilta la sessione per l'API
session_write_close();

// Permesso di accesso all'API da ogni dispositivo
header('Access-Control-Allow-Origin: *');

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
            $result = $api->update($request);
            break;

        // Richiesta POST (creazione elementi)
        case 'POST':
            $result = $api->create($request);
            break;

        // Richiesta GET (ottenimento elementi)
        case 'GET':
            // Risorsa specificata
            if (count($request) > 1) {
                $result = $api->retrieve($request);
            }

            // Risorsa non specificata (lista delle risorse disponibili)
            else {
                $result = API::response(API::getResources()['retrieve']);
            }
            break;

        // Richiesta DELETE (eliminazione elementi)
        case 'DELETE':
            $result = $api->delete($request);
            break;
    }
} catch (InvalidArgumentException $e) {
    $result = API::error('unauthorized');
} catch (Exception $e) {
    $result = API::error('serverError');
}

// Stampa dei risultati
echo $result;
