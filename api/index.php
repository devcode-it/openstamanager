<?php

include_once __DIR__.'/../core.php';

// Disabilta la sessione per l'API
session_write_close();

function serverError()
{
    die(API::error('serverError'));
}

// Gestione degli errori
set_error_handler('serverError');

// Permesso di accesso all'API da ogni dispositivo
header('Access-Control-Allow-Origin: *');

// Attenzione: al momento l'API permette la lettura di tutte le tabelle rpesenti nel database (non limitate a quelle del progetto).

// Controlli sulla chiave di accesso
try {
    $api = new API(filter('token'));

    $resource = filter('resource');

    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'PUT':
            $result = $api->update($resource);
            break;
        case 'POST':
            $result = $api->create($resource);
            break;
        case 'GET':
            if (!empty($resource)) {
                $result = $api->retrieve($resource);
            } else {
                $result = API::response(API::getResources()['retrieve']);
            }
            break;
        case 'DELETE':
            $result = $api->delete($resource);
            break;
    }
} catch (InvalidArgumentException $e) {
    $result = API::error('unauthorized');
} catch (Exception $e) {
    $result = API::error('serverError');
}

echo $result;
