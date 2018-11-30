<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'causali':
        $query = 'SELECT id, descrizione FROM dt_causalet |where| ORDER BY descrizione ASC';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
        }

        break;
}
