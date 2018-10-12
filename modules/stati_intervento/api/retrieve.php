<?php

switch ($resource) {
    // Elenco sedi per l'applicazione
    case 'stati_intervento':
        $table = 'in_statiintervento';

        $select = [
            '*',
            'id' => 'id_stato',
        ];

        if (empty($where['deleted_at'])) {
            $where['deleted_at'] = null;
        }

        break;
}

return [
    'stati_intervento',
];
