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

include_once __DIR__.'/../../core.php';

switch ($resource) {
    case 'smtp':
        $query = 'SELECT id AS id, CONCAT_WS(" - ", name, from_address ) AS descrizione FROM em_accounts |where| ORDER BY name';

        foreach ($elements as $element) {
            $filter[] = 'id = '.prepare($element);
        }
        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
        }
        if (!empty($search)) {
            $search_fields[] = 'name LIKE '.prepare('%'.$search.'%');
        }

        break;
}
