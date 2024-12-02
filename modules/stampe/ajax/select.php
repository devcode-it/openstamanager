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
    case 'allegati':
        $id_module = $superselect['id_module'];
        $id_plugin = $superselect['id_plugin'];
        $id_record = $superselect['id_record'];

        if (isset($id_module) || isset($id_plugin)) {
            $query = 'SELECT `id`, `title` AS descrizione FROM zz_files |where|';

            if (isset($id_module)) {
                $where[] = '`zz_files`.`id_module` = '.prepare($id_module);
            }
            if (isset($id_plugin)) {
                $where[] = '`zz_files`.`id_plugin` = '.prepare($id_plugin);
            }
            $where[] = '`zz_files`.`id_record` = '.prepare($id_record);

            if ($is_fiscale != null) {
                $where[] = '`zz_segments`.`is_fiscale` = '.prepare($is_fiscale);
            }

            if ($is_sezionale != null) {
                $where[] = '`zz_segments`.`is_sezionale` = '.prepare($is_sezionale);
            }

            if ($escludi_id != null) {
                $where[] = '`zz_segments`.`id` != '.prepare($escludi_id);
            }

            foreach ($elements as $element) {
                $filter[] = '`id`='.prepare($element);
            }

            if (!empty($search)) {
                $search_fields[] = '`zz_segments`.`title` LIKE '.prepare('%'.$search.'%');
            }
        }

        break;
}
