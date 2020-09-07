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
    case 'conti':
        $query = 'SELECT co_pianodeiconti2.* FROM co_pianodeiconti2 LEFT JOIN co_pianodeiconti3 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id |where| GROUP BY co_pianodeiconti2.id ORDER BY co_pianodeiconti2.numero ASC, co_pianodeiconti3.numero ASC';

        if ($search != '') {
            $wh = 'WHERE (co_pianodeiconti3.descrizione LIKE '.prepare('%'.$search.'%')." OR CONCAT( co_pianodeiconti2.numero, '.', co_pianodeiconti3.numero ) LIKE ".prepare('%'.$search.'%').')';
        } else {
            $wh = '';
        }
        $query = str_replace('|where|', $wh, $query);

        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            $results[] = ['text' => $r['numero'].' '.$r['descrizione'], 'children' => []];

            $subquery = 'SELECT co_pianodeiconti3.* FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id |where|';

            $where = [];
            $filter = [];
            $search_fields = [];

            foreach ($elements as $element) {
                $filter[] = 'co_pianodeiconti3.id='.prepare($element);
            }
            if (!empty($filter)) {
                $where[] = '('.implode(' OR ', $filter).')';
            }

            $where[] = 'idpianodeiconti2='.prepare($r['id']);

            if (!empty($search)) {
                $search_fields[] = '(co_pianodeiconti3.descrizione LIKE '.prepare('%'.$search.'%')." OR CONCAT(co_pianodeiconti2.numero, '.', co_pianodeiconti3.numero) LIKE ".prepare('%'.$search.'%').')';
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
        $query = "SELECT co_pianodeiconti3.id, CONCAT( co_pianodeiconti2.numero, '.', co_pianodeiconti3.numero, ' ', co_pianodeiconti3.descrizione ) AS descrizione FROM co_pianodeiconti3 INNER JOIN (co_pianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id |where| ORDER BY co_pianodeiconti2.numero ASC, co_pianodeiconti3.numero ASC";

        foreach ($elements as $element) {
            $filter[] = 'co_pianodeiconti3.id='.prepare($element);
        }

        $where[] = "co_pianodeiconti1.descrizione='Economico'";
        $where[] = "co_pianodeiconti2.dir='entrata'";

        if (!empty($search)) {
            $search_fields[] = "CONCAT( co_pianodeiconti2.numero, '.', co_pianodeiconti3.numero, ' ', co_pianodeiconti3.descrizione ) LIKE ".prepare('%'.$search.'%');
        }

        break;

    case 'conti-acquisti':
        $query = "SELECT co_pianodeiconti3.id, CONCAT( co_pianodeiconti2.numero, '.', co_pianodeiconti3.numero, ' ', co_pianodeiconti3.descrizione ) AS descrizione FROM co_pianodeiconti3 INNER JOIN (co_pianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id |where| ORDER BY co_pianodeiconti2.numero ASC, co_pianodeiconti3.numero ASC";

        foreach ($elements as $element) {
            $filter[] = 'co_pianodeiconti3.id='.prepare($element);
        }

        $where[] = "co_pianodeiconti1.descrizione='Economico'";
        $where[] = "co_pianodeiconti2.dir='uscita'";

        if (!empty($search)) {
            $search_fields[] = "CONCAT( co_pianodeiconti2.numero, '.', co_pianodeiconti3.numero, ' ', co_pianodeiconti3.descrizione ) LIKE ".prepare('%'.$search.'%');
        }

        break;

        case 'conti-modelliprimanota':
        $query = 'SELECT co_pianodeiconti2.* FROM co_pianodeiconti2 LEFT JOIN co_pianodeiconti3 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id |where| GROUP BY co_pianodeiconti2.id';

        if ($search != '') {
            $wh = 'WHERE (co_pianodeiconti3.descrizione LIKE '.prepare('%'.$search.'%')." OR CONCAT( co_pianodeiconti2.numero, '.', co_pianodeiconti3.numero ) LIKE ".prepare('%'.$search.'%').')';
        } else {
            $wh = '';
        }
        $query = str_replace('|where|', $wh, $query);

        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            $results[] = ['text' => $r['numero'].' '.$r['descrizione'], 'children' => []];

            $subquery = 'SELECT co_pianodeiconti3.* FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id |where|';

            $where = [];
            $filter = [];
            $search_fields = [];

            foreach ($elements as $element) {
                $filter[] = 'co_pianodeiconti3.id='.prepare($element);
            }
            if (!empty($filter)) {
                $where[] = '('.implode(' OR ', $filter).')';
            }

            $where[] = 'idpianodeiconti2='.prepare($r['id']);

            if (!empty($search)) {
                $search_fields[] = '(co_pianodeiconti3.descrizione LIKE '.prepare('%'.$search.'%')." OR CONCAT(co_pianodeiconti2.numero, '.', co_pianodeiconti3.numero) LIKE ".prepare('%'.$search.'%').')';
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

            $results[] = ['text' => 'Conto cliente/fornitore fattura', 'children' => []];
            $results[count($results) - 1]['children'][] = ['id' => '-1', 'text' => '{Conto cliente/fornitore fattura}'];
        }

        break;
}
