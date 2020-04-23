<?php

include_once __DIR__.'/../../core.php';

switch ($resource) {
    case 'smtp':
        $query = 'SELECT id AS id, CONCAT_WS(" - ", name, from_address ) AS descrizione FROM em_accounts |where| ORDER BY name';

        foreach ($elements as $element) {
            $filter[] = 'id = '.prepare($element);
        }
        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
        }
        if (!empty($search)) {
            $search_fields[] = 'name LIKE '.prepare('%'.$search.'%');
        }

        break;
}
