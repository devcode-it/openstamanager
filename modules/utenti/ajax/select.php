<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'anagrafiche_utenti':
        $query = 'SELECT `an_anagrafiche`.`id` AS id, `an_anagrafiche`.`ragione_sociale` AS descrizione, `an_tipi_anagrafiche_lang`.`title` AS optgroup FROM `an_tipi_anagrafiche` LEFT JOIN `an_tipi_anagrafiche_lang` ON (`an_tipi_anagrafiche`.`id`=`an_tipi_anagrafiche_lang`.`id_record` AND `an_tipi_anagrafiche_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') INNER JOIN `an_tipi_anagrafiche_anagrafiche` ON `an_tipi_anagrafiche`.`id`=`an_tipi_anagrafiche_anagrafiche`.`id_tipo_anagrafica` INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`id`=`an_tipi_anagrafiche_anagrafiche`.`id_anagrafica` |where| ORDER BY `optgroup` ASC';

        $where[] = '`an_anagrafiche`.`deleted_at` IS NULL';

        foreach ($elements as $element) {
            $filter[] = '`an_anagrafiche`.`id`='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = '`an_anagrafiche`.`ragione_sociale` LIKE '.prepare('%'.$search.'%');
        }

        if (!empty($search_fields)) {
            $where[] = '('.implode(' OR ', $search_fields).')';
        }

        if (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        $wh = '';
        if (count($where) != 0) {
            $wh = 'WHERE '.implode(' AND ', $where);
        }
        $query = str_replace('|where|', $wh, $query);

        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            if ($prev != $r['optgroup']) {
                $results[] = ['text' => $r['optgroup'], 'children' => []];
                $prev = $r['optgroup'];
            }

            $results[count($results) - 1]['children'][] = [
                'id' => $r['id'],
                'text' => $r['descrizione'],
                'descrizione' => $r['descrizione'],
            ];
        }

        $custom['link'] = 'module:Anagrafiche';

        break;

    case 'utenti':
        $query = "SELECT `zz_users`.`id` AS id, if(`an_anagrafiche`.`id` IS NOT NULL, CONCAT(`an_anagrafiche`.`ragione_sociale`, ' (', `zz_users`.`username`, ')'), `zz_users`.`username`) AS descrizione, `an_tipi_anagrafiche_lang`.`title` AS optgroup
        FROM
            `zz_users`
            LEFT JOIN `an_anagrafiche` ON `an_anagrafiche`.`id` = `zz_users`.`id_anagrafica`
            INNER JOIN `an_tipi_anagrafiche_anagrafiche` ON `an_anagrafiche`.`id`=`an_tipi_anagrafiche_anagrafiche`.`id_anagrafica`
            INNER JOIN `an_tipi_anagrafiche` ON `an_tipi_anagrafiche`.`id`=`an_tipi_anagrafiche_anagrafiche`.`id_tipo_anagrafica`
            LEFT JOIN `an_tipi_anagrafiche_lang` ON (`an_tipi_anagrafiche`.`id`=`an_tipi_anagrafiche_lang`.`id_record` AND `an_tipi_anagrafiche_lang`.`id_lang`=".prepare(Models\Locale::getDefault()->id).')
        |where|
        ORDER BY
            `optgroup` ASC';

        $where[] = '`an_anagrafiche`.`deleted_at` IS NULL';

        foreach ($elements as $element) {
            $filter[] = '`zz_users`.`id`='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = '`an_anagrafiche`.`ragione_sociale` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`zz_users`.`username` LIKE '.prepare('%'.$search.'%');
        }

        if (!empty($search_fields)) {
            $where[] = '('.implode(' OR ', $search_fields).')';
        }

        if (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        $wh = '';
        if (count($where) != 0) {
            $wh = 'WHERE '.implode(' AND ', $where);
        }
        $query = str_replace('|where|', $wh, $query);

        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            if ($prev != $r['optgroup']) {
                $results[] = ['text' => $r['optgroup'], 'children' => []];
                $prev = $r['optgroup'];
            }

            $results[count($results) - 1]['children'][] = [
                'id' => $r['id'],
                'text' => $r['descrizione'],
                'descrizione' => $r['descrizione'],
            ];
        }

        break;

    case 'gruppi':
        $query = 'SELECT `zz_groups`.`id`, `zz_groups_lang`.`title` AS descrizione FROM `zz_groups` LEFT JOIN `zz_groups_lang` ON `zz_groups`.`id`=`zz_groups_lang`.`id_record` AND `zz_groups_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).' |where| ORDER BY `title`';

        foreach ($elements as $element) {
            $filter[] = '`zz_groups`.`id`='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = '`zz_groups_lang`.`title` LIKE '.prepare('%'.$search.'%');
        }

        $custom['link'] = 'module:Utenti e permessi';

        break;

    case 'moduli_gruppo':
        $query = 'SELECT `zz_modules`.`id`, `zz_modules_lang`.`title` AS descrizione FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON `zz_modules`.`id`=`zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).' LEFT JOIN `zz_permissions` ON `zz_permissions`.`id_module`=`zz_modules`.`id` |where| GROUP BY `zz_modules`.`id` ORDER BY `title`';

        $where[] = '`zz_modules`.`enabled`=1';

        if (isset($superselect['id_gruppo']) && $superselect['id_gruppo'] != 1) {
            $where[] = '`zz_permissions`.`id_gruppo`='.prepare($superselect['id_gruppo']);
        }

        foreach ($elements as $element) {
            $filter[] = '`zz_modules`.`id`='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = '`zz_modules_lang`.`title` LIKE '.prepare('%'.$search.'%');
        }

        $custom['link'] = '';

        break;
}
