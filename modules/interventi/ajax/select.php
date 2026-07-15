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

use Modules\Interventi\Intervento;

switch ($resource) {
    case 'tipiintervento':
        // Verifica se è presente un contratto collegato
        $id_contratto = $superselect['id_contratto'] ?? null;
        $id_intervento = $superselect['id_intervento'] ?? null;

        if (!empty($id_contratto)) {
            // Verifica se il contratto ha righe con tipi di intervento specificati
            $righe_contratto_count = database()->table('co_righe_contratti')->where('id_contratto', $id_contratto)->whereNotNull('id_tipo_intervento')->count();

            if ($righe_contratto_count > 0) {
                // Se il contratto ha righe con tipi di intervento: mostra SOLO tipi presenti nelle righe del contratto
                $query = 'SELECT DISTINCT `in_tipi_intervento`.`id`, CASE WHEN ISNULL(`tempo_standard`) OR `tempo_standard` <= 0 THEN CONCAT(`codice`, \' - \', `title`, IF(`in_tipi_intervento`.`deleted_at` IS NULL, "", " ('.tr('eliminato').')")) WHEN `tempo_standard` > 0 THEN CONCAT(`codice`, \' - \', `title`, \' (\', REPLACE(FORMAT(`tempo_standard`, 2), \'.\', \',\'), \' ore)\', IF(`in_tipi_intervento`.`deleted_at` IS NULL, "", " ('.tr('eliminato').')")) END AS descrizione, `tempo_standard`
                    FROM `in_tipi_intervento`
                    LEFT JOIN `in_tipi_intervento_lang` ON (`in_tipi_intervento`.`id` = `in_tipi_intervento_lang`.`id_record` AND `in_tipi_intervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                    INNER JOIN `co_righe_contratti` ON `in_tipi_intervento`.`id` = `co_righe_contratti`.`id_tipo_intervento` AND `co_righe_contratti`.`id_contratto` = '.prepare($id_contratto).'
                    |where|
                    ORDER BY `title`';
            } else {
                // Se il contratto non ha righe con tipi di intervento: mostra i tipi abilitati per il contratto
                $query = 'SELECT `in_tipi_intervento`.`id`, CASE WHEN ISNULL(`tempo_standard`) OR `tempo_standard` <= 0 THEN CONCAT(`codice`, \' - \', `title`, IF(`in_tipi_intervento`.`deleted_at` IS NULL, "", " ('.tr('eliminato').')")) WHEN `tempo_standard` > 0 THEN CONCAT(`codice`, \' - \', `title`, \' (\', REPLACE(FORMAT(`tempo_standard`, 2), \'.\', \',\'), \' ore)\', IF(`in_tipi_intervento`.`deleted_at` IS NULL, "", " ('.tr('eliminato').')")) END AS descrizione, `tempo_standard`
                    FROM `in_tipi_intervento`
                    LEFT JOIN `in_tipi_intervento_lang` ON (`in_tipi_intervento`.`id` = `in_tipi_intervento_lang`.`id_record` AND `in_tipi_intervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                    INNER JOIN `co_contratti_tipi_intervento` ON `in_tipi_intervento`.`id` = `co_contratti_tipi_intervento`.`id_tipo_intervento` AND `co_contratti_tipi_intervento`.`id_contratto` = '.prepare($id_contratto).' AND `co_contratti_tipi_intervento`.`is_abilitato` = 1
                    |where|
                    ORDER BY `title`';
            }
        } else {
            // Altrimenti mostra tutti i tipi di intervento
            $query = 'SELECT `in_tipi_intervento`.`id`, CASE WHEN ISNULL(`tempo_standard`) OR `tempo_standard` <= 0 THEN CONCAT(`codice`, \' - \', `title`, IF(`in_tipi_intervento`.`deleted_at` IS NULL, "", " ('.tr('eliminato').')")) WHEN `tempo_standard` > 0 THEN CONCAT(`codice`, \' - \', `title`, \' (\', REPLACE(FORMAT(`tempo_standard`, 2), \'.\', \',\'), \' ore)\', IF(`in_tipi_intervento`.`deleted_at` IS NULL, "", " ('.tr('eliminato').')")) END AS descrizione, `tempo_standard` 
            FROM `in_tipi_intervento`
            LEFT JOIN `in_tipi_intervento_lang` ON (`in_tipi_intervento`.`id` = `in_tipi_intervento_lang`.`id_record` AND `in_tipi_intervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
            |where| 
            ORDER BY `title`';
        }

        foreach ($elements as $element) {
            $filter[] = '`in_tipi_intervento`.`id`='.prepare($element);
        }

        // Applica il filtro deleted_at sempre, tranne quando si filtrano elementi specifici
        if (empty($filter)) {
            $where[] = '`in_tipi_intervento`.`deleted_at` IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
        }

        if (!empty($superselect['id_tipi_intervento'])) {
            $ids = array_map('intval', (array) $superselect['id_tipi_intervento']);
            $where[] = '`in_tipi_intervento`.`id` IN ('.implode(',', $ids).')';
        } elseif (!empty($superselect['id_anagrafica'])) {
            $ids_tipo_intervento = database()->table('an_anagrafiche_tipi_intervento')->where('id_anagrafica', $superselect['id_anagrafica'])->pluck('id_tipo_intervento')->toArray();
            if (sizeof($ids_tipo_intervento) > 0) {
                $filter[] = '`in_tipi_intervento`.`id` IN('.implode(',', $ids_tipo_intervento).')';
            }
        }

        $data = AJAX::selectResults($query, $where, $filter, $search_fields, $limit, $custom);
        $rs = $data['results'];

        // Pre-carica i dati delle anagrafiche e dei tipi intervento
        $anagrafica_tipo = database()->table('an_anagrafiche')->where('id', $superselect['id_anagrafica'] ?? null)->value('tipo');
        
        $tipi_intervento_ids = array_column($rs, 'id');
        $tipologie_per_tipo = [];
        if (!empty($tipi_intervento_ids)) {
            $tipologie = database()->table('in_tipi_intervento_tipologie')->whereIn('id_tipo_intervento', $tipi_intervento_ids)->get(['id_tipo_intervento', 'tipo']);
            foreach ($tipologie as $tipologia) {
                $tipologie_per_tipo[$tipologia->id_tipo_intervento][] = $tipologia->tipo;
            }
        }

        $gruppi_per_tipo = [];
        if (!empty($tipi_intervento_ids)) {
            $gruppi = database()->table('in_tipi_intervento_groups')->whereIn('id_tipo_intervento', $tipi_intervento_ids)->get(['id_tipo_intervento', 'id_gruppo']);
            foreach ($gruppi as $gruppo) {
                $gruppi_per_tipo[$gruppo->id_tipo_intervento][] = $gruppo->id_gruppo;
            }
        }

        foreach ($rs as $k => $r) {
            $disabled = false;

            // Controllo se il tipo intervento è compatibile con la tipologia dell'anagrafica
            if (!empty($superselect['id_anagrafica'])) {
                // Ottengo le tipologie associate al tipo intervento
                $tipologie_tipo_intervento = $tipologie_per_tipo[$r['id']] ?? [];
                
                $tipologie_tipo_intervento = array_column($tipologie_tipo_intervento, 'tipo');
                if (!empty($tipologie_tipo_intervento)) {
                    // Controllo se la tipologia dell'anagrafica è presente nelle tipologie del tipo intervento
                    $compatibile = in_array($anagrafica_tipo, $tipologie_tipo_intervento);
                    if (!$compatibile) {
                        $disabled = true;
                    }
                }
            }

            // Controllo se il tipo intervento è compatibile con il gruppo utente
            $gruppi_tipo_intervento = $gruppi_per_tipo[$r['id']] ?? [];
            if (!empty($gruppi_tipo_intervento)) {
                $compatibile = in_array($id_gruppo, $gruppi_tipo_intervento);
                if (!$compatibile) {
                    $disabled = true;
                }
            }

            $rs[$k] = array_merge($r, [
                'text' => $r['descrizione'],
                'disabled' => $disabled,
            ]);
        }

        $results = [
            'results' => $rs,
            'recordsFiltered' => $data['recordsFiltered'],
        ];

        break;

    case 'tipiintervento-tecnico':
        $id_tecnico = $superselect['id_tecnico'];
        $id_intervento = $superselect['id_intervento'];

        if (empty($id_tecnico)) {
            $results = [
                'results' => [],
                'recordsFiltered' => 0,
            ];
            break;
        }

        $intervento = Intervento::find($id_intervento);

        // Query per i tipi di intervento in base alla sede al contratto o al tecnico
        // Priorità: tariffe contratto > tariffe sede > tariffe tecnico
        if (!empty($intervento->id_sede_destinazione)) {
            // Se c'è una sede configurata: prova prima tariffe contratto, poi sede, poi tecnico
            // Se c'è un contratto: mostra SOLO tipi presenti nelle righe del contratto
            if (!empty($intervento->id_contratto)) {
                $query = 'SELECT `in_tipi_intervento`.`id`, CASE WHEN ISNULL(`tempo_standard`) OR `tempo_standard` <= 0 THEN CONCAT(`codice`, \' - \', `title`) WHEN `tempo_standard` > 0 THEN CONCAT(`codice`, \' - \', `title`, \' (\', REPLACE(FORMAT(`tempo_standard`, 2), \'.\', \',\'), \' ore)\') END AS descrizione, `tempo_standard`,
                    COALESCE(`co_contratti_tipi_intervento`.`costo_ore`, `in_tariffe_sedi`.`costo_ore`, `in_tariffe`.`costo_ore`) AS prezzo_ore_unitario,
                    COALESCE(`co_contratti_tipi_intervento`.`costo_km`, `in_tariffe_sedi`.`costo_km`, `in_tariffe`.`costo_km`) AS prezzo_km_unitario,
                    COALESCE(`co_contratti_tipi_intervento`.`costo_diritto_chiamata`, `in_tariffe_sedi`.`costo_diritto_chiamata`, `in_tariffe`.`costo_diritto_chiamata`) AS prezzo_diritto_chiamata
                    FROM `in_tipi_intervento`
                    LEFT JOIN `in_tipi_intervento_lang` ON (`in_tipi_intervento`.`id` = `in_tipi_intervento_lang`.`id_record` AND `in_tipi_intervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                    INNER JOIN `co_righe_contratti` ON `in_tipi_intervento`.`id` = `co_righe_contratti`.`id_tipo_intervento` AND `co_righe_contratti`.`id_contratto` = '.prepare($intervento->id_contratto).'
                    LEFT JOIN `co_contratti_tipi_intervento` ON `in_tipi_intervento`.`id` = `co_contratti_tipi_intervento`.`id_tipo_intervento` AND `co_contratti_tipi_intervento`.`id_contratto` = '.prepare($intervento->id_contratto).'
                    LEFT JOIN `in_tariffe_sedi` ON `in_tipi_intervento`.`id` = `in_tariffe_sedi`.`id_tipo_intervento` AND `in_tariffe_sedi`.`id_sede` = '.prepare($intervento->id_sede_destinazione).'
                    LEFT JOIN `in_tariffe` ON `in_tipi_intervento`.`id` = `in_tariffe`.`id_tipo_intervento` AND `in_tariffe`.`id_tecnico` = '.prepare($id_tecnico).'
                    |where|
                    ORDER BY `title`';

                // Filtro: mostra SOLO tipi presenti nelle righe del contratto
                $where[] = '`co_righe_contratti`.`id_contratto` = '.prepare($intervento->id_contratto);
            } else {
                $query = 'SELECT `in_tipi_intervento`.`id`, CASE WHEN ISNULL(`tempo_standard`) OR `tempo_standard` <= 0 THEN CONCAT(`codice`, \' - \', `title`) WHEN `tempo_standard` > 0 THEN CONCAT(`codice`, \' - \', `title`, \' (\', REPLACE(FORMAT(`tempo_standard`, 2), \'.\', \',\'), \' ore)\') END AS descrizione, `tempo_standard`,
                    COALESCE(`in_tariffe_sedi`.`costo_ore`, `in_tariffe`.`costo_ore`) AS prezzo_ore_unitario,
                    COALESCE(`in_tariffe_sedi`.`costo_km`, `in_tariffe`.`costo_km`) AS prezzo_km_unitario,
                    COALESCE(`in_tariffe_sedi`.`costo_diritto_chiamata`, `in_tariffe`.`costo_diritto_chiamata`) AS prezzo_diritto_chiamata
                    FROM `in_tipi_intervento`
                    LEFT JOIN `in_tipi_intervento_lang` ON (`in_tipi_intervento`.`id` = `in_tipi_intervento_lang`.`id_record` AND `in_tipi_intervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                    LEFT JOIN `in_tariffe_sedi` ON `in_tipi_intervento`.`id` = `in_tariffe_sedi`.`id_tipo_intervento` AND `in_tariffe_sedi`.`id_sede` = '.prepare($intervento->id_sede_destinazione).'
                    LEFT JOIN `in_tariffe` ON `in_tipi_intervento`.`id` = `in_tariffe`.`id_tipo_intervento` AND `in_tariffe`.`id_tecnico` = '.prepare($id_tecnico).'
                    |where|
                    ORDER BY `title`';

                // Filtro: mostra tipi con tariffe sede o tecnico
                $where[] = '(
                    `in_tariffe_sedi`.`id_sede` = '.prepare($intervento->id_sede_destinazione).'
                    OR `in_tariffe`.`id_tecnico` = '.prepare($id_tecnico).'
                )';
            }
        } elseif (!empty($intervento->id_contratto)) {
            // Se c'è un contratto: mostra SOLO tipi presenti nelle righe del contratto
            $query = 'SELECT DISTINCT `in_tipi_intervento`.`id`, CASE WHEN ISNULL(`tempo_standard`) OR `tempo_standard` <= 0 THEN CONCAT(`codice`, \' - \', `title`) WHEN `tempo_standard` > 0 THEN CONCAT(`codice`, \' - \', `title`, \' (\', REPLACE(FORMAT(`tempo_standard`, 2), \'.\', \',\'), \' ore)\') END AS descrizione, `tempo_standard`,
                COALESCE(`co_contratti_tipi_intervento`.`costo_ore`, `in_tariffe`.`costo_ore`) AS prezzo_ore_unitario,
                COALESCE(`co_contratti_tipi_intervento`.`costo_km`, `in_tariffe`.`costo_km`) AS prezzo_km_unitario,
                COALESCE(`co_contratti_tipi_intervento`.`costo_diritto_chiamata`, `in_tariffe`.`costo_diritto_chiamata`) AS prezzo_diritto_chiamata
                FROM `in_tipi_intervento`
                LEFT JOIN `in_tipi_intervento_lang` ON (`in_tipi_intervento`.`id` = `in_tipi_intervento_lang`.`id_record` AND `in_tipi_intervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                INNER JOIN `co_righe_contratti` ON `in_tipi_intervento`.`id` = `co_righe_contratti`.`id_tipo_intervento` AND `co_righe_contratti`.`id_contratto` = '.prepare($intervento->id_contratto).'
                LEFT JOIN `co_contratti_tipi_intervento` ON `in_tipi_intervento`.`id` = `co_contratti_tipi_intervento`.`id_tipo_intervento` AND `co_contratti_tipi_intervento`.`id_contratto` = '.prepare($intervento->id_contratto).'
                LEFT JOIN `in_tariffe` ON `in_tipi_intervento`.`id` = `in_tariffe`.`id_tipo_intervento` AND `in_tariffe`.`id_tecnico` = '.prepare($id_tecnico).'
                |where|
                ORDER BY `title`';

            // Filtro: mostra SOLO tipi presenti nelle righe del contratto
            $where[] = '`co_righe_contratti`.`id_contratto` = '.prepare($intervento->id_contratto);
        } else {
            // Altrimenti usa solo tariffe tecnico
            $query = 'SELECT `in_tipi_intervento`.`id`, CASE WHEN ISNULL(`tempo_standard`) OR `tempo_standard` <= 0 THEN CONCAT(`codice`, \' - \', `title`) WHEN `tempo_standard` > 0 THEN CONCAT(`codice`, \' - \', `title`, \' (\', REPLACE(FORMAT(`tempo_standard`, 2), \'.\', \',\'), \' ore)\') END AS descrizione, `tempo_standard`,
                `in_tariffe`.`costo_ore` AS prezzo_ore_unitario,
                `in_tariffe`.`costo_km` AS prezzo_km_unitario,
                `in_tariffe`.`costo_diritto_chiamata` AS prezzo_diritto_chiamata
                FROM `in_tipi_intervento`
                LEFT JOIN `in_tipi_intervento_lang` ON (`in_tipi_intervento`.`id` = `in_tipi_intervento_lang`.`id_record` AND `in_tipi_intervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                INNER JOIN `in_tariffe` ON `in_tipi_intervento`.`id` = `in_tariffe`.`id_tipo_intervento` AND `in_tariffe`.`id_tecnico` = '.prepare($id_tecnico).'
                |where|
                ORDER BY `title`';

            $where[] = '`in_tariffe`.`id_tecnico` = '.prepare($id_tecnico);
        }

        foreach ($elements as $element) {
            $filter[] = '`in_tipi_intervento`.`id`='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
        }

        if (!empty($superselect['id_anagrafica'])) {
            $ids_tipo_intervento = database()->table('an_anagrafiche_tipi_intervento')->where('id_anagrafica', $superselect['id_anagrafica'])->pluck('id_tipo_intervento')->toArray();
            if (sizeof($ids_tipo_intervento) > 0) {
                $filter[] = '`in_tipi_intervento`.`id` IN('.implode(',', $ids_tipo_intervento).')';
            }
        }

        $data = AJAX::selectResults($query, $where, $filter, $search_fields, $limit, $custom);
        $rs = $data['results'];

        foreach ($rs as $k => $r) {
            $rs[$k] = array_merge($r, [
                'text' => $r['descrizione'],
            ]);
        }

        $results = [
            'results' => $rs,
            'recordsFiltered' => $data['recordsFiltered'],
        ];

        break;
}
