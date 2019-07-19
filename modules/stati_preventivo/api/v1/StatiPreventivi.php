<?php

namespace Modules\StatiPreventivo\API\v1;

use API\Interfaces\RetrieveInterface;

class StatiPreventivi implements RetrieveInterface
{
    public function retrieve($request)
    {
        $table = 'co_statipreventivi';

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
