<?php

namespace Modules\StatiContratto\API\v1;

use API\Interfaces\RetrieveInterface;
use API\Resource;

class StatiContratti extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        $table = 'co_staticontratti';

        $select = [
            '*',
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
