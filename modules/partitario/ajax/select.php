<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'conti':
        $query = 'SELECT co_pianodeiconti2.* FROM co_pianodeiconti2 LEFT JOIN co_pianodeiconti3 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id |where| GROUP BY co_pianodeiconti2.id';

        if($search!=''){
            $wh = "WHERE co_pianodeiconti3.descrizione LIKE ".prepare('%'.$search.'%');
        }else{
            $wh = "";
        }
        $query = str_replace('|where|', $wh, $query);

        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            $results[] = ['text' => $r['numero'].' '.$r['descrizione'], 'children' => []];

            $subquery = 'SELECT * FROM co_pianodeiconti3 |where|';

            $where = [];
            $filter = [];
            $search_fields = [];

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }
            if (!empty($filter)) {
                $where[] = '('.implode(' OR ', $filter).')';
            }

            $where[] = 'idpianodeiconti2='.prepare($r['id']);

            if (!empty($search)) {
                $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
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
                $results[count($results) - 1]['children'][] = ['id' => $r2['id'], 'text' => $r2['descrizione']];
            }
        }

        break;

    case 'conti-vendite':
        $query = "SELECT co_pianodeiconti3.id, CONCAT_WS( ' ', co_pianodeiconti3.numero, co_pianodeiconti3.descrizione ) AS descrizione FROM co_pianodeiconti3 INNER JOIN (co_pianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id |where| ORDER BY co_pianodeiconti3.numero ASC";

        foreach ($elements as $element) {
            $filter[] = 'co_pianodeiconti3.id='.prepare($element);
        }

        $where[] = "co_pianodeiconti1.descrizione='Economico'";
        $where[] = "co_pianodeiconti2.dir='entrata'";

        if (!empty($search)) {
            $search_fields[] = 'co_pianodeiconti3.descrizione LIKE '.prepare('%'.$search.'%');
        }

        break;

    case 'conti-acquisti':
        $query = "SELECT co_pianodeiconti3.id, CONCAT_WS( ' ', co_pianodeiconti3.numero, co_pianodeiconti3.descrizione ) AS descrizione FROM co_pianodeiconti3 INNER JOIN (co_pianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id |where| ORDER BY co_pianodeiconti3.numero ASC";

        foreach ($elements as $element) {
            $filter[] = 'co_pianodeiconti3.id='.prepare($element);
        }

        $where[] = "co_pianodeiconti1.descrizione='Economico'";
        $where[] = "co_pianodeiconti2.dir='uscita'";

        if (!empty($search)) {
            $search_fields[] = 'co_pianodeiconti3.descrizione LIKE '.prepare('%'.$search.'%');
        }

        break;
}
