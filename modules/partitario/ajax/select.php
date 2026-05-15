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
    case 'conti':
        $query = 'SELECT co_piano_dei_conti2.* FROM co_piano_dei_conti2 LEFT JOIN co_piano_dei_conti3 ON co_piano_dei_conti3.id_piano_dei_conti2=co_piano_dei_conti2.id |where| GROUP BY co_piano_dei_conti2.id ORDER BY co_piano_dei_conti2.numero ASC, co_piano_dei_conti3.numero ASC';

        if ($search != '') {
            $wh = 'WHERE (co_piano_dei_conti3.descrizione LIKE '.prepare('%'.$search.'%')." OR CONCAT( co_piano_dei_conti2.numero, '.', co_piano_dei_conti3.numero ) LIKE ".prepare('%'.$search.'%').')';
        } else {
            $wh = '';
        }
        $query = str_replace('|where|', $wh, $query);

        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            $results[] = ['text' => $r['numero'].' '.$r['descrizione'], 'children' => []];

            $subquery = 'SELECT co_piano_dei_conti3.* FROM co_piano_dei_conti3 INNER JOIN co_piano_dei_conti2 ON co_piano_dei_conti3.id_piano_dei_conti2=co_piano_dei_conti2.id |where|';

            $where = [];
            $filter = [];
            $search_fields = [];

            foreach ($elements as $element) {
                $filter[] = 'co_piano_dei_conti3.id='.prepare($element);
            }
            if (!empty($filter)) {
                $where[] = '('.implode(' OR ', $filter).')';
            }

            $where[] = 'id_piano_dei_conti2='.prepare($r['id']);

            if (!empty($search)) {
                $search_fields[] = '(co_piano_dei_conti3.descrizione LIKE '.prepare('%'.$search.'%')." OR CONCAT(co_piano_dei_conti2.numero, '.', co_piano_dei_conti3.numero) LIKE ".prepare('%'.$search.'%').')';
            }
            if (!empty($search_fields)) {
                $where[] = '('.implode(' OR ', $search_fields).')';
            }

            $wh = '';
            if (count($where) != 0) {
                $wh = 'WHERE '.implode(' AND ', $where);
            }
            $subquery = str_replace('|where|', $wh, $subquery);

            $rs2 = $dbo->fetchArray($subquery);
            foreach ($rs2 as $r2) {
                $results[count($results) - 1]['children'][] = ['id' => $r2['id'], 'text' => $r['numero'].'.'.$r2['numero'].' '.$r2['descrizione']];
            }
        }

        break;

    case 'conti-vendite':
        $query = "SELECT co_piano_dei_conti3.id, CONCAT( co_piano_dei_conti2.numero, '.', co_piano_dei_conti3.numero, ' ', co_piano_dei_conti3.descrizione ) AS descrizione FROM co_piano_dei_conti3 INNER JOIN (co_piano_dei_conti2 INNER JOIN co_piano_dei_conti1 ON co_piano_dei_conti2.id_piano_dei_conti1=co_piano_dei_conti1.id) ON co_piano_dei_conti3.id_piano_dei_conti2=co_piano_dei_conti2.id |where| ORDER BY co_piano_dei_conti2.numero ASC, co_piano_dei_conti3.numero ASC";

        foreach ($elements as $element) {
            $filter[] = 'co_piano_dei_conti3.id='.prepare($element);
        }

        $where[] = "(co_piano_dei_conti2.dir='entrata' OR co_piano_dei_conti2.dir='entrata/uscita')";

        if (!empty($search)) {
            $search_fields[] = "CONCAT( co_piano_dei_conti2.numero, '.', co_piano_dei_conti3.numero, ' ', co_piano_dei_conti3.descrizione ) LIKE ".prepare('%'.$search.'%');
        }

        $custom['link'] = '';

        break;

    case 'conti-acquisti':
        $query = "SELECT co_piano_dei_conti3.id, CONCAT( co_piano_dei_conti2.numero, '.', co_piano_dei_conti3.numero, ' ', co_piano_dei_conti3.descrizione ) AS descrizione FROM co_piano_dei_conti3 INNER JOIN (co_piano_dei_conti2 INNER JOIN co_piano_dei_conti1 ON co_piano_dei_conti2.id_piano_dei_conti1=co_piano_dei_conti1.id) ON co_piano_dei_conti3.id_piano_dei_conti2=co_piano_dei_conti2.id |where| ORDER BY co_piano_dei_conti2.numero ASC, co_piano_dei_conti3.numero ASC";

        foreach ($elements as $element) {
            $filter[] = 'co_piano_dei_conti3.id='.prepare($element);
        }

        $where[] = "(co_piano_dei_conti2.dir='uscita' OR co_piano_dei_conti2.dir='entrata/uscita')";

        if (!empty($search)) {
            $search_fields[] = "CONCAT( co_piano_dei_conti2.numero, '.', co_piano_dei_conti3.numero, ' ', co_piano_dei_conti3.descrizione ) LIKE ".prepare('%'.$search.'%');
        }

        $custom['link'] = '';

        break;

    case 'conti-modelliprima_nota':
        $query = 'SELECT co_piano_dei_conti2.* FROM co_piano_dei_conti2 LEFT JOIN co_piano_dei_conti3 ON co_piano_dei_conti3.id_piano_dei_conti2=co_piano_dei_conti2.id |where| GROUP BY co_piano_dei_conti2.id';

        if ($search != '') {
            $wh = 'WHERE (co_piano_dei_conti3.descrizione LIKE '.prepare('%'.$search.'%')." OR CONCAT( co_piano_dei_conti2.numero, '.', co_piano_dei_conti3.numero ) LIKE ".prepare('%'.$search.'%').')';
        } else {
            $wh = '';
        }
        $query = str_replace('|where|', $wh, $query);

        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            $results[] = ['text' => $r['numero'].' '.$r['descrizione'], 'children' => []];

            $subquery = 'SELECT co_piano_dei_conti3.* FROM co_piano_dei_conti3 INNER JOIN co_piano_dei_conti2 ON co_piano_dei_conti3.id_piano_dei_conti2=co_piano_dei_conti2.id |where|';

            $where = [];
            $filter = [];
            $search_fields = [];

            foreach ($elements as $element) {
                $filter[] = 'co_piano_dei_conti3.id='.prepare($element);
            }
            if (!empty($filter)) {
                $where[] = '('.implode(' OR ', $filter).')';
            }

            $where[] = 'id_piano_dei_conti2='.prepare($r['id']);

            if (!empty($search)) {
                $search_fields[] = '(co_piano_dei_conti3.descrizione LIKE '.prepare('%'.$search.'%')." OR CONCAT(co_piano_dei_conti2.numero, '.', co_piano_dei_conti3.numero) LIKE ".prepare('%'.$search.'%').')';
            }
            if (!empty($search_fields)) {
                $where[] = '('.implode(' OR ', $search_fields).')';
            }

            $wh = '';
            if (count($where) != 0) {
                $wh = 'WHERE '.implode(' AND ', $where);
            }
            $subquery = str_replace('|where|', $wh, $subquery);

            $rs2 = $dbo->fetchArray($subquery);
            foreach ($rs2 as $r2) {
                $results[count($results) - 1]['children'][] = ['id' => $r2['id'], 'text' => $r['numero'].'.'.$r2['numero'].' '.$r2['descrizione']];
            }

            $results[] = ['text' => 'Conto cliente/fornitore', 'children' => []];
            $results[count($results) - 1]['children'][] = ['id' => '-1', 'text' => '{Conto cliente/fornitore}'];
        }

        break;
}
