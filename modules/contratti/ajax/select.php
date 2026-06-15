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
     * - id_anagrafica
     * - stato
     */
    case 'contratti':
        $query = 'SELECT
                `co_contratti`.`id` AS id,
                CONCAT("Contratto ", `numero`, " del ", DATE_FORMAT(`data_bozza`, "%d/%m/%Y"), " - ", `co_contratti`.`nome`, " [", (SELECT `title` FROM `co_stati_contratti` LEFT JOIN `co_stati_contratti_lang` ON (`co_stati_contratti`.`id` = `co_stati_contratti_lang`.`id_record` AND `co_stati_contratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_stati_contratti`.`id` = `id_stato`) , "]") AS descrizione,
                (SELECT SUM(`subtotale`) FROM `co_righe_contratti` WHERE `id_contratto`=`co_contratti`.`id`) AS totale,
                (SELECT SUM(`sconto`) FROM `co_righe_contratti` WHERE `id_contratto`=`co_contratti`.`id`) AS sconto,
                (SELECT COUNT(`id`) FROM `co_righe_contratti` WHERE `id_contratto`=`co_contratti`.`id`) AS n_righe,
                `co_contratti`.`id_tipo_intervento`,
                `in_tipi_intervento_lang`.`title` AS id_tipo_intervento_descrizione,
                `in_tipi_intervento`.`tempo_standard` AS tempo_standard
            FROM
                `co_contratti`
                INNER JOIN `an_anagrafiche` ON `co_contratti`.`id_anagrafica`=`an_anagrafiche`.`id`
                LEFT JOIN `in_tipi_intervento` ON (`co_contratti`.`id_tipo_intervento`=`in_tipi_intervento`.`id`)
                LEFT JOIN `in_tipi_intervento_lang` ON (`in_tipi_intervento`.`id`=`in_tipi_intervento_lang`.`id_record` AND `in_tipi_intervento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
            |where|
            ORDER BY
                `co_contratti`.`id`';

        foreach ($elements as $element) {
            $filter[] = '`co_contratti`.`id`='.prepare($element);
        }

        if (empty($elements)) {
            $where[] = '`an_anagrafiche`.`id`='.prepare($superselect['id_anagrafica']);
            $stati_consentiti = ['is_pianificabile', 'is_bloccato', 'is_fatturabile'];
            $stato = !empty($superselect['stato']) && in_array($superselect['stato'], $stati_consentiti)
                ? $superselect['stato']
                : 'is_pianificabile';
            $where[] = '`id_stato` IN (SELECT `id` FROM `co_stati_contratti` WHERE `'.str_replace('`', '', $stato).'` = 1)';
        }

        if (!empty($search)) {
            $search_fields[] = '`co_contratti`.`nome` LIKE '.prepare('%'.$search.'%');
        }

        $data = AJAX::selectResults($query, $where, $filter, $search_fields, $limit, $custom);
        $rs = $data['results'];

        $contratti_results = [];
        foreach ($rs as $r) {
            $contratto = Contratto::find($r['id']);
            $ore_erogate = $contratto->interventi->sum('ore_totali_da_conteggiare');
            $ore_previste = $contratto->getRighe()->where('um', 'ore')->sum('qta');
            $perc_ore = $ore_previste > 0 ? ($ore_erogate * 100) / $ore_previste : 0;
            if ($ore_previste) {
                if ($perc_ore < 75) {
                    $color = '#81f794';
                } elseif ($perc_ore > 75) {
                    $color = '#f5cb78';
                }
            } else {
                $color = '';
            }

            $descrizione = ($ore_previste > 0 ? $r['descrizione'].' - '.tr('_EROGATE_/_PREVISTE_ ore', [
                '_EROGATE_' => Translator::numberToLocale($ore_erogate, 2),
                '_PREVISTE_' => Translator::numberToLocale($ore_previste, 2),
            ]) : $r['descrizione']);

            $contratti_results[] = [
                'id' => $r['id'],
                'text' => $descrizione,
                'descrizione' => $descrizione,
                '_bgcolor_' => $color,
                'id_tipo_intervento' => $r['id_tipo_intervento'],
                'id_tipo_intervento_descrizione' => $r['id_tipo_intervento_descrizione'],
            ];
        }

        $results = [
            'results' => $contratti_results,
            'recordsFiltered' => $data['recordsFiltered'],
            'link' => 'module:Contratti',
        ];

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

        $custom['link'] = 'module:Categorie contratti';

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

            $custom['link'] = 'module:Categorie contratti';
        }
        break;

        /*
         * Opzioni utilizzate:
         * - id_record
         */
    case 'tipiintervento_abilitati':
        // Try to get id_contratto from various sources
        $id_contratto = null;
        if (!empty($superselect['id_record'])) {
            $id_contratto = $superselect['id_record'];
        } elseif (!empty($superselect['tipiintervento']['id_record'])) {
            $id_contratto = $superselect['tipiintervento']['id_record'];
        } elseif (isset($id_record)) {
            $id_contratto = $id_record;
        }

        $query = 'SELECT
                    `in_tipi_intervento`.`id` AS id,
                    `in_tipi_intervento_lang`.`title` AS descrizione
                FROM
                    `co_contratti_tipi_intervento`
                    INNER JOIN `in_tipi_intervento` ON `in_tipi_intervento`.`id` = `co_contratti_tipi_intervento`.`id_tipo_intervento`
                    LEFT JOIN `in_tipi_intervento_lang` ON `in_tipi_intervento_lang`.`id_record` = `in_tipi_intervento`.`id` AND `in_tipi_intervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).'
                WHERE
                    `co_contratti_tipi_intervento`.`id_contratto` = '.prepare($id_contratto).'
                    AND `co_contratti_tipi_intervento`.`is_abilitato` = 1
                ORDER BY
                    `in_tipi_intervento_lang`.`title`';

        $where = [];
        $custom = [];

        if (!empty($search)) {
            $search_fields[] = '`in_tipi_intervento_lang`.`title` LIKE '.prepare('%'.$search.'%');
        }

        $data = AJAX::selectResults($query, $where, $filter, $search_fields, $limit, $custom);
        $rs = $data['results'];

        $results = [];
        foreach ($rs as $r) {
            $results[] = [
                'id' => $r['id'],
                'text' => $r['descrizione'],
                'descrizione' => $r['descrizione'],
            ];
        }

        $results = [
            'results' => $results,
            'recordsFiltered' => $data['recordsFiltered'],
        ];

        break;
}
