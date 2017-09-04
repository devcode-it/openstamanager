<?php

switch ($resource) {
    case 'an_anagrafiche':
        $table = 'an_anagrafiche';

        if (empty($order)) {
            $order[] = 'idanagrafica';
        }

        if(empty($where['deleted'])){
            $where['deleted'] = 0;
        }

        break;
}

return [
    'an_anagrafiche',
];
