<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'categorie_documenti':
        $query = 'SELECT id, descrizione FROM zz_documenti_categorie |where| ORDER BY descrizione ASC';

        foreach ($elements as $element) {
            $filter[] = 'zz_documenti_categorie.id='.prepare($element);
        }

        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
        }

        break;
}
