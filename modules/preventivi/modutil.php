<?php

include_once __DIR__.'/../../core.php';

use Modules\Preventivi\Preventivo;

/**
 * Questa funzione rimuove un articolo dal ddt data e lo riporta in magazzino
 * 	$idarticolo		integer		codice dell'articolo da scollegare dall'ordine
 * 	$idordine	 	integer		codice dell'ordine da cui scollegare l'articolo.
 */
function rimuovi_articolo_dapreventivo($idarticolo, $idpreventivo, $idriga)
{
    global $dir;

    $dbo = database();

    // Leggo la quantità di questo articolo nell'ordine
    $query = 'SELECT qta, subtotale FROM co_righe_preventivi WHERE id='.prepare($idriga);
    $rs = $dbo->fetchArray($query);
    $qta = floatval($rs[0]['qta']);
    $subtotale = $rs[0]['subtotale'];

    // Elimino la riga dall'ordine
    $dbo->query('DELETE FROM co_righe_preventivi WHERE id='.prepare($idriga));
}

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

/**
 * Aggiorna il budget del preventivo leggendo tutte le righe inserite.
 *
 * @deprecated 2.3
 */
function update_budget_preventivo($idpreventivo)
{
    $dbo = database();

    // Totale articoli
    $rs = $dbo->fetchArray('SELECT SUM(subtotale) AS totale FROM co_righe_preventivi GROUP BY idpreventivo HAVING idpreventivo='.prepare($idpreventivo));
    $totale_articoli = $rs[0]['totale'];

    $rs = $dbo->fetchArray('SELECT SUM(sconto*qta) AS totale FROM co_righe_preventivi GROUP BY idpreventivo HAVING idpreventivo='.prepare($idpreventivo));
    $totale_sconto = $rs[0]['totale'];

    $rs = $dbo->fetchArray('SELECT SUM(iva) AS totale FROM co_righe_preventivi GROUP BY idpreventivo HAVING idpreventivo='.prepare($idpreventivo));
    $totale_iva = $rs[0]['totale'];

    // Totale costo ore, km e diritto di chiamata
    // $rs = $dbo->fetchArray("SELECT SUM(costo_orario*ore_lavoro + costo_diritto_chiamata) AS totale FROM co_preventivi GROUP BY id HAVING id=\"".$idpreventivo."\"");
    // $totale_lavoro = $rs[0]['totale'];

    // Aggiorno il budget su co_preventivi
    $dbo->query('UPDATE co_preventivi SET budget='.prepare(($totale_articoli - $totale_sconto) + $totale_iva).' WHERE id='.prepare($idpreventivo));
}
