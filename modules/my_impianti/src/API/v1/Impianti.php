<?php

namespace Modules\Impianti\API\v1;

use API\Interfaces\RetrieveInterface;
use API\Resource;

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
