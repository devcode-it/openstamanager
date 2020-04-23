<?php

namespace Modules\TipiIntervento\API\v1;

use API\Interfaces\RetrieveInterface;
use API\Resource;

class TipiInterventi extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        $table = 'in_tipiintervento';

        $select = $request['select'];
        if (empty($select)) {
            $select = [
                '*',
                'id' => 'idtipointervento',
            ];
        }

        return [
            'select' => $select,
            'table' => $table,
        ];
    }
}
