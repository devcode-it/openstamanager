<?php

namespace Modules\Articoli\API\v1;

use API\Interfaces\RetrieveInterface;

class Articoli implements RetrieveInterface
{
    public function retrieve($request)
    {
        $query = 'SELECT *,
            (SELECT nome FROM mg_categorie WHERE id = mg_articoli.id_categoria) AS categoria,
            (SELECT nome FROM mg_categorie WHERE id = mg_articoli.id_sottocategoria) AS sottocategoria
        FROM mg_articoli WHERE attivo = 1';

        return [
            'query' => $query,
        ];
    }
}
