<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

$name = filter('name');
$value = filter('value');

switch ($name) {
    case 'codice':
        $disponibile = Articolo::where([
            ['codice', $value],
            ['id', '<>', $id_record],
        ])->count() == 0;

        $message = $disponibile ? tr('Il codice è disponbile') : tr('Il codice è già utilizzato in un altro articolo');

        $response = [
            'result' => $disponibile,
            'message' => $message,
        ];

        break;
}
