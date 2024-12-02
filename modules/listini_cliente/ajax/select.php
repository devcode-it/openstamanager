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
    case 'listini':
        $query = 'SELECT id, nome AS descrizione FROM mg_listini |where| ORDER BY nome ASC';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
        }

        break;

    case 'articoli_listino':
        // Gestione campi di ricerca
        if (!empty($search)) {
            $search_fields[] = '|table_listini|.data_scadenza LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '|table_listini|.prezzo_unitario LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '|table_listini|.prezzo_unitario_ivato LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '|table_listini|.sconto_percentuale LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '|table_articoli|.codice LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '|table_articoli|.descrizione LIKE '.prepare('%'.$search.'%');
            $search_fields[] = ($prezzi_ivati ? '|table_articoli|.minimo_vendita_ivato' : '|table_articoli|.minimo_vendita').' LIKE '.prepare('%'.$search.'%');
        }

        // Aggiunta filtri di ricerca
        $where = empty($search_fields) ? '1=1' : '('.implode(' OR ', $search_fields).')';

        $query = 'SELECT `mg_listini_articoli`.*, `mg_articoli`.`codice`, `mg_articoli_lang`.`title` as descrizione,  `mg_articoli`.'.($prezzi_ivati ? 'minimo_vendita_ivato' : 'minimo_vendita').' AS minimo_vendita FROM `mg_listini_articoli` LEFT JOIN `mg_articoli` ON `mg_listini_articoli`.`id_articolo`=`mg_articoli`.`id` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id`=`mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `id_listino`='.prepare($id_listino).' AND 1=1  LIMIT '.$start.', '.$length;

        $query = str_replace('1=1', !empty($where) ? replace($where, [
            '|table_listini|' => 'mg_listini_articoli',
            '|table_articoli|' => 'mg_articoli',
        ]) : '', $query);
        $articoli = $database->fetchArray($query);
        $results = $articoli;

        break;
}
