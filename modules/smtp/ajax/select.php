<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'smtp':
        $query = 'SELECT id AS id, name AS descrizione FROM zz_smtp |where| ORDER BY name';

        foreach ($elements as $element) {
            $filter[] = 'id = '.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = 'name LIKE '.prepare('%'.$search.'%');
        }

        break;
}
