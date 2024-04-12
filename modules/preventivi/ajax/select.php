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
     * - stato
     */
    case 'preventivi':
        if (isset($superselect['idanagrafica'])) {
            $query = 'SELECT 
                    `co_preventivi`.`id` AS id, 
                    `an_anagrafiche`.`idanagrafica`, 
                    CONCAT("Preventivo ", numero, " del ", DATE_FORMAT(`data_bozza`, "%d/%m/%Y"), " - ", `co_preventivi`.`nome`, " [", `co_statipreventivi_lang`.`name` , "]") AS descrizione,
                    `co_preventivi`.`idtipointervento`,
                    `in_tipiintervento_lang`.`name` AS idtipointervento_descrizione,
                    `in_tipiintervento`.`tempo_standard` AS tempo_standard,
                    (SELECT SUM(subtotale) FROM co_righe_preventivi WHERE idpreventivo=co_preventivi.id GROUP BY idpreventivo) AS totale,
                    (SELECT SUM(sconto) FROM co_righe_preventivi WHERE idpreventivo=co_preventivi.id GROUP BY idpreventivo) AS sconto
                FROM 
                    `co_preventivi`
                    INNER JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica`=`an_anagrafiche`.`idanagrafica` 
                    INNER JOIN `co_statipreventivi` ON `co_preventivi`.`idstato`=`co_statipreventivi`.`id`
                    LEFT JOIN `co_statipreventivi_lang` ON (`co_preventivi`.`idstato`=`co_statipreventivi_lang`.`id_record` AND `co_statipreventivi_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
                    INNER JOIN `in_tipiintervento` ON (`co_preventivi`.`idtipointervento`=`in_tipiintervento`.`id`)
                    LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id`=`in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
                |where| 
                ORDER BY 
                    `co_preventivi`.`id`';

            foreach ($elements as $element) {
                $filter[] = '`co_preventivi`.`id`='.prepare($element);
            }

            if (empty($elements)) {
                $where[] = '`an_anagrafiche`.`idanagrafica`='.prepare($superselect['idanagrafica']);
                $where[] = '`co_preventivi`.`default_revision`=1';

                $stato = !empty($superselect['stato']) ? $superselect['stato'] : 'is_pianificabile';
                $where[] = '('.$stato.' = 1)';
            }

            if (!empty($search)) {
                $search_fields[] = '`nome` LIKE '.prepare('%'.$search.'%');
            }
        }

        break;
}
