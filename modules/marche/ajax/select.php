<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'marche':
        $query = 'SELECT `id`, `name` AS descrizione FROM `zz_marche` |where| ORDER BY `name` ASC';

        foreach ($elements as $element) {
            $filter[] = '`id`='.prepare($element);
        }

        $where[] = '`parent` IS NULL';

        if (isset($superselect['is_articolo'])) {
            $where[] = '`is_articolo` = 1';
        } elseif (isset($superselect['is_impianto'])) {
            $where[] = '`is_impianto` = 1';
        }

        if (!empty($search)) {
            $search_fields[] = '`name` LIKE '.prepare('%'.$search.'%');
        }

        $custom['link'] = 'module:Marche';

        break;

        /*
         * Opzioni utilizzate:
         * - id_marca
         */
    case 'modelli':
        if (isset($superselect['id_marca'])) {
            $query = 'SELECT `id`, `name` AS descrizione FROM `zz_marche` |where| ORDER BY `name` ASC';

            foreach ($elements as $element) {
                $filter[] = '`id`='.prepare($element);
            }

            $where[] = '`parent`='.prepare($superselect['id_marca']);

            if (isset($superselect['is_articolo'])) {
                $where[] = '`is_articolo` = 1';
            } elseif (isset($superselect['is_impianto'])) {
                $where[] = '`is_impianto` = 1';
            }

            if (!empty($search)) {
                $search_fields[] = '`name` LIKE '.prepare('%'.$search.'%');
            }

            $custom['link'] = 'module:Marche';
        }
        break;
}
