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
    case 'categorie_documenti':
        $query = 'SELECT `do_categorie`.`id`, `do_categorie_lang`.`title` as descrizione FROM `do_categorie` LEFT JOIN `do_categorie_lang` ON (`do_categorie_lang`.`id_record` = `do_categorie`.`id` AND `do_categorie_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')|where| ORDER BY `title` ASC';

        foreach ($elements as $element) {
            $filter[] = '`do_categorie`.`id`='.prepare($element);
        }

        if (empty($filter)) {
            $where[] = '`deleted_at` IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
        }

        $custom['link'] = 'module:Categorie documenti';

        break;
}
