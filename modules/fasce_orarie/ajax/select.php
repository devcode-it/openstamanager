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
    case 'fasce_orarie':
        $query = 'SELECT `in_fasce_orarie`.`id`, `title` AS `descrizione` FROM `in_fasce_orarie` LEFT JOIN `in_fasce_orarie_lang` ON (`in_fasce_orarie_lang`.`id_record` = `in_fasce_orarie`.`id` AND `in_fasce_orarie_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') |where| ORDER BY `title` ASC';

        foreach ($elements as $element) {
            $filter[] = '`in_fasce_orarie`.`id`='.prepare($element);
        }
        if (empty($filter)) {
            $where[] = '`in_fasce_orarie`.`deleted_at` IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
        }

        break;
}
