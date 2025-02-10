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

use Modules\Contratti\Contratto;

switch ($resource) {
    /*
     * Opzioni utilizzate:
     * - idanagrafica
     * - stato
     */
    case 'contratti':
        $query = 'SELECT
                `co_contratti`.`id` AS id,
                CONCAT("Contratto ", `numero`, " del ", DATE_FORMAT(`data_bozza`, "%d/%m/%Y"), " - ", `co_contratti`.`nome`, " [", (SELECT `title` FROM `co_staticontratti` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_staticontratti`.`id` = `idstato`) , "]") AS descrizione,
                (SELECT SUM(`subtotale`) FROM `co_righe_contratti` WHERE `idcontratto`=`co_contratti`.`id`) AS totale,
                (SELECT SUM(`sconto`) FROM `co_righe_contratti` WHERE `idcontratto`=`co_contratti`.`id`) AS sconto,
                (SELECT COUNT(`id`) FROM `co_righe_contratti` WHERE `idcontratto`=`co_contratti`.`id`) AS n_righe,
                `co_contratti`.`idtipointervento`,
                `in_tipiintervento_lang`.`title` AS idtipointervento_descrizione,
                `in_tipiintervento`.`tempo_standard` AS tempo_standard
            FROM
                `co_contratti` 
                INNER JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica`=`an_anagrafiche`.`idanagrafica`
                LEFT JOIN `in_tipiintervento` ON (`co_contratti`.`idtipointervento`=`in_tipiintervento`.`id`)
                LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id`=`in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
            |where| 
            ORDER BY
                `co_contratti`.`id`';

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

        $query = str_replace('|where|', !empty($where) ? 'WHERE '.implode(' AND ', $where) : '', $query);
        $rs = $dbo->fetchArray($query);

        foreach ($rs as $r) {
            $contratto = Contratto::find($r['id']);
            $ore_erogate = $contratto->interventi->sum('ore_totali');
            $ore_previste = $contratto->getRighe()->where('um', 'ore')->sum('qta');
            $perc_ore = $ore_previste != 0 ? ($ore_erogate * 100) / ($ore_previste ?: 1) : 0;

            if ($ore_previste) {
                if ($perc_ore < 75) {
                    $color = '#81f794';
                } elseif ($perc_ore <= 100) {
                    $color = '#f5cb78';
                }
            }

            $descrizione = ($ore_previste > 0 ? $r['descrizione'].' - '.tr('_EROGATE_/_PREVISTE_ ore', [
                '_EROGATE_' => Translator::numberToLocale($ore_erogate, 2),
                '_PREVISTE_' => Translator::numberToLocale($ore_previste, 2),
            ]) : $r['descrizione']);

            $results[] = [
                'id' => $r['id'],
                'text' => $descrizione,
                'descrizione' => $descrizione,
                '_bgcolor_' => $color,
                'idtipointervento' => $r['idtipointervento'],
                'idtipointervento_descrizione' => $r['idtipointervento_descrizione'],
            ];
        }

        break;

    case 'categorie_contratti':
        $query = 'SELECT `co_categorie_contratti`.`id`, `title` AS descrizione FROM `co_categorie_contratti` LEFT JOIN `co_categorie_contratti_lang` ON (`co_categorie_contratti`.`id` = `co_categorie_contratti_lang`.`id_record` AND `co_categorie_contratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') |where| ORDER BY `title`';

        foreach ($elements as $element) {
            $filter[] = '`co_categorie_contratti`.`id`='.prepare($element);
        }

        $where[] = '`parent` IS NULL';

        if (!empty($search)) {
            $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
        }

        break;

        /*
            * Opzioni utilizzate:
            * - id_categoria
            */
    case 'sottocategorie_contratti':
        if (isset($superselect['id_categoria'])) {
            $query = 'SELECT `co_categorie_contratti`.`id`, `title` AS descrizione FROM `co_categorie_contratti` LEFT JOIN `co_categorie_contratti_lang` ON (`co_categorie_contratti`.`id` = `co_categorie_contratti_lang`.`id_record` AND `co_categorie_contratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') |where| ORDER BY `title`';

            foreach ($elements as $element) {
                $filter[] = '`co_categorie_contratti`.`id`='.prepare($element);
            }

            $where[] = '`parent`='.prepare($superselect['id_categoria']);

            if (!empty($search)) {
                $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
            }
        }
        break;
}
