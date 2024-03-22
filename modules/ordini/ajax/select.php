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
     * - idanagrafica
     */
    case 'ordini-cliente':
        if (isset($superselect['idanagrafica'])) {
            $query = 'SELECT 
                `or_ordini`.`id` AS id,
                CONCAT("Ordine ", `numero_esterno`, " del ", DATE_FORMAT(data, "%d/%m/%Y"), " [", `or_statiordine_lang`.`name` , "]") AS descrizione
            FROM 
                `or_ordini`
                INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
                INNER JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
                INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id`
                LEFT JOIN `or_statiordine_lang` ON (`or_statiordine_lang`.`id_record` = `or_statiordine`.`id` AND `or_statiordine_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
            |where|
            ORDER BY 
                `or_ordini`.`id`';

            foreach ($elements as $element) {
                $filter[] = '`or_ordini`.`id`='.prepare($element);
            }

            $where[] = '`or_tipiordine`.`dir`='.prepare('entrata');
            if (empty($elements)) {
                $where[] = '`an_anagrafiche`.`idanagrafica`='.prepare($superselect['idanagrafica']);

                $stato = !empty($superselect['stato']) ? $superselect['stato'] : 'is_fatturabile';
                $where[] = '`or_statiordine`.'.$stato.' = 1)';
            }
        }

        break;
}
