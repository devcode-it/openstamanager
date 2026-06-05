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
    case 'attributi':
        $query = 'SELECT `mg_attributi`.`id`, `mg_attributi_lang`.`title` AS descrizione FROM `mg_attributi` LEFT JOIN `mg_attributi_lang` ON (`mg_attributi`.`id` = `mg_attributi_lang`.`id_record` AND `mg_attributi_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') |where| ORDER BY `descrizione` ASC';

        $where[] = '`mg_attributi`.`deleted_at` IS NULL';

        if (!empty($superselect['exclude_ids'])) {
            $exclude_ids = is_array($superselect['exclude_ids']) ? $superselect['exclude_ids'] : explode(',', $superselect['exclude_ids']);
            $exclude_ids = array_map('intval', $exclude_ids);
            if (!empty($exclude_ids)) {
                $where[] = '`mg_attributi`.`id` NOT IN ('.implode(',', $exclude_ids).')';
            }
        }

        foreach ($elements as $element) {
            $filter[] = '`mg_attributi`.`id`='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = '`mg_attributi_lang`.`title` LIKE '.prepare('%'.$search.'%');
        }

        break;

    case 'valori_attributi':
        $query = 'SELECT `mg_valori_attributi`.`id`, `mg_valori_attributi`.`nome` AS descrizione FROM `mg_valori_attributi` |where| ORDER BY `nome` ASC';

        $where[] = '`mg_valori_attributi`.`deleted_at` IS NULL';

        if (isset($superselect['id_attributo'])) {
            $where[] = '`mg_valori_attributi`.`id_attributo` = '.prepare($superselect['id_attributo']);
        }

        foreach ($elements as $element) {
            $filter[] = '`mg_valori_attributi`.`id`='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = '`mg_valori_attributi`.`nome` LIKE '.prepare('%'.$search.'%');
        }

        break;
}
