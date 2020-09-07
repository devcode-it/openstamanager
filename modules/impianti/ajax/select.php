<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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
    case 'impianti':
            $query = 'SELECT id, CONCAT(matricola, " - ", nome) AS descrizione FROM my_impianti |where| ORDER BY id, idanagrafica';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'matricola LIKE '.prepare('%'.$search.'%');
            }
        break;

    /*
     * Opzioni utilizzate:
     * - idanagrafica
     */
    case 'impianti-cliente':
        if (isset($superselect['idanagrafica'])) {
            $query = 'SELECT id, CONCAT(matricola, " - ", nome) AS descrizione FROM my_impianti |where| ORDER BY idsede';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'idanagrafica='.prepare($superselect['idanagrafica']);
            if (!empty($superselect['idsede_destinazione'])) {
                $where[] = 'idsede='.prepare($superselect['idsede_destinazione']);
            }

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'matricola LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    /*
     * Opzioni utilizzate:
     * - idintervento
     */
    case 'impianti-intervento':
        if (isset($superselect['idintervento'])) {
            $query = 'SELECT id, CONCAT(matricola, " - ", nome) AS descrizione FROM my_impianti INNER JOIN my_impianti_interventi ON my_impianti.id=my_impianti_interventi.idimpianto |where| ORDER BY idsede';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'my_impianti_interventi.idintervento='.prepare($superselect['idintervento']);

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'matricola LIKE '.prepare('%'.$search.'%');
            }
        }
        break;
}
