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
     * - id_anagrafica
     * - stato
     */
    case 'preventivi':
        if (isset($superselect['id_anagrafica'])) {
            $query = 'SELECT
                    `co_preventivi`.`id` AS id,
                    `an_anagrafiche`.`id`,
                    CONCAT("Preventivo ", numero, " del ", DATE_FORMAT(`data_bozza`, "%d/%m/%Y"), " - ", `co_preventivi`.`nome`, " [", `co_statipreventivi_lang`.`title` , "]") AS descrizione,
                    `co_preventivi`.`id_tipo_intervento`,
                    `in_tipiintervento_lang`.`title` AS id_tipo_intervento_descrizione,
                    `in_tipiintervento`.`tempo_standard` AS tempo_standard,
                    (SELECT SUM(subtotale) FROM co_righe_preventivi WHERE idpreventivo=co_preventivi.id GROUP BY idpreventivo) AS totale,
                    (SELECT SUM(sconto) FROM co_righe_preventivi WHERE idpreventivo=co_preventivi.id GROUP BY idpreventivo) AS sconto
                FROM
                    `co_preventivi`
                    INNER JOIN `an_anagrafiche` ON `co_preventivi`.`id_anagrafica`=`an_anagrafiche`.`id`
                    INNER JOIN `co_statipreventivi` ON `co_preventivi`.`id_stato`=`co_statipreventivi`.`id`
                    LEFT JOIN `co_statipreventivi_lang` ON (`co_preventivi`.`id_stato`=`co_statipreventivi_lang`.`id_record` AND `co_statipreventivi_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
                    INNER JOIN `in_tipiintervento` ON (`co_preventivi`.`id_tipo_intervento`=`in_tipiintervento`.`id`)
                    LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id`=`in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
                |where|
                ORDER BY
                    `co_preventivi`.`id`';

            foreach ($elements as $element) {
                $filter[] = '`co_preventivi`.`id`='.prepare($element);
            }

            if (empty($elements)) {
                $where[] = '`an_anagrafiche`.`id`='.prepare($superselect['id_anagrafica']);
                $where[] = '`co_preventivi`.`default_revision`=1';

                $stati_consentiti = ['is_pianificabile', 'is_completato', 'is_fatturabile', 'is_concluso'];
                $stato = !empty($superselect['stato']) && in_array($superselect['stato'], $stati_consentiti)
                    ? $superselect['stato']
                    : 'is_pianificabile';
                $where[] = '(`'.str_replace('`', '', $stato).'` = 1)';
            }

            if (!empty($search)) {
                $search_fields[] = '`co_preventivi`.`nome` LIKE '.prepare('%'.$search.'%');
                $search_fields[] = '`co_preventivi`.`numero` LIKE '.prepare('%'.$search.'%');
                $search_fields[] = '`co_preventivi`.`data_bozza` LIKE '.prepare('%'.$search.'%');
                $search_fields[] = '`co_statipreventivi_lang`.`title` LIKE '.prepare('%'.$search.'%');
            }

            $custom['link'] = 'module:Preventivi';
        }

        break;
}
