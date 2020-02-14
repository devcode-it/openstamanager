<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'categorie_documenti':
        $query = 'SELECT id, descrizione FROM do_categorie |where| ORDER BY descrizione ASC';

        foreach ($elements as $element) {
            $filter[] = 'do_categorie.id='.prepare($element);
        }

        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
        }

        break;
}
