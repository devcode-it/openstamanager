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
    case 'causali':
        $query = 'SELECT `dt_causalet`.`id`, `dt_causalet_lang`.`title` as descrizione FROM dt_causalet LEFT JOIN `dt_causalet_lang` ON (`dt_causalet`.`id` = `dt_causalet_lang`.`id_record` AND `dt_causalet_lang`.`id_lang` ='.prepare(Models\Locale::getDefault()->id).') |where| ORDER BY `title` ASC';

        foreach ($elements as $element) {
            $filter[] = '`dt_causalet`.`id`='.prepare($element);
        }
        if (empty($filter)) {
            $where[] = '`dt_causalet`.`deleted_at` IS NULL';
        }
        if (!empty($search)) {
            $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
        }

        break;
}
