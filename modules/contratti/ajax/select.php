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
    case 'contratti':
        $query = 'SELECT `co_contratti`.`id` AS id, CONCAT("Contratto ", `numero`, " del ", DATE_FORMAT(`data_bozza`, "%d/%m/%Y"), " - ", `co_contratti`.`nome`, " [", (SELECT `name` FROM `co_staticontratti` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(setting('Lingua')).') WHERE `co_staticontratti`.`id` = `idstato`) , "]") AS descrizione, (SELECT SUM(`subtotale`) FROM `co_righe_contratti` WHERE `idcontratto`=`co_contratti`.`id`) AS totale, (SELECT SUM(`sconto`) FROM `co_righe_contratti` WHERE `idcontratto`=`co_contratti`.`id`) AS sconto, (SELECT COUNT(`id`) FROM `co_righe_contratti` WHERE `idcontratto`=`co_contratti`.`id`) AS n_righe FROM `co_contratti` INNER JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica`=`an_anagrafiche`.`idanagrafica` |where| ORDER BY `co_contratti`.`id`';

        foreach ($elements as $element) {
            $filter[] = '`co_contratti`.`id`='.prepare($element);
        }

        if (empty($elements)) {
            $where[] = '`an_anagrafiche`.`idanagrafica`='.prepare($superselect['idanagrafica']);

            $stato = !empty($superselect['stato']) ? $superselect['stato'] : 'is_pianificabile';
            $where[] = '`idstato` IN (SELECT `id` FROM `co_staticontratti` WHERE '.$stato.' = 1)';
        }

        if (!empty($search)) {
            $search_fields[] = '`co_contratti`.`nome` LIKE '.prepare('%'.$search.'%');
        }

        break;
}
