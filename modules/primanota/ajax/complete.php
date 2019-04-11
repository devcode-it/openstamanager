<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'get_conti':
        $idmastrino = get('idmastrino');
        $conti = [];
        $rs_conti = $dbo->fetchArray('SELECT *, (SELECT CONCAT ((SELECT numero FROM co_pianodeiconti2 WHERE id=co_pianodeiconti3.idpianodeiconti2), ".", numero, " ", descrizione) FROM co_pianodeiconti3 WHERE id=co_movimenti_modelli.idconto) AS descrizione_conto FROM co_movimenti_modelli WHERE idmastrino='.prepare($idmastrino).' GROUP BY id ORDER BY id');

        for ($i = 0; $i < sizeof($rs_conti); ++$i) {
            $conti[$i] = $rs_conti[$i]['idconto'].';'.$rs_conti[$i]['descrizione_conto'];
        }

        echo implode(',', $conti);

        break;
}
