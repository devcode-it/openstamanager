<?php

namespace Modules\TipiIntervento\API\v1;

use API\Interfaces\RetrieveInterface;

class TipiInterventi implements RetrieveInterface
{
    public function retrieve($request)
    {
        $table = 'in_tipiintervento';

        $select = [
            '*',
            'id' => 'idtipointervento',
        ];

        return [
            'select' => $select,
            'table' => $table,
        ];
    }
}
