<?php

switch ($resource) {
    // Elenco stati preventivi
    case 'stati_preventivo':
        $table = 'co_statipreventivi';

        $select = [
            '*',
            'id' => 'id',
        ];

        if (empty($where['deleted_at'])) {
            $where['deleted_at'] = null;
        }

        break;
}

return [
    'stati_preventivo',
];
