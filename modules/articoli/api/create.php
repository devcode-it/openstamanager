<?php

include_once Modules::filepath('Articoli', 'modutil.php');

use Modules\Articoli\Articolo;

switch ($resource) {
    case 'movimento_articolo':
        $data = $request['data'];

        $articolo = Articolo::find($data['id_articolo']);
        $articolo->movimenta($data['qta'], $data['descrizione'], $data['data'], true);

        break;
}

return [
    'movimento_articolo',
];
