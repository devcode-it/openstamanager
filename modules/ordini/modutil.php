<?php

include_once __DIR__.'/../../core.php';

/**
 * Funzione per generare un nuovo numero per la fattura.
 */
function get_new_numeroordine($data)
{
    global $dbo;
    global $dir;

    $query = "SELECT numero AS max_numeroordine FROM or_ordini WHERE DATE_FORMAT( data, '%Y' ) = ".prepare(date('Y', strtotime($data))).' AND idtipoordine IN(SELECT id FROM or_tipiordine WHERE dir='.prepare($dir).') ORDER BY CAST(numero AS UNSIGNED) DESC LIMIT 0,1';
    $rs = $dbo->fetchArray($query);
    $numero = $rs[0]['max_numeroordine'] + 1;

    return $numero;
}

/**
 * Funzione per calcolare il numero secondario successivo utilizzando la maschera dalle impostazioni.
 */
function get_new_numerosecondarioordine($data)
{
    global $dbo;
    global $dir;

    $query = "SELECT numero_esterno FROM or_ordini WHERE DATE_FORMAT( data, '%Y' ) = ".prepare(date('Y', strtotime($data))).' AND idtipoordine IN(SELECT id FROM or_tipiordine WHERE dir='.prepare($dir).') ORDER BY CAST(numero_esterno AS UNSIGNED) DESC LIMIT 0,1';
    $rs = $dbo->fetchArray($query);
    $numero_secondario = $rs[0]['numero_esterno'];

    // Calcolo il numero secondario se stabilito dalle impostazioni e se documento di vendita
    $formato_numero_secondario = get_var('Formato numero secondario ordine');

    if ($numero_secondario == '') {
        $numero_secondario = $formato_numero_secondario;
    }

    if ($formato_numero_secondario != '' && $dir == 'entrata') {
        $numero_esterno = get_next_code($numero_secondario, 1, $formato_numero_secondario);
    } else {
        $numero_esterno = '';
    }

    return $numero_esterno;
}

/**
 * Calcolo imponibile ordine (totale_righe - sconto).
 */
function get_imponibile_ordine($idordine)
{
    global $dbo;

    $query = 'SELECT SUM(subtotale-sconto) AS imponibile FROM or_righe_ordini GROUP BY idordine HAVING idordine='.prepare($idordine);
    $rs = $dbo->fetchArray($query);

    return $rs[0]['imponibile'];
}

/**
 * Calcolo totale ordine (imponibile + iva).
 */
function get_totale_ordine($idordine)
{
    global $dbo;

    // Sommo l'iva di ogni riga al totale
    $query = 'SELECT SUM(iva) AS iva FROM or_righe_ordini GROUP BY idordine HAVING idordine='.prepare($idordine);
    $rs = $dbo->fetchArray($query);

    // Aggiungo la rivalsa inps se c'è
    $query2 = 'SELECT rivalsainps FROM or_ordini WHERE id='.prepare($idordine);
    $rs2 = $dbo->fetchArray($query2);

    return get_imponibile_ordine($idordine) + $rs[0]['iva'] + $rs2[0]['rivalsainps'];
}

/**
 * Calcolo netto a pagare ordine (totale - iva).
 */
function get_netto_ordine($idordine)
{
    global $dbo;

    $query = 'SELECT ritenutaacconto,bollo FROM or_ordini WHERE id='.prepare($idordine);
    $rs = $dbo->fetchArray($query);

    return get_totale_ordine($idordine) - $rs[0]['ritenutaacconto'] + $rs[0]['bollo'];
}

/**
 * Calcolo iva detraibile ordine.
 */
function get_ivadetraibile_ordine($idordine)
{
    global $dbo;

    $query = 'SELECT SUM(iva)-SUM(iva_indetraibile) AS iva_detraibile FROM or_righe_ordini GROUP BY idordine HAVING idordine='.prepare($idordine);
    $rs = $dbo->fetchArray($query);

    return $rs[0]['iva_detraibile'];
}

/**
 * Calcolo iva indetraibile ordine.
 */
function get_ivaindetraibile_ordine($idordine)
{
    global $dbo;

    $query = 'SELECT SUM(iva_indetraibile) AS iva_indetraibile FROM or_righe_ordini GROUP BY idordine HAVING idordine='.prepare($idordine);
    $rs = $dbo->fetchArray($query);

    return $rs[0]['iva_indetraibile'];
}

/**
 * Questa funzione aggiunge un articolo nell'ordine. E' comoda quando si devono inserire
 * degli interventi con articoli collegati o preventivi che hanno interventi con articoli collegati!
 * $iddocumento	integer		id dell'ordine
 * $idarticolo		integer		id dell'articolo da inserire nell'ordine
 * $idiva			integer		id del codice iva associato all'articolo
 * $qta			float		quantità dell'articolo nell'ordine
 * $prezzo			float		prezzo totale degli articoli (prezzounitario*qtà).
 */
function add_articolo_inordine($idordine, $idarticolo, $descrizione, $idiva, $qta, $prezzo, $sconto = 0, $sconto_unitario = 0, $tipo_sconto = 'UNT', $lotto = '', $serial = '', $altro = '', $idgruppo = 0)
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

    // Lettura iva dell'articolo
    $rs2 = $dbo->fetchArray('SELECT descrizione, percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva));
    $iva = ($prezzo - $sconto) / 100 * $rs2[0]['percentuale'];
    $iva_indetraibile = $iva / 100 * $rs2[0]['indetraibile'];

    if ($qta > 0) {
        $rsart = $dbo->fetchArray('SELECT abilita_serial FROM mg_articoli WHERE id='.prepare($idarticolo));
        $qta_in = !empty($rsart[0]['abilita_serial']) ? $qta : 1;

        for ($i = 0; $i < $qta_in; ++$i) {
            /*
            $iva = $iva / $qta_in;
            $qta = $qta / $qta_in;
            $ubtotale = $subtotale / $qta_in;
            $sconto = $sconto / $qta_in;

            $iva_indetraibile = $iva / 100 * $rs2[0]['indetraibile'];
            */

            $dbo->query('INSERT INTO or_righe_ordini(idordine, idarticolo, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, abilita_serial, idgruppo, `order`) VALUES ('.prepare($idordine).', '.prepare($idarticolo).', '.prepare($idiva).', '.prepare($rs2[0]['descrizione']).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($prezzo).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', '.prepare($rsart[0]['abilita_serial']).', '.prepare($idgruppo).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM or_righe_ordini AS t WHERE idordine='.prepare($idordine).'))');
        }
    }
}

/**
 * Questa funzione rimuove un articolo dall'ordine
 * 	$idarticolo		integer		codice dell'articolo da scollegare dall'ordine
 * 	$idordine	 	integer		codice dell'ordine da cui scollegare l'articolo
 * 	$idrigaordine 	integer		id della riga ordine da rimuovere.
 */
function rimuovi_articolo_daordine($idarticolo, $idordine, $idrigaordine)
{
    global $dbo;
    global $dir;

    // Leggo la quantità di questo articolo in fattura
    $query = 'SELECT idgruppo FROM or_righe_ordini WHERE id='.prepare($idrigaordine);
    $rs = $dbo->fetchArray($query);
    $idgruppo = $rs[0]['idgruppo'];

    if ($dir == 'uscita') {
        $non_rimovibili = $dbo->fetchArray("SELECT COUNT(*) AS non_rimovibili FROM or_righe_ordini WHERE serial IN (SELECT serial FROM vw_serials WHERE dir = 'entrata') AND idgruppo=".prepare($idgruppo).' AND idordine='.prepare($idordine))[0]['non_rimovibili'];
        if ($non_rimovibili != 0) {
            return false;
        }
    }

    // Elimino la riga dall'ordine
    $dbo->query('DELETE FROM `or_righe_ordini` WHERE id='.prepare($idrigaordine));

    return true;
}

/**
 * Ricalcola i costi aggiuntivi in ordine (rivalsa inps, ritenuta d'acconto, marca da bollo)
 * Deve essere eseguito ogni volta che si aggiunge o toglie una riga
 * $idordine				int		ID del ordine
 * $idrivalsainps		int		ID della rivalsa inps da applicare. Se omesso viene utilizzata quella impostata di default
 * $idritenutaacconto	int		ID della ritenuta d'acconto da applicare. Se omesso viene utilizzata quella impostata di default
 * $bolli				float	Costi aggiuntivi delle marche da bollo. Se omesso verrà usata la cifra predefinita.
 */
function ricalcola_costiagg_ordine($idordine, $idrivalsainps = '', $idritenutaacconto = '', $bolli = '')
{
    global $dbo;
    global $dir;

    // Se ci sono righe nel ordine faccio i conteggi, altrimenti azzero gli sconti e le spese aggiuntive (inps, ritenuta, marche da bollo)
    $query = 'SELECT COUNT(id) AS righe FROM or_righe_ordini WHERE idordine='.prepare($idordine);
    $rs = $dbo->fetchArray($query);
    if ($rs[0]['righe'] > 0) {
        $totale_imponibile = get_imponibile_ordine($idordine);
        $totale_ordine = get_totale_ordine($idordine);

        // Leggo gli id dei costi aggiuntivi
        if ($dir == 'uscita') {
            $query2 = 'SELECT idrivalsainps, idritenutaacconto, bollo FROM or_ordini WHERE id='.prepare($idordine);
            $rs2 = $dbo->fetchArray($query2);
            $idrivalsainps = $rs2[0]['idrivalsainps'];
            $idritenutaacconto = $rs2[0]['idritenutaacconto'];
            $bollo = $rs2[0]['bollo'];
        }

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
        $dbo->query("UPDATE or_ordini SET rivalsainps='$rivalsainps' WHERE id=".prepare($idordine));

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
        $ritenutaacconto = $totale_ordine / 100 * $rs[0]['percentuale'];
        $netto_a_pagare = $totale_ordine - $ritenutaacconto;

        // Leggo la marca da bollo se c'è e se il netto a pagare supera la soglia
        $bolli = str_replace(',', '.', $bolli);
        $bolli = floatval($bolli);
        if ($dir == 'uscita') {
            if ($bolli != 0.00) {
                $bolli = str_replace(',', '.', $bolli);
                if (abs($bolli) > 0 && abs($netto_a_pagare > get_var("Soglia minima per l'applicazione della marca da bollo"))) {
                    $marca_da_bollo = str_replace(',', '.', $bolli);
                } else {
                    $marca_da_bollo = 0.00;
                }
            }
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

        // Leggo l'iva predefinita per calcolare l'iva aggiuntiva sulla rivalsa inps
        $qi = 'SELECT percentuale FROM co_iva WHERE id='.prepare(get_var('Iva predefinita'));
        $rsi = $dbo->fetchArray($qi);
        $iva_rivalsainps = $rivalsainps / 100 * $rsi[0]['percentuale'];

        $dbo->query('UPDATE or_ordini SET ritenutaacconto='.prepare($ritenutaacconto).', bollo='.prepare($marca_da_bollo).', iva_rivalsainps='.prepare($iva_rivalsainps).' WHERE id='.prepare($idordine));
    } else {
        $dbo->query("UPDATE or_ordini SET ritenutaacconto='0', bollo='0', rivalsainps='0' WHERE id=".prepare($idordine));
    }
}

/**
 * Restituisce lo stato dell'ordine in base alle righe.
 */
function get_stato_ordine($idordine)
{
    global $dbo;

    $rs = $dbo->fetchArray('SELECT SUM(qta) AS qta, SUM(qta_evasa) AS qta_evasa FROM or_righe_ordini GROUP BY idordine HAVING idordine='.prepare($idordine));

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
