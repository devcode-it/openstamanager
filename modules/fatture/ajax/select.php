<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    /*
     * Opzioni utilizzate:
     * - id_anagrafica
     */
    case 'riferimenti-fe':
        $direzione = 'uscita';
        $id_anagrafica = $superselect['id_anagrafica'];
        if (empty($id_anagrafica)) {
            return [];
        }

        // Campi di ricerca
        $search_fields = [];
        if (!empty($search)) {
            $search_fields[] = "IF(numero_esterno != '', numero_esterno, numero) LIKE ".prepare('%'.$search.'%');
            $search_fields[] = "DATE_FORMAT(data, '%d/%m/%Y') LIKE ".prepare('%'.$search.'%');
        }

        $where = implode(' OR ', $search_fields);
        $where = $where ? '('.$where.')' : '1=1';

        $query_ordini = "SELECT or_ordini.id,
            CONCAT('Ordine num. ', IF(numero_esterno != '', numero_esterno, numero), ' del ', DATE_FORMAT(data, '%d/%m/%Y'), ' [', (SELECT descrizione FROM or_statiordine WHERE id = idstatoordine)  , ']') AS text,
            'Ordini' AS optgroup,
            'ordine' AS tipo
        FROM or_ordini
            INNER JOIN or_righe_ordini ON or_righe_ordini.idordine = or_ordini.id
        WHERE idanagrafica = ".prepare($id_anagrafica)." AND
            idstatoordine IN (
                SELECT id FROM or_statiordine WHERE descrizione != 'Fatturato'
            ) AND
            idtipoordine IN (
                SELECT id FROM or_tipiordine WHERE dir = ".prepare($direzione).'
            ) AND |where|
        GROUP BY or_ordini.id
        HAVING SUM(or_righe_ordini.qta - or_righe_ordini.qta_evasa) > 0
        ORDER BY data DESC, numero DESC';

        $query_ddt = "SELECT dt_ddt.id,
           CONCAT('DDT num. ', IF(numero_esterno != '', numero_esterno, numero), ' del ', DATE_FORMAT(data, '%d/%m/%Y'), ' [', (SELECT descrizione FROM dt_statiddt WHERE id = idstatoddt)  , ']') AS text,
            'DDT' AS optgroup,
           'ddt' AS tipo
        FROM dt_ddt
            INNER JOIN dt_righe_ddt ON dt_righe_ddt.idddt = dt_ddt.id
        WHERE idanagrafica = ".prepare($id_anagrafica)." AND
            idstatoddt IN (
                SELECT id FROM dt_statiddt WHERE descrizione != 'Fatturato'
            ) AND
            idtipoddt IN (
                SELECT id FROM dt_tipiddt WHERE dir=".prepare($direzione).'
            ) AND |where|
         GROUP BY dt_ddt.id
        HAVING SUM(dt_righe_ddt.qta - dt_righe_ddt.qta_evasa) > 0
        ORDER BY data DESC, numero DESC';

        // Sostituzione per la ricerca
        $query_ordini = replace($query_ordini, [
            '|where|' => $where,
        ]);

        $query_ddt = replace($query_ddt, [
            '|where|' => $where,
        ]);

        $ordini = $database->fetchArray($query_ordini);
        $ddt = $database->fetchArray($query_ddt);
        $results = array_merge($ordini, $ddt);

        break;
}
