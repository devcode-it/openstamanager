<?php

namespace Modules\Articoli\API\v1;

use API\Interfaces\CreateInterface;
use API\Resource;
use Modules\Articoli\Articolo;

class Movimenti extends Resource implements CreateInterface
{
    public function create($request)
    {
        $data = $request['data'];

        $articolo = Articolo::find($data['id_articolo']);
        $articolo->movimenta($data['qta'], $data['descrizione'], $data['data'], true);
    }
}
