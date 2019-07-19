<?php

namespace Modules\Articoli\API\v1;

use API\Interfaces\CreateInterface;
use Modules\Articoli\Articolo;

class Movimenti implements CreateInterface
{
    public function create($request)
    {
        $data = $request['data'];

        $articolo = Articolo::find($data['id_articolo']);
        $articolo->movimenta($data['qta'], $data['descrizione'], $data['data'], true);
    }
}
