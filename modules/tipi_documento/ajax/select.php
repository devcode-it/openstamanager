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
    case 'tipi_documento':
        $query = 'SELECT `co_tipidocumento`.`id`, `co_tipidocumento_lang`.`title` AS descrizione FROM `co_tipidocumento` |where| ORDER BY `title` ASC';

        $where[] = '`co_tipidocumento`.`enabled` = 1';
        $where[] = '`dir`='.$superselect['dir'];

        foreach ($elements as $element) {
            $filter[] = '`id`='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
        }

        $custom['link'] = 'module:Tipi documento';

        break;
}
