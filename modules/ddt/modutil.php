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

use Modules\DDT\DDT;

/**
 * Funzione per generare un nuovo numero per il ddt.
 *
 * @deprecated 2.4.5
 */
function get_new_numeroddt($data)
{
    global $dir;

    return DDT::getNextNumero($data, $dir);
}

/**
 * Funzione per calcolare il numero secondario successivo utilizzando la maschera dalle impostazioni.
 *
 * @deprecated 2.4.5
 */
function get_new_numerosecondarioddt($data)
{
    global $dir;

    return DDT::getNextNumeroSecondario($data, $dir);
}

/**
 * Calcolo imponibile ddt (totale_righe - sconto).
 *
 * @deprecated 2.4.5
 */
function get_imponibile_ddt($id_ddt)
{
    $ddt = DDT::find($id_ddt);

    return $ddt->imponibile;
}

/**
 * Calcolo totale ddt (imponibile + iva).
 *
 * @deprecated 2.4.5
 */
function get_totale_ddt($id_ddt)
{
    $ddt = DDT::find($id_ddt);

    return $ddt->totale;
}

/**
 * Calcolo netto a pagare ddt (totale - ritenute - bolli).
 *
 * @deprecated 2.4.5
 */
function get_netto_ddt($id_ddt)
{
    $ddt = DDT::find($id_ddt);

    return $ddt->netto;
}

/**
 * Calcolo iva detraibile ddt.
 *
 * @deprecated 2.4.5
 */
function get_ivadetraibile_ddt($id_ddt)
{
    $ddt = DDT::find($id_ddt);

    return $ddt->iva_detraibile;
}

/**
 * Calcolo iva indetraibile ddt.
 *
 * @deprecated 2.4.5
 */
function get_ivaindetraibile_ddt($id_ddt)
{
    $ddt = DDT::find($id_ddt);

    return $ddt->iva_indetraibile;
}

/**
 * Ricalcola i costi aggiuntivi in ddt (rivalsa inps, ritenuta d'acconto, marca da bollo)
 * Deve essere eseguito ogni volta che si aggiunge o toglie una riga
 * $idddt				int		ID del ddt
 * $idrivalsainps		int		ID della rivalsa inps da applicare. Se omesso viene utilizzata quella impostata di default
 * $idritenutaacconto	int		ID della ritenuta d'acconto da applicare. Se omesso viene utilizzata quella impostata di default
 * $bolli				float	Costi aggiuntivi delle marche da bollo. Se omesso verrà usata la cifra predefinita.
 */
function ricalcola_costiagg_ddt($idddt, $idrivalsainps = '', $idritenutaacconto = '', $bolli = '')
{
    global $dir;

    $dbo = database();

    // Se ci sono righe nel ddt faccio i conteggi, altrimenti azzero gli sconti e le spese aggiuntive (inps, ritenuta, marche da bollo)
    $query = "SELECT COUNT(id) AS righe FROM dt_righe_ddt WHERE idddt='$idddt'";
    $rs = $dbo->fetchArray($query);
    if ($rs[0]['righe'] > 0) {
        $totale_imponibile = get_imponibile_ddt($idddt);
        $totale_ddt = get_totale_ddt($idddt);

        // Leggo gli id dei costi aggiuntivi
        if ($dir == 'uscita') {
            $query2 = "SELECT idrivalsainps, idritenutaacconto, bollo FROM dt_ddt WHERE id='$idddt'";
            $rs2 = $dbo->fetchArray($query2);
            $idrivalsainps = $rs2[0]['idrivalsainps'];
            $idritenutaacconto = $rs2[0]['idritenutaacconto'];
            $bollo = $rs2[0]['bollo'];
        }

        // Leggo la rivalsa inps se c'è (per i ddt di vendita lo leggo dalle impostazioni)
        if ($dir == 'entrata') {
            if (!empty($idrivalsainps)) {
                $idrivalsainps = setting('Percentuale rivalsa');
            }
        }

        $query = "SELECT percentuale FROM co_rivalse WHERE id='".$idrivalsainps."'";
        $rs = $dbo->fetchArray($query);
        $rivalsainps = $totale_imponibile / 100 * $rs[0]['percentuale'];

        // Leggo l'iva predefinita per calcolare l'iva aggiuntiva sulla rivalsa inps
        $qi = "SELECT percentuale FROM co_iva WHERE id='".setting('Iva predefinita')."'";
        $rsi = $dbo->fetchArray($qi);
        $iva_rivalsainps = $rivalsainps / 100 * $rsi[0]['percentuale'];

        // Aggiorno la rivalsa inps
        $dbo->query("UPDATE dt_ddt SET rivalsainps='$rivalsainps', iva_rivalsainps='$iva_rivalsainps' WHERE id='$idddt'");

        $totale_ddt = get_totale_ddt($idddt);

        // Leggo la ritenuta d'acconto se c'è (per i ddt di vendita lo leggo dalle impostazioni)
        if (!empty($idritenutaacconto)) {
            if ($dir == 'entrata') {
                $idritenutaacconto = setting("Percentuale ritenuta d'acconto");
            }
        }

        $query = "SELECT percentuale FROM co_ritenutaacconto WHERE id='".$idritenutaacconto."'";
        $rs = $dbo->fetchArray($query);
        $ritenutaacconto = $totale_ddt / 100 * $rs[0]['percentuale'];
        $netto_a_pagare = $totale_ddt - $ritenutaacconto;

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

        $dbo->query("UPDATE dt_ddt SET ritenutaacconto='$ritenutaacconto', bollo='$marca_da_bollo' WHERE id='$idddt'");
    } else {
        $dbo->query("UPDATE dt_ddt SET ritenutaacconto='0', bollo='0', rivalsainps='0', iva_rivalsainps='0' WHERE id='$idddt'");
    }
}

/**
 * Restituisce lo stato del ddt in base alle righe.
 */
function get_stato_ddt($idddt)
{
    $dbo = database();

    $rs = $dbo->fetchArray('SELECT SUM(qta) AS qta, SUM(qta_evasa) AS qta_evasa FROM dt_righe_ddt GROUP BY idddt HAVING idddt='.prepare($idddt));

    if ($rs[0]['qta'] == 0) {
        return 'Bozza';
    } else {
        if ($rs[0]['qta_evasa'] > 0) {
            if ($rs[0]['qta'] > $rs[0]['qta_evasa']) {
                return 'Parzialmente fatturato';
            } elseif ($rs[0]['qta'] == $rs[0]['qta_evasa']) {
                return 'Fatturato';
            }
        } else {
            return 'Evaso';
        }
    }
}
