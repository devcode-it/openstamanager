<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'tipi_scadenze':
        $query = 'SELECT nome AS id, descrizione FROM co_tipi_scadenze |where| ORDER BY nome ASC';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
        }

        break;
}
