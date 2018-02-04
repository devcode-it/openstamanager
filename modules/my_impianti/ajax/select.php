<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'impianti':
        if (isset($superselect['idanagrafica'])) {
            $query = 'SELECT id, CONCAT(matricola, " - ", nome) AS descrizione FROM my_impianti |where| ORDER BY idsede';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'idanagrafica='.prepare($superselect['idanagrafica']);
            $where[] = 'idsede='.prepare($superselect['idsede']);

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'matricola LIKE '.prepare('%'.$search.'%');
            }
        }
        break;
}
