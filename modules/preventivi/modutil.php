<?php

include_once __DIR__.'/../../core.php';

use Modules\Preventivi\Preventivo;

function get_imponibile_preventivo($idpreventivo)
{
    $preventivo = Preventivo::find($idpreventivo);

    return $preventivo->totale_imponibile;
}

/**
 * Restituisce lo stato dell'ordine in base alle righe.
 */
function get_stato_preventivo($idpreventivo)
{
    $dbo = database();

    $rs = $dbo->fetchArray('SELECT SUM(qta) AS qta, SUM(qta_evasa) AS qta_evasa FROM co_righe_preventivi GROUP BY idpreventivo HAVING idpreventivo='.prepare($idpreventivo));

    if ($rs[0]['qta_evasa'] > 0) {
        if ($rs[0]['qta'] > $rs[0]['qta_evasa']) {
            return 'Parzialmente evaso';
        } elseif ($rs[0]['qta'] == $rs[0]['qta_evasa']) {
            return 'Evaso';
        }
    } else {
        return 'Non evaso';
    }
}
