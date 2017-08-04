<?php

include_once __DIR__.'/../core.php';

// Disabilta la sessione per l'API
session_write_close();

// Permesso di accesso all'API da ogni dispositivo
header('Access-Control-Allow-Origin: *');

// Attenzione: al momento l'API permette la lettura di tutte le tabelle rpesenti nel database (non limitate a quelle del progetto).

// Controlli sulla chiave di accesso
try {
    $api = new API(filter('token'));

    $resource = filter('resource');
    if (!empty($resource)) {
        $result = $api->retrieve($resource);
    } else {
        $result = API::response(API::getResources()['retrieve']);
    }
} catch (InvalidArgumentException $e) {
    $result = API::error('unauthorized');
} catch (Exception $e) {
    $result = API::error('serverError');
}

echo $result;
