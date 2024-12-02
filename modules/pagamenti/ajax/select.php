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
     * - codice_modalita_pagamento_fe
     */
    case 'pagamenti':
        // Filtri per banche dell'Azienda
        $id_azienda = setting('Azienda predefinita');

        $query = "SELECT 
            `co_pagamenti`.`id`,
            CONCAT_WS(' - ', `codice_modalita_pagamento_fe`, `title`) AS descrizione,
            `codice_modalita_pagamento_fe`,
            `banca_vendite`.`id` AS id_banca_vendite,
            CONCAT(`banca_vendite`.`nome`, ' - ', `banca_vendite`.`iban`) AS descrizione_banca_vendite,
            `banca_acquisti`.`id` AS id_banca_acquisti,
            CONCAT(`banca_acquisti`.`nome`, ' - ', `banca_acquisti`.`iban`) AS descrizione_banca_acquisti,
            `banca_cliente`.`id` AS id_banca_cliente
        FROM `co_pagamenti`
            LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')
            LEFT JOIN `co_banche` banca_cliente ON `banca_cliente`.`id_anagrafica` = '.prepare($superselect['idanagrafica']).' AND `banca_cliente`.`deleted_at` IS NULL
            LEFT JOIN `co_banche` banca_vendite ON `co_pagamenti`.`idconto_vendite` = `banca_vendite`.`id_pianodeiconti3` AND `banca_vendite`.`id_anagrafica` = '.prepare($id_azienda).' AND `banca_vendite`.`deleted_at` IS NULL
            LEFT JOIN `co_banche` banca_acquisti ON `co_pagamenti`.`idconto_acquisti` = `banca_acquisti`.`id_pianodeiconti3` AND `banca_acquisti`.`id_anagrafica` = '.prepare($id_azienda).' AND `banca_acquisti`.`deleted_at` IS NULL
        |where| 
        GROUP BY 
            `co_pagamenti_lang`.`title` ORDER BY `co_pagamenti_lang`.`title` ASC';

        foreach ($elements as $element) {
            $filter[] = '`co_pagamenti`.`id` = '.prepare($element);
        }

        if (!empty($superselect['codice_modalita_pagamento_fe'])) {
            $where[] = '`codice_modalita_pagamento_fe` = '.prepare($superselect['codice_modalita_pagamento_fe']);
        }

        if (!empty($search)) {
            $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
        }

        $data = AJAX::selectResults($query, $where,
            $filter,
            $search_fields,
            $limit,
            $custom
        );
        $rs = $data['results'];

        foreach ($rs as $k => $r) {
            // Controllo metodi di pagamento con ri.ba. solo per i documenti con dir entrata
            if ($dbo->fetchOne('SELECT `co_tipidocumento`.`dir` AS dir FROM `co_tipidocumento` WHERE `co_tipidocumento`.`id`='.prepare($superselect['idtipodocumento']))['dir'] == 'entrata') {
                $rs[$k] = array_merge($r, [
                    'text' => (($r['codice_modalita_pagamento_fe'] == 'MP12' && empty($r['id_banca_cliente'])) ? $r['descrizione'].' '.tr('(Informazioni bancarie mancanti)') : $r['descrizione']),
                    'disabled' => (($r['codice_modalita_pagamento_fe'] == 'MP12' && empty($r['id_banca_cliente'])) ? 1 : 0),
                ]);
            }
        }

        $results = [
            'results' => $rs,
            'recordsFiltered' => $data['recordsFiltered'],
        ];

        break;
}
