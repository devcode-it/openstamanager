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
    case 'tipi_scadenze':
        $query = 'SELECT `name` AS `id`, `description` as `descrizione` FROM `co_tipi_scadenze` LEFT JOIN `co_tipi_scadenze_lang` ON (`co_tipi_scadenze_lang`.`id_record` = `co_tipi_scadenze`.`id` AND `co_tipi_scadenze_lang`.`id_lang` = '.prepare(\App::getLang()).') |where| ORDER BY `name` ASC';

        foreach ($elements as $element) {
            $filter[] = '`co_tipi_scadenze`.`id`='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = '`name` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`description` LIKE '.prepare('%'.$search.'%');
        }

        break;
}
