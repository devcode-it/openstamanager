<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'marchi':
        $query = 'SELECT `id`, `name` FROM `mg_marchi` |where| ORDER BY `name` ASC';

        foreach ($elements as $element) {
            $filter[] = '`id`='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = '`name` LIKE '.prepare('%'.$search.'%');
        }

        break;
}
