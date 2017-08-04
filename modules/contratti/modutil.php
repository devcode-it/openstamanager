<?php

include_once __DIR__.'/../../core.php';

/**
 * Questa funzione aggiunge un articolo nell'ordine. E' comoda quando si devono inserire
 * degli interventi con articoli collegati o preventivi che hanno interventi con articoli collegati!
 * $idpreventivo	integer		id del preventivo
 * $idarticolo		integer		id dell'articolo da inserire nel preventivo
 * $idiva			integer		id del codice iva associato all'articolo
 * $qta			float		quantità dell'articolo nell'ordine
 * $prezzo			float		prezzo totale degli articoli (prezzounitario*qtà).
 */
function add_articolo_inpreventivo($idpreventivo, $idarticolo, $descrizione, $idiva, $qta, $prezzo, $lotto = '', $serial = '', $altro = '')
{
    global $dbo;
    global $dir;

    // Lettura unità di misura dell'articolo
    // $query = "SELECT valore FROM mg_unitamisura WHERE id=(SELECT idum FROM mg_articoli WHERE id='".$idarticolo."')";
    // $rs = $dbo->fetchArray($query);
    // $um = $rs[0]['valore'];

    $query = 'SELECT um FROM mg_articoli WHERE id='.prepare($idarticolo);
    $rs = $dbo->fetchArray($query);
    $um = $rs[0]['um'];

    /*
        Ordine cliente
    */
    if ($dir == 'entrata') {
        // Lettura iva dell'articolo
        $rs2 = $dbo->fetchArray('SELECT percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva));
        $iva = $prezzo / 100 * $rs2[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $rs2[0]['indetraibile'];

        // Verifico se nell'ordine c'è già questo articolo allo stesso prezzo unitario
        $rs = $dbo->fetchArray('SELECT id, qta FROM co_righe_preventivi WHERE idarticolo='.prepare($idarticolo).' AND idpreventivo='.prepare($idpreventivo).' AND lotto='.prepare($lotto).' AND serial='.prepare($serial).' AND altro='.prepare($altro));

        // Inserisco la riga nell'ordine: se nell'ordine c'è già questo articolo incremento la quantità...
        if (sizeof($rs) > 0) {
            $dbo->query('UPDATE co_righe_preventivi SET qta=qta+'.$qta.', subtotale=subtotale+'.$prezzo.' WHERE id='.prepare($rs[0]['id']));
        }

        // ...altrimenti inserisco la scorta nell'ordine da zero
        else {
            $dbo->query('INSERT INTO co_righe_preventivi(idpreventivo, idarticolo, idiva, iva, iva_indetraibile, descrizione, subtotale, um, qta, lotto, serial, altro, `order`) VALUES ('.prepare($idpreventivo).', '.prepare($idarticolo).', '.prepare($idiva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($prezzo).', '.prepare($um).', '.prepare($qta).', '.prepare($lotto).', '.prepare($serial).', '.prepare($altro).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_preventivi AS t WHERE idpreventivo='.prepare($idpreventivo).'))');
        }
    }

    /*
        Ordine fornitore
    */
    elseif ($dir == 'uscita') {
    }
}

/**
 * Questa funzione rimuove un articolo dal ddt data e lo riporta in magazzino
 * 	$idarticolo		integer		codice dell'articolo da scollegare dall'ordine
 * 	$idordine	 	integer		codice dell'ordine da cui scollegare l'articolo.
 */
function rimuovi_articolo_dapreventivo($idarticolo, $idpreventivo, $idriga)
{
    global $dbo;
    global $dir;

    // Leggo la quantità di questo articolo nell'ordine
    $query = 'SELECT qta, subtotale FROM co_righe_preventivi WHERE id='.prepare($idriga);
    $rs = $dbo->fetchArray($query);
    $qta = floatval($rs[0]['qta']);
    $subtotale = $rs[0]['subtotale'];

    // Elimino la riga dall'ordine
    $dbo->query('DELETE FROM co_righe_preventivi WHERE id='.prepare($idriga));
}

/**
 * Ricalcola i costi aggiuntivi in ordine (rivalsa inps, ritenuta d'acconto, marca da bollo)
 * Deve essere eseguito ogni volta che si aggiunge o toglie una riga
 * $idordine				int		ID del ordine
 * $idrivalsainps		int		ID della rivalsa inps da applicare. Se omesso viene utilizzata quella impostata di default
 * $idritenutaacconto	int		ID della ritenuta d'acconto da applicare. Se omesso viene utilizzata quella impostata di default
 * $bolli				float	Costi aggiuntivi delle marche da bollo. Se omesso verrà usata la cifra predefinita.
 */
function ricalcola_costiagg_preventivo($idpreventivo, $idrivalsainps = '', $idritenutaacconto = '', $bolli = '')
{
    global $dbo;
    global $dir;

    // Se ci sono righe nel ordine faccio i conteggi, altrimenti azzero gli sconti e le spese aggiuntive (inps, ritenuta, marche da bollo)
    $query = 'SELECT COUNT(id) AS righe FROM co_righe_preventivi WHERE idpreventivo='.prepare($idpreventivo);
    $rs = $dbo->fetchArray($query);
    if ($rs[0]['righe'] > 0) {
        $totale_imponibile = get_imponibile_preventivo($idpreventivo);
        $totale_preventivo = get_totale_preventivo($idpreventivo);

        // Leggo la rivalsa inps se c'è (per i ordine di vendita lo leggo dalle impostazioni)
        if ($dir == 'entrata') {
            if (!empty($idrivalsainps)) {
                $idrivalsainps = get_var('Percentuale rivalsa INPS');
            }
        }

        $query = 'SELECT percentuale FROM co_rivalsainps WHERE id='.prepare($idrivalsainps);
        $rs = $dbo->fetchArray($query);
        $rivalsainps = $totale_imponibile / 100 * $rs[0]['percentuale'];

        // Aggiorno la rivalsa inps
        // $dbo->query("UPDATE or_ordini SET rivalsainps='$rivalsainps' WHERE id='$idordine'");

        // Leggo la ritenuta d'acconto se c'è
        $totale_ordine = get_totale_ordine($idordine);

        // Leggo la rivalsa inps se c'è (per i ordine di vendita lo leggo dalle impostazioni)
        if (!empty($idritenutaacconto)) {
            if ($dir == 'entrata') {
                $idritenutaacconto = get_var("Percentuale ritenuta d'acconto");
            }
        }

        $query = 'SELECT percentuale FROM co_ritenutaacconto WHERE id='.prepare($idritenutaacconto);
        $rs = $dbo->fetchArray($query);
        $ritenutaacconto = $totale_preventivo / 100 * $rs[0]['percentuale'];
        $netto_a_pagare = $totale_preventivo - $ritenutaacconto;

        // Leggo la marca da bollo se c'è e se il netto a pagare supera la soglia
        $bolli = str_replace(',', '.', $bolli);
        $bolli = floatval($bolli);
        if ($dir == 'uscita') {
        } else {
            $bolli = str_replace(',', '.', get_var('Importo marca da bollo'));
            if (abs($bolli) > 0 && abs($netto_a_pagare) > abs(get_var("Soglia minima per l'applicazione della marca da bollo"))) {
                $marca_da_bollo = str_replace(',', '.', $bolli);
            } else {
                $marca_da_bollo = 0.00;
            }

            // Se l'importo è negativo può essere una nota di accredito, quindi cambio segno alla marca da bollo
            if ($netto_a_pagare < 0) {
                $marca_da_bollo *= -1;
            }
        }

        // $dbo->query("UPDATE or_ordini SET ritenutaacconto='$ritenutaacconto', bollo='$marca_da_bollo' WHERE id='$idordine'");
    } else {
        // $dbo->query("UPDATE or_ordini SET ritenutaacconto='0', bollo='0', sconto='0', rivalsainps='0' WHERE id='$idordine'");
    }
}

/**
 * Restituisce lo stato dell'ordine in base alle righe.
 */
function get_stato_preventivo($idpreventivo)
{
    global $dbo;

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
 */
function update_budget_preventivo($idpreventivo)
{
    global $dbo;

    // Totale articoli
    $rs = $dbo->fetchArray('SELECT SUM(subtotale) AS totale FROM co_righe_preventivi GROUP BY idpreventivo HAVING idpreventivo='.prepare($idpreventivo));
    $totale_articoli = $rs[0]['totale'];

    // Totale costo ore, km e diritto di chiamata
    $rs = $dbo->fetchArray('SELECT SUM(costo_orario*ore_lavoro + costo_diritto_chiamata) AS totale FROM co_preventivi GROUP BY id HAVING id='.prepare($idpreventivo));
    $totale_lavoro = $rs[0]['totale'];

    // Aggiorno il budget su co_preventivi
    $dbo->query('UPDATE co_preventivi SET budget='.prepare($totale_articoli + $totale_lavoro).' WHERE id='.prepare($idpreventivo));
}
