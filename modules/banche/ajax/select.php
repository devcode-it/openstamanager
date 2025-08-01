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
    case 'banche':
        $query = "SELECT id, CONCAT (nome, ' - ' , iban) AS descrizione FROM co_banche |where| ORDER BY nome";

        foreach ($elements as $element) {
            $filter[] = 'id = '.prepare($element);
        }

        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
        }

        $where[] = 'id_anagrafica='.prepare($superselect['id_anagrafica']);

        if (!empty($search)) {
            $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'filiale LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'iban LIKE '.prepare('%'.$search.'%');
        }

        $custom['link'] = 'module:Banche';

        break;
}
