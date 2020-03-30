<?php

namespace Modules\Impianti\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\DeleteInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Modules;
use Modules\Anagrafiche\Anagrafica;

class Impianti extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        $query = 'SELECT id, idanagrafica, matricola, nome, descrizione FROM my_impianti';

        return [
            'query' => $query,
        ];
    }
}
