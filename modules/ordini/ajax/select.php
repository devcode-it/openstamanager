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
     */
    case 'ordini-cliente':
        if (isset($superselect['id_anagrafica'])) {
            $query = 'SELECT
                `or_ordini`.`id` AS id,
                CONCAT("Ordine ", `numero_esterno`, " del ", DATE_FORMAT(data, "%d/%m/%Y"), " [", `or_stati_ordine_lang`.`title` , "]") AS descrizione
            FROM
                `or_ordini`
                INNER JOIN `or_tipi_ordine` ON `or_ordini`.`id_tipo_ordine` = `or_tipi_ordine`.`id`
                INNER JOIN `an_anagrafiche` ON `or_ordini`.`id_anagrafica` = `an_anagrafiche`.`id`
                INNER JOIN `or_stati_ordine` ON `or_ordini`.`id_stato` = `or_stati_ordine`.`id`
                LEFT JOIN `or_stati_ordine_lang` ON (`or_stati_ordine_lang`.`id_record` = `or_stati_ordine`.`id` AND `or_stati_ordine_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
            |where|
            ORDER BY
                `or_ordini`.`id`';

            foreach ($elements as $element) {
                $filter[] = '`or_ordini`.`id`='.prepare($element);
            }

            $where[] = '`or_tipi_ordine`.`dir`='.prepare('entrata');
            if (empty($elements)) {
                $where[] = '`an_anagrafiche`.`id`='.prepare($superselect['id_anagrafica']);

                $stati_consentiti = ['is_fatturabile', 'is_evadibile', 'is_bloccato'];
                $stato = !empty($superselect['stato']) && in_array($superselect['stato'], $stati_consentiti)
                    ? $superselect['stato']
                    : 'is_fatturabile';
                $where[] = '`or_stati_ordine`.'.$stato.' = 1';
                $where[] = '`or_stati_ordine`.`'.str_replace('`', '', $stato).'` = 1';
            }
        }

        $custom['link'] = 'module:Ordini cliente';

        break;
}
