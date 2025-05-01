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
    case 'descrizioni_predefinite':
        $query = 'SELECT `zz_default_description`.`id`, `zz_default_description`.`name` as descrizione, `zz_default_description`.`descrizione` AS `descrizione_predefinita` FROM `zz_default_description` LEFT JOIN `zz_default_description_module` ON `zz_default_description`.`id` = `zz_default_description_module`.`id_description` |where| ORDER BY `name` ASC';

        foreach ($elements as $element) {
            $filter[] = '`zz_default_description`.`id`='.prepare($element);
        }
        if (empty($filter)) {
            $where[] = '`zz_default_description_module`.`id_module`='.prepare($superselect['id_module']);
        }
        if (!empty($search)) {
            $search_fields[] = '`name` LIKE '.prepare('%'.$search.'%');
        }

        break;
}
