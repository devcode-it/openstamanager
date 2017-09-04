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

include_once __DIR__.'/../core.php';

// Disabilta la sessione per l'API
session_write_close();

// Permesso di accesso all'API da ogni dispositivo
header('Access-Control-Allow-Origin: *');

// Attenzione: al momento l'API permette la lettura di tutte le tabelle rpesenti nel database (non limitate a quelle del progetto).

try {
    // Controlli sulla chiave di accesso
    $api = new API();

    // Lettura delle informazioni
    $request = API::getRequest();

    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'PUT':
            $result = $api->update($request);
            break;
        case 'POST':
            $result = $api->create($request);
            break;
        case 'GET':
            if (!empty($request)) {
                $result = $api->retrieve($request);
            } else {
                $result = API::response(API::getResources()['retrieve']);
            }
            break;
        case 'DELETE':
            $result = $api->delete($request);
            break;
    }
} catch (InvalidArgumentException $e) {
    $result = API::error('unauthorized');
} catch (Exception $e) {
    $result = API::error('serverError');
}

echo $result;
