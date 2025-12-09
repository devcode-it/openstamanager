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
        $query = 'SELECT `in_tipiintervento`.`id`, CASE WHEN ISNULL(`tempo_standard`) OR `tempo_standard` <= 0 THEN CONCAT(`codice`, \' - \', `title`, IF(`in_tipiintervento`.`deleted_at` IS NULL, "", " ('.tr('eliminato').')")) WHEN `tempo_standard` > 0 THEN  CONCAT(`codice`, \' - \', `title`, \' (\', REPLACE(FORMAT(`tempo_standard`, 2), \'.\', \',\'), \' ore)\', IF(`in_tipiintervento`.`deleted_at` IS NULL, "", " ('.tr('eliminato').')")) END AS descrizione, `tempo_standard` 
        FROM `in_tipiintervento`
        LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id` = `in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        |where| 
        ORDER BY `title`';

        foreach ($elements as $element) {
            $filter[] = '`in_tipiintervento`.`id`='.prepare($element);
        }

        if (empty($filter)) {
            $where[] = '`in_tipiintervento`.`deleted_at` IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
        }

        $data = AJAX::selectResults($query, $where, $filter, $search_fields, $limit, $custom);
        $rs = $data['results'];

        foreach ($rs as $k => $r) {
            $disabled = false;

            // Controllo se il tipo intervento è compatibile con la tipologia dell'anagrafica
            if (!empty($superselect['idanagrafica'])) {
                // Ottengo la tipologia dell'anagrafica selezionata
                $anagrafica_tipo = $dbo->fetchOne('SELECT `tipo`
                    FROM `an_anagrafiche`
                    WHERE `idanagrafica` = '.prepare($superselect['idanagrafica']));

                // Ottengo le tipologie associate al tipo intervento
                $tipologie_tipo_intervento = $dbo->fetchArray('SELECT `tipo`
                    FROM `in_tipiintervento_tipologie`
                    WHERE `idtipointervento` = '.prepare($r['id']));

                $tipologie_tipo_intervento = array_column($tipologie_tipo_intervento, 'tipo');
                if (!empty($tipologie_tipo_intervento)) {
                    // Controllo se la tipologia dell'anagrafica è presente nelle tipologie del tipo intervento
                    $compatibile = in_array($anagrafica_tipo['tipo'], $tipologie_tipo_intervento);
                    if (!$compatibile) {
                        $disabled = true;
                    }
                }
            }

            // Controllo se il tipo intervento è compatibile con il gruppo utente
            $gruppi_tipo_intervento = $dbo->fetchArray('SELECT `id_gruppo`
                FROM `in_tipiintervento_groups`
                WHERE `idtipointervento` = '.prepare($r['id']));

            $gruppi_tipo_intervento = array_column($gruppi_tipo_intervento, 'id_gruppo');
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
        $idtecnico = $superselect['idtecnico'];
        $id_intervento = $superselect['id_intervento'];

        if (empty($idtecnico)) {
            $results = [
                'results' => [],
                'recordsFiltered' => 0,
            ];
            break;
        }

        $intervento = Intervento::find($id_intervento);

        // Query per i tipi di intervento in base al contratto o al tecnico
        if (!empty($intervento->id_contratto)) {
            $query = 'SELECT `in_tipiintervento`.`id`, CONCAT(`codice`, \' - \', `title`) AS descrizione, `co_contratti_tipiintervento`.`costo_ore` AS prezzo_ore_unitario, `co_contratti_tipiintervento`.`costo_km` AS prezzo_km_unitario, `co_contratti_tipiintervento`.`costo_dirittochiamata` AS prezzo_dirittochiamata
            FROM `in_tipiintervento`
            LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id` = `in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
            INNER JOIN `co_contratti_tipiintervento` ON `in_tipiintervento`.`id` = `co_contratti_tipiintervento`.`idtipointervento`
            |where|
            ORDER BY `title`';

            $where[] = '`co_contratti_tipiintervento`.`idtecnico` = '.prepare($idtecnico);

        } else {
            $query = 'SELECT `in_tipiintervento`.`id`, CONCAT(`codice`, \' - \', `title`) AS descrizione, `in_tariffe`.`costo_ore` AS prezzo_ore_unitario, `in_tariffe`.`costo_km` AS prezzo_km_unitario, `in_tariffe`.`costo_dirittochiamata` AS prezzo_dirittochiamata
            FROM `in_tipiintervento`
            LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id` = `in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
            INNER JOIN `in_tariffe` ON `in_tipiintervento`.`id` = `in_tariffe`.`idtipointervento`
            |where|
            ORDER BY `title`';

            $where[] = '`in_tariffe`.`idtecnico` = '.prepare($idtecnico);
            
        }

        foreach ($elements as $element) {
            $filter[] = '`in_tipiintervento`.`id`='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
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
