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

use Util\Ini;

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    /*
     * Opzioni utilizzate:
     * - matricola
     */
    case 'componenti':
        if (isset($superselect['matricola'])) {
            $query = 'SELECT id, nome AS descrizione, contenuto FROM my_impianto_componenti |where| ORDER BY id';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $temp = [];
            $impianti = explode(',', $superselect['matricola']);
            foreach ($impianti as $key => $idimpianto) {
                $temp[] = 'idimpianto='.prepare($idimpianto);
            }
            $where[] = '('.implode(' OR ', $temp).')';

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }

            $results = AJAX::selectResults($query, $where, $filter, $search, $limit, $custom);
            $data = $results['results'];
            foreach ($data as $key => $value) {
                $matricola = Ini::getValue($value['contenuto'], 'Matricola');

                $data[$key]['text'] = (empty($matricola) ? '' : $matricola.' - ').$data[$key]['text'];

                unset($data[$key]['contenuto']);
            }

            $results['results'] = $data;
        }

        break;
}
