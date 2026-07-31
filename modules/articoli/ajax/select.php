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
     * - permetti_movimento_a_zero
     * - id_sede_partenza e id_sede_destinazione
     * - dir
     * - id_anagrafica
     */
    case 'articoli':
        $sedi_non_impostate = !isset($superselect['id_sede_partenza']) && !isset($superselect['id_sede_destinazione']);
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
        $usare_dettaglio_fornitore = $superselect['dir'] == 'uscita';
        $ricerca_codici_fornitore = 1;
        $usare_iva_anagrafica = $superselect['dir'] == 'entrata' && !empty($superselect['id_anagrafica']);
        $solo_non_varianti = $superselect['solo_non_varianti'];
        $id_agente = $superselect['id_agente'];
        $id_listino = $superselect['id_listino'];
        $iva_predefinita = setting('IVA predefinita');

        $query = "SELECT
            DISTINCT `mg_articoli`.`id`,
            IF(`categoria_lang`.`title` IS NOT NULL, CONCAT(`categoria_lang`.`title`, IF(`sottocategoria_lang`.`title` IS NOT NULL, CONCAT(' (', `sottocategoria_lang`.`title`, ')'), '-')), '<i>".tr('Nessuna categoria')."</i>') AS optgroup,
            `mg_articoli_barcode`.`barcode` AS barcode,
            `mg_articoli`.".($prezzi_ivati ? '`prezzo_vendita_ivato`' : '`prezzo_vendita`').' AS prezzo_vendita,
            `mg_articoli`.`prezzo_vendita_ivato` AS prezzo_vendita_ivato,
            `mg_articoli`.'.($prezzi_ivati ? '`minimo_vendita_ivato`' : '`minimo_vendita`').' AS minimo_vendita,';

        // Informazioni relative al fornitore specificato dal documenti di acquisto
        if ($usare_dettaglio_fornitore) {
            $query .= '
            IFNULL(`mg_fornitore_articolo`.`codice_fornitore`, `mg_articoli`.`codice`) AS codice,
            IFNULL(`mg_fornitore_articolo`.`descrizione`, `mg_articoli_lang`.`title`) AS descrizione,
            IFNULL(`mg_fornitore_articolo`.`prezzo_acquisto`, `mg_articoli`.`prezzo_acquisto`) AS prezzo_acquisto,
            IFNULL(`mg_fornitore_articolo`.`qta_minima`, 0) AS qta_minima,
            `mg_fornitore_articolo`.`id` AS id_dettaglio_fornitore,';
        }
        // Informazioni dell'articolo per i documenti di vendita
        else {
            $query .= '
            `mg_articoli`.`codice` AS codice,
            `mg_articoli_lang`.`title` AS descrizione,
            `mg_articoli`.`prezzo_acquisto` AS prezzo_acquisto,
            0 AS qta_minima,
            `mg_fornitore_articolo`.`codice_fornitore` AS codice_fornitore,
            `mg_fornitore_articolo`.`id` AS id_dettaglio_fornitore,';
        }

        if ($usare_iva_anagrafica) {
            $query .= '
            IFNULL(`iva_anagrafica`.`id`, IFNULL(`iva_articolo`.`id`, `iva_predefinita`.`id`)) AS id_iva_vendita,
            IFNULL(`iva_anagrafica_lang`.`title`, IFNULL(`iva_articolo_lang`.`title`, `iva_predefinita_lang`.`title`)) AS iva_vendita,
            IFNULL(`iva_anagrafica`.`percentuale`, IFNULL(`iva_articolo`.`percentuale`, `iva_predefinita`.`percentuale`)) AS percentuale,';
        } else {
            $query .= '
            IFNULL(`iva_articolo`.`id`, `iva_predefinita`.`id`) AS id_iva_vendita,
            IFNULL(`iva_articolo_lang`.`title`, `iva_predefinita_lang`.`title`) AS iva_vendita,
            IFNULL(`iva_articolo`.`percentuale`, `iva_predefinita`.`percentuale`) AS percentuale,';
        }

        if ($id_agente) {
            $query .= '
            `co_provvigioni`.`provvigione` AS provvigione,
            `co_provvigioni`.`tipo_provvigione` AS tipo_provvigione,';
        }

        $query .= '
            round(`mg_articoli`.`qta`,'.setting('Cifre decimali per quantità').") AS qta,
            `mg_articoli`.`um`,
            `mg_articoli`.`fattore_um_secondaria`,
            `mg_articoli`.`servizio`,
            `mg_articoli`.`abilita_serial`,
            `mg_articoli`.`ubicazione`,
            `mg_articoli`.`id_conto_vendita`,
            `mg_articoli`.`id_conto_acquisto`,
            `categoria_lang`.`title` AS categoria,
            `sottocategoria_lang`.`title` AS sottocategoria,
            `righe`.`media_ponderata`,
            CONCAT(`conto_vendita_categoria` .`numero`, '.', `conto_vendita_sottocategoria`.`numero`, ' ', `conto_vendita_sottocategoria`.`descrizione`) AS id_conto_vendita_title,
            CONCAT(`conto_acquisto_categoria` .`numero`, '.', `conto_acquisto_sottocategoria`.`numero`, ' ', `conto_acquisto_sottocategoria`.`descrizione`) AS id_conto_acquisto_title
        FROM
            `mg_articoli`
            LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')
            LEFT JOIN `zz_categorie` AS categoria ON `categoria`.`id` = `mg_articoli`.`id_categoria`
            LEFT JOIN `zz_categorie_lang` AS categoria_lang ON (`categoria`.`id` = `categoria_lang`.`id_record` AND `categoria_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
            LEFT JOIN `zz_categorie` AS sottocategoria ON `sottocategoria`.`id` = `mg_articoli`.`id_sottocategoria`
            LEFT JOIN `zz_categorie_lang` AS sottocategoria_lang ON (`sottocategoria`.`id` = `sottocategoria_lang`.`id_record` AND `sottocategoria_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).")
            LEFT JOIN `co_piano_dei_conti3` AS conto_vendita_sottocategoria ON `conto_vendita_sottocategoria`.`id`=`mg_articoli`.`id_conto_vendita`
                LEFT JOIN `co_piano_dei_conti2` AS conto_vendita_categoria ON `conto_vendita_sottocategoria`.`id_piano_dei_conti2`=`conto_vendita_categoria`.`id`
            LEFT JOIN `co_piano_dei_conti3` AS conto_acquisto_sottocategoria ON `conto_acquisto_sottocategoria`.`id`=`mg_articoli`.`id_conto_acquisto`
                LEFT JOIN `co_piano_dei_conti2` AS conto_acquisto_categoria ON `conto_acquisto_sottocategoria`.`id_piano_dei_conti2`=`conto_acquisto_categoria`.`id`
            LEFT JOIN (SELECT `co_righe_documenti`.`id_articolo` AS id, (SUM((`co_righe_documenti`.`prezzo_unitario`-`co_righe_documenti`.`sconto_unitario`)*`co_righe_documenti`.`qta`)/SUM(`co_righe_documenti`.`qta`)) AS media_ponderata FROM `co_righe_documenti` LEFT JOIN `co_documenti` ON `co_documenti`.`id`=`co_righe_documenti`.`id_documento` LEFT JOIN `co_tipi_documento` ON `co_tipi_documento`.`id`=`co_documenti`.`id_tipo_documento` WHERE `co_tipi_documento`.`dir`='uscita' GROUP BY `co_righe_documenti`.`id_articolo`) AS righe
            ON `righe`.`id`=`mg_articoli`.`id`
            LEFT JOIN `co_iva` AS iva_articolo ON `iva_articolo`.`id` = `mg_articoli`.`id_iva_vendita`
            LEFT JOIN `co_iva_lang` AS iva_articolo_lang on (`iva_articolo`.`id` = `iva_articolo_lang`.`id_record` AND `iva_articolo_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).")
            LEFT JOIN `co_iva` AS `iva_predefinita` ON `iva_predefinita`.`id` = '.$iva_predefinita.'
            LEFT JOIN `co_iva_lang` AS iva_predefinita_lang on (`iva_predefinita`.`id` = `iva_predefinita_lang`.`id_record` AND `iva_predefinita_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')
            LEFT JOIN mg_articoli_barcode ON mg_articoli_barcode.id_articolo = mg_articoli.id';

        if ($usare_iva_anagrafica) {
            $query .= '
            LEFT JOIN `co_iva` AS iva_anagrafica ON `iva_anagrafica`.`id` = (SELECT `id_iva_vendite` FROM `an_anagrafiche` WHERE `id` = '.prepare($superselect['id_anagrafica']).')
            LEFT JOIN `co_iva_lang` AS iva_anagrafica_lang on (`iva_anagrafica`.`id` = `iva_anagrafica_lang`.`id_record` AND `iva_anagrafica_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')';
        }

        if ($id_agente) {
            $query .= '
            LEFT JOIN `co_provvigioni` ON `co_provvigioni`.`id_articolo` = `mg_articoli`.`id` AND `co_provvigioni`.`id_agente`='.prepare($id_agente);
        }

        if ($dir == 'uscita') {
            $query .= '

            LEFT JOIN `mg_fornitore_articolo` ON `mg_fornitore_articolo`.`id_articolo` = `mg_articoli`.`id` AND `mg_fornitore_articolo`.`deleted_at` IS NULL AND `mg_fornitore_articolo`.`id_fornitore` = '.prepare($superselect['id_anagrafica']);
        } else {
            $query .= '

            LEFT JOIN `mg_fornitore_articolo` ON (`mg_fornitore_articolo`.`id_articolo` = `mg_articoli`.`id` AND `mg_fornitore_articolo`.`deleted_at` IS NULL AND `mg_fornitore_articolo`.`id_fornitore` = `mg_articoli`.`id_fornitore`)';
        }

        // Se c'è una sede settata, carico tutti gli articoli presenti in quella sede
        if (!$sedi_non_impostate) {
            $query .= '
            LEFT JOIN (SELECT `id_articolo`, `id_sede` FROM `mg_movimenti` GROUP BY `id_articolo`, `id_sede`) movimenti ON `movimenti`.`id_articolo`=`mg_articoli`.`id`
            LEFT JOIN `an_sedi` ON `an_sedi`.`id` = `movimenti`.`id_sede`';
        }

        $query .= '
        |where|';

        // Se c'è una sede settata, carico tutti gli articoli presenti in quella sede
        if (!$sedi_non_impostate) {
            $query .= '
        GROUP BY
            `mg_articoli`.`id`';
        }

        $query .= '
        ORDER BY
            `categoria_lang`.`title` ASC,
            `sottocategoria_lang`.`title` ASC,
            `mg_articoli`.`codice` ASC,
            `mg_articoli_lang`.`title` ASC';

        foreach ($elements as $element) {
            $filter[] = '`mg_articoli`.`id`='.prepare($element);
        }

        $where[] = '`mg_articoli`.`attivo` = 1';
        $where[] = '`mg_articoli`.`deleted_at` IS NULL';

        if ($solo_non_varianti) {
            $where[] = '`mg_articoli`.`id_combinazione` IS NULL';
        }

        if ($id_listino) {
            $where[] = '`mg_articoli`.`id` NOT IN (SELECT `id_articolo` FROM `mg_listini_articoli` WHERE `id_listino`='.prepare($id_listino).')';
        }

        if (!empty($search)) {
            $search_fields[] = '`mg_articoli_lang`.`title` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`mg_articoli`.`codice` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'CONCAT(`mg_articoli`.`codice`, " - ", `mg_articoli_lang`.`title`) LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`categoria_lang`.`title` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`mg_articoli_barcode`.`barcode` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`sottocategoria_lang`.`title` LIKE '.prepare('%'.$search.'%');

            if ($usare_dettaglio_fornitore) {
                $search_fields[] = '`mg_fornitore_articolo`.`descrizione` LIKE '.prepare('%'.$search.'%');
                $search_fields[] = '`mg_fornitore_articolo`.`codice_fornitore` LIKE '.prepare('%'.$search.'%');
                $search_fields[] = '`mg_fornitore_articolo`.`barcode_fornitore` LIKE '.prepare('%'.$search.'%');
            }

            if ($ricerca_codici_fornitore) {
                $search_fields[] = '`mg_fornitore_articolo`.`descrizione` LIKE '.prepare('%'.$search.'%');
                $search_fields[] = '`mg_fornitore_articolo`.`codice_fornitore` LIKE '.prepare('%'.$search.'%');
                $search_fields[] = '`mg_fornitore_articolo`.`barcode_fornitore` LIKE '.prepare('%'.$search.'%');
            }
        }

        $data = AJAX::selectResults($query, $where, $filter, $search_fields, $limit, $custom);
        $rs = $data['results'];

        // Utilizzo dell'impostazione per disabilitare articoli con quantità <= 0
        $permetti_movimenti_sotto_zero = setting('Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita') ? true : $superselect['permetti_movimento_a_zero'];

        // Pre-carico le quantità per tutte le sedi interessate in un'unica query
        $id_sede_target = $superselect['dir'] == 'uscita' ? $superselect['id_sede_destinazione'] : $superselect['id_sede_partenza'];
        $qta_per_articolo = [];
        if (!$sedi_non_impostate) {
            $qta_sede_results = $dbo->fetchArray('SELECT `id_articolo`, IFNULL(SUM(`qta`), 0) AS qta FROM `mg_movimenti` WHERE `id_sede` = '.prepare($id_sede_target).' AND `id_articolo` IN ('.implode(',', array_map(prepare(...), array_column($rs, 'id'))).') GROUP BY `id_articolo`');
            foreach ($qta_sede_results as $qta_row) {
                $qta_per_articolo[$qta_row['id_articolo']] = $qta_row['qta'];
            }
        }

        // Eventuali articoli disabilitati
        foreach ($rs as $k => $r) {
            $qta_da_usare = $sedi_non_impostate ? 0 : ($qta_per_articolo[$r['id']] ?? 0);

            $rs[$k] = array_merge($r, [
                'text' => $r['codice'].' - '.$r['descrizione'].' '.(!$r['servizio'] ? '('.Translator::numberToLocale($qta_da_usare).(!empty($r['um']) ? ' '.$r['um'] : '').')' : '').($r['codice_fornitore'] ? ' ('.$r['codice_fornitore'].')' : ''),
                'qta' => $qta_da_usare,
                'qta_sede' => isset($superselect['id_sede_partenza']) || isset($superselect['id_sede_destinazione']) ? $qta_da_usare : null,
                'disabled' => $qta_da_usare <= 0 && !$permetti_movimenti_sotto_zero && !$r['servizio'],
            ]);
        }

        $results = [
            'results' => $rs,
            'recordsFiltered' => $data['recordsFiltered'],
            'link' => '',
        ];

        break;

    case 'categorie':
        $query = 'SELECT `zz_categorie`.`id`, `title` AS descrizione FROM `zz_categorie` LEFT JOIN `zz_categorie_lang` ON (`zz_categorie`.`id` = `zz_categorie_lang`.`id_record` AND `zz_categorie_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') |where| ORDER BY `title`';

        foreach ($elements as $element) {
            $filter[] = '`zz_categorie`.`id`='.prepare($element);
        }

        $where[] = '`parent` IS NULL';
        $where[] = '`zz_categorie`.`is_articolo` = 1';

        if (!empty($search)) {
            $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
        }

        $custom['link'] = 'module:Categorie';

        break;

        /*
         * Opzioni utilizzate:
         * - id_categoria
         */
    case 'sottocategorie':
        if (isset($superselect['id_categoria'])) {
            $query = 'SELECT `zz_categorie`.`id`, `title` AS descrizione FROM `zz_categorie` LEFT JOIN `zz_categorie_lang` ON (`zz_categorie`.`id` = `zz_categorie_lang`.`id_record` AND `zz_categorie_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') |where| ORDER BY `title`';

            foreach ($elements as $element) {
                $filter[] = '`zz_categorie`.`id`='.prepare($element);
            }

            $where[] = '`parent`='.prepare($superselect['id_categoria']);
            $where[] = '`zz_categorie`.`is_articolo` = 1';

            if (!empty($search)) {
                $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
            }

            $custom['link'] = 'module:Categorie';
        }
        break;

    case 'misure':
        $query = 'SELECT `valore` AS id, `valore` AS descrizione FROM `mg_unita_misura` |where| ORDER BY `valore`';

        foreach ($elements as $element) {
            $filter[] = '`valore`='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = '`valore` LIKE '.prepare('%'.$search.'%');
        }

        break;

        /*
         * Opzioni utilizzate:
         * - id_anagrafica
         */
    case 'articoli_barcode':
        $id_anagrafica = filter('id_anagrafica'); // ID passato via URL in modo fisso
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

        $query = 'SELECT `mg_articoli`.*,
            `mg_articoli`.`id`,
            `mg_articoli`.`qta`,
            `mg_articoli`.`um`,
            `mg_articoli`.`id`,
            `mg_articoli`.`id`,
            IFNULL(`mg_fornitore_articolo`.`codice_fornitore`, `mg_articoli`.`codice`) AS codice,
            IFNULL(`mg_fornitore_articolo`.`descrizione`, `mg_articoli_lang`.`title`) AS descrizione,
            IFNULL(`mg_fornitore_articolo`.`prezzo_acquisto`, `mg_articoli`.`prezzo_acquisto`) AS prezzo_acquisto,
            `mg_articoli`.'.($prezzi_ivati ? '`prezzo_vendita_ivato`' : '`prezzo_vendita`').' AS prezzo_vendita,
            `mg_articoli`.`prezzo_vendita_ivato` AS prezzo_vendita_ivato,
            IFNULL(`mg_fornitore_articolo`.`qta_minima`, 0) AS qta_minima,
            `mg_fornitore_articolo`.`id` AS id_dettaglio_fornitore
        FROM `mg_articoli`
            LEFT JOIN `mg_fornitore_articolo` ON `mg_fornitore_articolo`.`id_articolo` = `mg_articoli`.`id` AND `mg_fornitore_articolo`.`deleted_at` IS NULL AND `mg_fornitore_articolo`.`id_fornitore` = '.prepare($id_anagrafica).'
            LEFT JOIN `mg_articoli_barcode` ON `mg_articoli`.`id` = `mg_articoli_barcode`.`id_articolo`
        |where|';

        $where[] = '`mg_articoli`.`attivo` = 1';
        $where[] = '`mg_articoli`.`deleted_at` IS NULL';

        if (!empty($search)) {
            $search_fields[] = '`mg_articoli`.`codice` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`mg_articoli_barcode`.`barcode` LIKE '.prepare('%'.$search.'%');
        }

        break;

    case 'fornitori-articolo':
        $query = 'SELECT `an_anagrafiche`.`id` AS id, CONCAT(`an_anagrafiche`.`ragione_sociale`, IF(`mg_fornitore_articolo`.`codice_fornitore` IS NOT NULL, CONCAT( " (", `mg_fornitore_articolo`.`codice_fornitore`, ")" ), "-") ) AS descrizione, (`mg_prezzi_articoli`.`prezzo_unitario`-(`mg_prezzi_articoli`.`prezzo_unitario`*`mg_prezzi_articoli`.`sconto_percentuale`)/100) AS prezzo_unitario FROM `mg_prezzi_articoli` LEFT JOIN `an_anagrafiche` ON `mg_prezzi_articoli`.`id_anagrafica`=`an_anagrafiche`.`id` LEFT JOIN `mg_fornitore_articolo` ON (`mg_fornitore_articolo`.`id_articolo`=`mg_prezzi_articoli`.`id_articolo` AND `mg_fornitore_articolo`.`id_fornitore` = `mg_prezzi_articoli`.`id_anagrafica`) |where| GROUP BY id ORDER BY `an_anagrafiche`.`ragione_sociale`';

        foreach ($elements as $element) {
            $filter[] = '`an_anagrafiche`.`id`='.prepare($element);
        }

        $where[] = '`dir`="uscita"';
        $where[] = '`mg_prezzi_articoli`.`id_articolo`='.prepare($superselect['id_articolo']);

        if (!empty($search)) {
            $search_fields[] = '`an_anagrafiche`.`ragione_sociale` LIKE '.prepare('%'.$search.'%');
        }

        $custom['link'] = 'module:Anagrafiche';

        break;

    case 'serial-articolo':
        // Query per selezionare i serial disponibili in magazzino
        // Un serial è disponibile se il suo ultimo movimento cronologico ha dir='uscita'
        
        // Verifica se il modulo Vendita al banco è installato
        $modulo_venditabanco = \Models\Module::where('name', 'Vendita al banco')->first();
        $has_venditabanco = !empty($modulo_venditabanco);
        
        // Costruisci la query per ottenere la data del movimento
        $data_movimento = "COALESCE(`co_documenti`.`data`, `dt_ddt`.`data`, `or_ordini`.`data`, `in_interventi`.`data_richiesta`";
        
        if ($has_venditabanco) {
            $data_movimento .= ", `vb_venditabanco`.`data`";
        }
        
        $data_movimento .= ", '1000-01-01')";

        // Costruisci la query base per i movimenti seriali
        $movimenti_seriali = 'SELECT
            `mg_prodotti`.`id`,
            `mg_prodotti`.`id_articolo`,
            `mg_prodotti`.`serial`,
            `mg_prodotti`.`dir`,
            '.$data_movimento.' AS `data_movimento`
        FROM `mg_prodotti`
            LEFT JOIN `co_righe_documenti` ON `mg_prodotti`.`id_riga_documento` = `co_righe_documenti`.`id`
            LEFT JOIN `co_documenti` ON `co_righe_documenti`.`id_documento` = `co_documenti`.`id`
            LEFT JOIN `dt_righe_ddt` ON `mg_prodotti`.`id_riga_ddt` = `dt_righe_ddt`.`id`
            LEFT JOIN `dt_ddt` ON `dt_righe_ddt`.`id_ddt` = `dt_ddt`.`id`
            LEFT JOIN `or_righe_ordini` ON `mg_prodotti`.`id_riga_ordine` = `or_righe_ordini`.`id`
            LEFT JOIN `or_ordini` ON `or_righe_ordini`.`id_ordine` = `or_ordini`.`id`
            LEFT JOIN `in_righe_interventi` ON `mg_prodotti`.`id_riga_intervento` = `in_righe_interventi`.`id`
            LEFT JOIN `in_interventi` ON `in_righe_interventi`.`id_intervento` = `in_interventi`.`id`';
        
        if ($has_venditabanco) {
            $movimenti_seriali .= '
            LEFT JOIN `vb_righe_venditabanco` ON `mg_prodotti`.`id_riga_venditabanco` = `vb_righe_venditabanco`.`id`
            LEFT JOIN `vb_venditabanco` ON `vb_righe_venditabanco`.`idvendita` = `vb_venditabanco`.`id`';
        }
        
        $movimenti_seriali .= '
        WHERE `mg_prodotti`.`serial` IS NOT NULL';

        $query = 'SELECT `seriali_disponibili`.`serial` AS id, `seriali_disponibili`.`serial` AS descrizione
        FROM (
            SELECT
                `movimenti`.`id_articolo`,
                `movimenti`.`serial`,
                SUBSTRING_INDEX(GROUP_CONCAT(`movimenti`.`dir` ORDER BY `movimenti`.`data_movimento` DESC, `movimenti`.`id` DESC), ",", 1) AS `ultimo_dir`
            FROM ('.$movimenti_seriali.') AS `movimenti`
            GROUP BY `movimenti`.`id_articolo`, `movimenti`.`serial`
        ) AS `seriali_disponibili`
        |where|
        ORDER BY `seriali_disponibili`.`serial` ASC';

        foreach ($elements as $element) {
            $filter[] = '`seriali_disponibili`.`serial`='.prepare($element);
        }

        $where[] = '`seriali_disponibili`.`id_articolo`='.prepare($superselect['id_articolo']);

        if (!empty($filter)) {
            $where[] = '(`seriali_disponibili`.`ultimo_dir`=\'uscita\' OR ('.implode(' OR ', $filter).'))';
        } else {
            $where[] = '`seriali_disponibili`.`ultimo_dir`=\'uscita\'';
        }

        if (!empty($search)) {
            $search_fields[] = '`seriali_disponibili`.`serial` LIKE '.prepare('%'.$search.'%');
        }

        break;
}
