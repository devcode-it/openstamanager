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

use Modules\Interventi\Intervento;
use Modules\Iva\Aliquota;
use Modules\Ordini\Ordine;

/*
 * Funzione per generare un nuovo numero per l'ordine.
 *
 * @deprecated 2.4.5
 */
if (!function_exists('get_new_numeroordine')) {
    function get_new_numeroordine($data)
    {
        global $dir;

        return Ordine::getNextNumero($data, $dir);
    }
}

/*
 * Funzione per calcolare il numero secondario successivo utilizzando la maschera dalle impostazioni.
 *
 * @deprecated 2.4.5
 */

if (!function_exists('get_new_numerosecondarioordine')) {
    function get_new_numerosecondarioordine($data)
    {
        global $dir;
        global $id_segment;

        return Ordine::getNextNumeroSecondario($data, $dir, $id_segment);
    }
}

/*
 * Calcolo imponibile ordine (totale_righe - sconto).
 *
 * @deprecated 2.4.5
 */

if (!function_exists('get_imponibile_ordine')) {
    function get_imponibile_ordine($id_ordine)
    {
        $ordine = Ordine::find($id_ordine);

        return $ordine->imponibile;
    }
}

/*
 * Calcolo totale ordine (imponibile + iva).
 *
 * @deprecated 2.4.5
 */
if (!function_exists('get_totale_ordine')) {
    function get_totale_ordine($id_ordine)
    {
        $ordine = Ordine::find($id_ordine);

        return $ordine->totale;
    }
}

/*
 * Calcolo netto a pagare ordine (totale - ritenute - bolli).
 *
 * @deprecated 2.4.5
 */

if (!function_exists('get_netto_ordine')) {
    function get_netto_ordine($id_ordine)
    {
        $ordine = Ordine::find($id_ordine);

        return $ordine->netto;
    }
}
/*
 * Calcolo iva detraibile ordine.
 *
 * @deprecated 2.4.5
 */
if (!function_exists('get_ivadetraibile_ordine')) {
    function get_ivadetraibile_ordine($id_ordine)
    {
        $ordine = Ordine::find($id_ordine);

        return $ordine->iva_detraibile;
    }
}

/*
 * Calcolo iva indetraibile ordine.
 *
 * @deprecated 2.4.5
 */
if (!function_exists('get_ivaindetraibile_ordine')) {
    function get_ivaindetraibile_ordine($id_ordine)
    {
        $ordine = Ordine::find($id_ordine);

        return $ordine->iva_indetraibile;
    }
}
/*
 * Ricalcola i costi aggiuntivi in ordine (rivalsa inps, ritenuta d'acconto, marca da bollo)
 * Deve essere eseguito ogni volta che si aggiunge o toglie una riga
 * $id_ordine				int		ID del ordine
 * $id_rivalsa_inps		int		ID della rivalsa inps da applicare. Se omesso viene utilizzata quella impostata di default
 * $id_ritenuta_acconto	int		ID della ritenuta d'acconto da applicare. Se omesso viene utilizzata quella impostata di default
 * $bolli				float	Costi aggiuntivi delle marche da bollo. Se omesso verrà usata la cifra predefinita.
 */
if (!function_exists('ricalcola_costiagg_ordine')) {
    function ricalcola_costiagg_ordine($id_ordine, $id_rivalsa_inps = '', $id_ritenuta_acconto = '', $bolli = '')
    {
        global $dir;

        $dbo = database();

        // Se ci sono righe nel ordine faccio i conteggi, altrimenti azzero gli sconti e le spese aggiuntive (inps, ritenuta, marche da bollo)
        $query = 'SELECT COUNT(id) AS righe FROM or_righe_ordini WHERE id_ordine='.prepare($id_ordine);
        $rs = $dbo->fetchArray($query);
        if ($rs[0]['righe'] > 0) {
            $totale_imponibile = get_imponibile_ordine($id_ordine);
            $totale_ordine = get_totale_ordine($id_ordine);

            // Leggo gli id dei costi aggiuntivi
            if ($dir == 'uscita') {
                $query2 = 'SELECT id_rivalsa_inps, id_ritenuta_acconto, bollo FROM or_ordini WHERE id='.prepare($id_ordine);
                $rs2 = $dbo->fetchArray($query2);
                $id_rivalsa_inps = $rs2[0]['id_rivalsa_inps'];
                $id_ritenuta_acconto = $rs2[0]['id_ritenuta_acconto'];
                $bollo = $rs2[0]['bollo'];
            }

            // Leggo la rivalsa inps se c'è (per i ordine di vendita lo leggo dalle impostazioni)
            if ($dir == 'entrata') {
                if (!empty($id_rivalsa_inps)) {
                    $id_rivalsa_inps = setting('Cassa previdenziale predefinita');
                }
            }

            $query = 'SELECT percentuale FROM co_rivalse WHERE id='.prepare($id_rivalsa_inps);
            $rs = $dbo->fetchArray($query);
            $rivalsa_inps = $totale_imponibile / 100 * $rs[0]['percentuale'];

            // Aggiorno la rivalsa inps
            $dbo->query('UPDATE or_ordini SET rivalsa_inps='.prepare($rivalsa_inps).' WHERE id='.prepare($id_ordine));

            // Leggo la ritenuta d'acconto se c'è
            $totale_ordine = get_totale_ordine($id_ordine);

            // Leggo la rivalsa inps se c'è (per i ordine di vendita lo leggo dalle impostazioni)
            if (!empty($id_ritenuta_acconto)) {
                if ($dir == 'entrata') {
                    $id_ritenuta_acconto = setting("Ritenuta d'acconto predefinita");
                }
            }

            $query = 'SELECT percentuale FROM co_ritenuta_acconto WHERE id='.prepare($id_ritenuta_acconto);
            $rs = $dbo->fetchArray($query);
            $ritenuta_acconto = $totale_ordine / 100 * $rs[0]['percentuale'];
            $netto_a_pagare = $totale_ordine - $ritenuta_acconto;

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
            $qi = Aliquota::find(setting('Iva predefinita'))->percentuale;
            $iva_rivalsa_inps = $rivalsa_inps / 100 * $qi;

            $dbo->query('UPDATE or_ordini SET ritenuta_acconto='.prepare($ritenuta_acconto).', bollo='.prepare($marca_da_bollo).', iva_rivalsa_inps='.prepare($iva_rivalsa_inps).' WHERE id='.prepare($id_ordine));
        } else {
            $dbo->query("UPDATE or_ordini SET ritenuta_acconto='0', bollo='0', rivalsa_inps='0' WHERE id=".prepare($id_ordine));
        }
    }
}
/*
 * Restituisce lo stato dell'ordine in base alle righe.
 */
if (!function_exists('get_stato_ordine')) {
    function get_stato_ordine($id_ordine)
    {
        $dbo = database();

        $rs_ordine = $dbo->fetchArray('SELECT IFNULL(SUM(qta), 0) AS qta FROM or_righe_ordini WHERE id_ordine='.prepare($id_ordine));
        $qta_ordine = $rs_ordine[0]['qta'];

        // Righe dell'ordine in ddt
        $rs_ddt = $dbo->fetchArray('SELECT IFNULL(SUM(qta), 0) AS qta FROM dt_righe_ddt WHERE id_ordine='.prepare($id_ordine));
        $qta_ddt = $rs_ddt[0]['qta'];

        // Righe dell'ordine in fattura
        $rs_fattura = $dbo->fetchArray('SELECT IFNULL(SUM(qta), 0) AS qta FROM co_righe_documenti WHERE id_ordine='.prepare($id_ordine));
        $qta_fattura = $rs_fattura[0]['qta'];

        // Righe dell'ordine in fattura passando da ddt
        $rs_ddt_fattura = $dbo->fetchArray('SELECT IFNULL(SUM(qta), 0) AS qta FROM co_righe_documenti WHERE idddt IN(SELECT DISTINCT idddt FROM dt_righe_ddt WHERE id_ordine='.prepare($id_ordine).')');
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
}

if (!function_exists('get_totale_interventi_ordine')) {
    function get_totale_interventi_ordine($id_ordine)
    {
        $interventi = Intervento::where('id_ordine', $id_ordine)->get();
        $array_interventi = $interventi->toArray();

        $totale = sum(array_column($array_interventi, 'totale_imponibile'));

        return $totale;
    }
}
