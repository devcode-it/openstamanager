<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'zone':
        $query = 'SELECT `id`, CONCAT(`nome`, \' - \', `descrizione`) AS `descrizione` FROM an_zone |where| ORDER BY descrizione ASC';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
        }

        break;
}
