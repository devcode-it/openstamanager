<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'iva':
        $query = 'SELECT id, IF(codice IS NULL, descrizione, CONCAT(codice, " - ", descrizione)) AS descrizione FROM co_iva |where| ORDER BY descrizione ASC';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'codice LIKE '.prepare('%'.$search.'%');
        }

        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
        }

        break;
}
