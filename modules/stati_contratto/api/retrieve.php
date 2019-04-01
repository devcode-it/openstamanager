<?php

switch ($resource) {
    // Elenco stati contratti
    case 'stati_contratto':
        $table = 'co_staticontratti';

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
    'stati_contratto',
];
