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
    /*
     * Opzioni utilizzate:
     * - split_payment
     */
    case 'iva':
        $query = 'SELECT `co_iva`.`id`, IF( `codice_natura_fe` IS NULL, IF(`codice` IS NULL, `name`, CONCAT(`codice`, " - ", `name`)), CONCAT( IF(`codice` IS NULL, `name`, CONCAT(`codice`, " - ", `name`)), " (", `codice_natura_fe`, ")" ) ) AS descrizione, `percentuale` FROM co_iva LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(setting('Lingua')).') |where| ORDER BY `descrizione` ASC';

        foreach ($elements as $element) {
            $filter[] = '`co_iva`.`id`='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = '`name` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`codice` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`codice_natura_fe` LIKE '.prepare('%'.$search.'%');
        }

        if (empty($filter)) {
            $where[] = '`deleted_at` IS NULL';

            // se sto valorizzando un documento con lo split payment impedisco la selezione delle aliquote iva con natura N6.X (reverse charge)
            if (isset($superselect['split_payment']) and !empty($superselect['split_payment'])) {
                $where[] = '(`codice_natura_fe` IS NULL OR `codice_natura_fe` NOT LIKE "N6%")';
            }
        }

        break;
}
