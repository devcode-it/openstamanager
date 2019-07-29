<?php

namespace Modules\StatiIntervento\API\v1;

use API\Interfaces\RetrieveInterface;
use API\Resource;

class StatiInterventi extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        $table = 'in_statiintervento';

        $select = [
            '*',
            'id' => 'idstatointervento',
        ];

        $where = $request['where'];
        if (empty($where['deleted_at'])) {
            $where['deleted_at'] = null;
        }

        return [
            'select' => $select,
            'table' => $table,
        ];
    }
}
