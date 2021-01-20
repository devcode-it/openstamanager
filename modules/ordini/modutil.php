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

include_once __DIR__.'/../../core.php';

use Modules\Ordini\Ordine;

/**
 * Funzione per generare un nuovo numero per l'ordine.
 *
 * @deprecated 2.4.5
 */
function get_new_numeroordine($data)
{
    global $dir;

    return Ordine::getNextNumero($data, $dir);
}

/**
 * Funzione per calcolare il numero secondario successivo utilizzando la maschera dalle impostazioni.
 *
 * @deprecated 2.4.5
 */
function get_new_numerosecondarioordine($data)
{
    global $dir;

    return Ordine::getNextNumeroSecondario($data, $dir);
}

/**
 * Calcolo imponibile ordine (totale_righe - sconto).
 *
 * @deprecated 2.4.5
 */
function get_imponibile_ordine($idordine)
{
    $ordine = Ordine::find($idordine);

    return $ordine->imponibile;
}

/**
 * Calcolo totale ordine (imponibile + iva).
 *
 * @deprecated 2.4.5
 */
function get_totale_ordine($idordine)
{
    $ordine = Ordine::find($idordine);

    return $ordine->totale;
}

/**
 * Calcolo netto a pagare ordine (totale - ritenute - bolli).
 *
 * @deprecated 2.4.5
 */
function get_netto_ordine($idordine)
{
    $ordine = Ordine::find($idordine);

    return $ordine->netto;
}

/**
 * Calcolo iva detraibile ordine.
 *
 * @deprecated 2.4.5
 */
function get_ivadetraibile_ordine($idordine)
{
    $ordine = Ordine::find($idordine);

    return $ordine->iva_detraibile;
}

/**
 * Calcolo iva indetraibile ordine.
 *
 * @deprecated 2.4.5
 */
function get_ivaindetraibile_ordine($idordine)
{
    $ordine = Ordine::find($idordine);

    return $ordine->iva_indetraibile;
}

/**
 * Questa funzione aggiunge un articolo nell'ordine. E' comoda quando si devono inserire
 * degli interventi con articoli collegati o preventivi che hanno interventi con articoli collegati!
 * $iddocumento	integer		id dell'ordine
 * $idarticolo		integer		id dell'articolo da inserire nell'ordine
 * $idiva			integer		id del codice iva associato all'articolo
 * $qta			float		quantità dell'articolo nell'ordine
 * $prezzo			float		prezzo totale degli articoli (prezzounitario*qtà).
 *
 * @deprecated 2.4.11
 */
function add_articolo_inordine($idordine, $idarticolo, $descrizione, $idiva, $qta, $idum, $prezzo, $sconto = 0, $sconto_unitario = 0, $tipo_sconto = 'UNT')
{
    global $dir;

    $dbo = database();

    // Lettura unità di misura dell'articolo
    if (empty($idum)) {
        $rs = $dbo->fetchArray('SELECT um FROM mg_articoli WHERE id='.prepare($idarticolo));
        $um = $rs[0]['valore'];
    } else {
        $um = $idum;
    }

    // Lettura iva dell'articolo
    $rs2 = $dbo->fetchArray('SELECT descrizione, percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva));
    $iva = ($prezzo - $sconto) / 100 * $rs2[0]['percentuale'];
    $iva_indetraibile = $iva / 100 * $rs2[0]['indetraibile'];

    if ($qta > 0) {
        $rsart = $dbo->fetchArray('SELECT abilita_serial FROM mg_articoli WHERE id='.prepare($idarticolo));
        $dbo->query('INSERT INTO or_righe_ordini(idordine, idarticolo, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, abilita_serial, `order`) VALUES ('.prepare($idordine).', '.prepare($idarticolo).', '.prepare($idiva).', '.prepare($rs2[0]['descrizione']).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($prezzo).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', '.prepare($rsart[0]['abilita_serial']).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM or_righe_ordini AS t WHERE idordine='.prepare($idordine).'))');
    }
}

/**
 * Questa funzione rimuove un articolo dall'ordine
 * 	$idarticolo		integer		codice dell'articolo da scollegare dall'ordine
 * 	$idordine	 	integer		codice dell'ordine da cui scollegare l'articolo
 * 	$idrigaordine 	integer		id della riga ordine da rimuovere.
 *
 * @deprecated 2.4.11
 */
function rimuovi_articolo_daordine($idarticolo, $idordine, $idrigaordine)
{
    global $dir;

    $dbo = database();

    $non_rimovibili = seriali_non_rimuovibili('id_riga_ordine', $idrigaordine, $dir);
    if (!empty($non_rimovibili)) {
        return false;
    }

    // Elimino la riga dall'ordine
    $dbo->query('DELETE FROM `or_righe_ordini` WHERE id='.prepare($idrigaordine));

    // Elimino i seriali utilizzati dalla riga
    $dbo->query('DELETE FROM `mg_prodotti` WHERE id_articolo = '.prepare($idarticolo).' AND id_riga_ordine = '.prepare($idrigaordine));

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
    global $dir;

    $dbo = database();

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
                $idrivalsainps = setting('Percentuale rivalsa');
            }
        }

        $query = 'SELECT percentuale FROM co_rivalse WHERE id='.prepare($idrivalsainps);
        $rs = $dbo->fetchArray($query);
        $rivalsainps = $totale_imponibile / 100 * $rs[0]['percentuale'];

        // Aggiorno la rivalsa inps
        $dbo->query("UPDATE or_ordini SET rivalsainps='$rivalsainps' WHERE id=".prepare($idordine));

        // Leggo la ritenuta d'acconto se c'è
        $totale_ordine = get_totale_ordine($idordine);

        // Leggo la rivalsa inps se c'è (per i ordine di vendita lo leggo dalle impostazioni)
        if (!empty($idritenutaacconto)) {
            if ($dir == 'entrata') {
                $idritenutaacconto = setting("Percentuale ritenuta d'acconto");
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
                if (abs($bolli) > 0 && abs($netto_a_pagare > setting("Soglia minima per l'applicazione della marca da bollo"))) {
                    $marca_da_bollo = str_replace(',', '.', $bolli);
                } else {
                    $marca_da_bollo = 0.00;
                }
            }
        } else {
            $marca_da_bollo = 0.00;
        }

        // Leggo l'iva predefinita per calcolare l'iva aggiuntiva sulla rivalsa inps
        $qi = 'SELECT percentuale FROM co_iva WHERE id='.prepare(setting('Iva predefinita'));
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
    $dbo = database();

    $rs_ordine = $dbo->fetchArray("SELECT IFNULL(SUM(qta), 0) AS qta FROM or_righe_ordini WHERE idordine='".$idordine."'");
    $qta_ordine = $rs_ordine[0]['qta'];

    //Righe dell'ordine in ddt
    $rs_ddt = $dbo->fetchArray('SELECT IFNULL(SUM(qta), 0) AS qta FROM dt_righe_ddt WHERE idordine='.prepare($idordine));
    $qta_ddt = $rs_ddt[0]['qta'];

    //Righe dell'ordine in fattura
    $rs_fattura = $dbo->fetchArray('SELECT IFNULL(SUM(qta), 0) AS qta FROM co_righe_documenti WHERE idordine='.prepare($idordine));
    $qta_fattura = $rs_fattura[0]['qta'];

    //Righe dell'ordine in fattura passando da ddt
    $rs_ddt_fattura = $dbo->fetchArray("SELECT IFNULL(SUM(qta), 0) AS qta FROM co_righe_documenti WHERE idddt IN(SELECT DISTINCT idddt FROM dt_righe_ddt WHERE idordine='".$idordine."')");
    $qta_ddt_fattura = $rs_ddt_fattura[0]['qta'];

    if ($qta_ddt == 0) {
        $stato = 'Accettato';
    }
    if ($qta_fattura == 0) {
        $stato = 'Accettato';
    }
    if ($qta_ddt > 0 && $qta_ddt < $qta_ordine && $qta_ordine > 0) {
        $stato = 'Parzialmente evaso';
    }
    if ($qta_ddt == $qta_ordine && $qta_ordine > 0) {
        $stato = 'Evaso';
    }
    if ($qta_fattura > 0 && $qta_fattura < $qta_ordine && $qta_ordine > 0) {
        $stato = 'Parzialmente fatturato';
    }
    if ($qta_fattura == $qta_ordine && $qta_ordine > 0) {
        $stato = 'Fatturato';
    }
    if ($qta_ddt_fattura > 0 && $qta_ddt_fattura < $qta_ordine && $qta_ordine > 0) {
        $stato = 'Parzialmente fatturato';
    }
    if ($qta_ddt_fattura == $qta_ordine && $qta_ordine > 0) {
        $stato = 'Fatturato';
    }

    return $stato;
}
