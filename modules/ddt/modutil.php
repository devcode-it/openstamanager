<?php

include_once __DIR__.'/../../core.php';

/**
 * Funzione per generare un nuovo numero per il ddt.
 */
function get_new_numeroddt($data)
{
    global $dir;

    $dbo = database();

    $query = "SELECT IFNULL(MAX(numero),'0') AS max_numeroddt FROM dt_ddt WHERE DATE_FORMAT( data, '%Y' ) = '".date('Y', strtotime($data))."' AND idtipoddt IN(SELECT id FROM dt_tipiddt WHERE dir='".$dir."') ORDER BY CAST(numero AS UNSIGNED) DESC LIMIT 0,1";
    $rs = $dbo->fetchArray($query);

    return intval($rs[0]['max_numeroddt']) + 1;
}

/**
 * Funzione per calcolare il numero secondario successivo utilizzando la maschera dalle impostazioni.
 */
function get_new_numerosecondarioddt($data)
{
    global $dir;

    $dbo = database();

    // Calcolo il numero secondario se stabilito dalle impostazioni e se documento di vendita
    $formato_numero_secondario = setting('Formato numero secondario ddt');

    $query = "SELECT numero_esterno FROM dt_ddt WHERE DATE_FORMAT( data, '%Y' ) = '".date('Y', strtotime($data))."' AND idtipoddt IN(SELECT id FROM dt_tipiddt WHERE dir='".$dir."') ORDER BY numero_esterno DESC LIMIT 0,1";

    $rs = $dbo->fetchArray($query);
    $numero_secondario = $rs[0]['numero_esterno'];

    if ($numero_secondario == '') {
        $numero_secondario = $formato_numero_secondario;
    }

    if ($formato_numero_secondario != '' && $dir == 'entrata') {
        $numero_esterno = Util\Generator::generate($formato_numero_secondario, $numero_secondario);
    } else {
        $numero_esterno = '';
    }

    return $numero_esterno;
}

/**
 * Questa funzione rimuove un articolo dal ddt data e lo riporta in magazzino
 * 	$idarticolo		integer		codice dell'articolo da scollegare dal ddt
 * 	$idddt		 	integer		codice del ddt da cui scollegare l'articolo.
 */
function rimuovi_articolo_daddt($idarticolo, $idddt, $idrigaddt)
{
    global $dir;

    $dbo = database();

    // Leggo la quantità di questo articolo in ddt
    $query = 'SELECT qta, subtotale FROM dt_righe_ddt WHERE id='.prepare($idrigaddt);
    $rs = $dbo->fetchArray($query);
    $qta = floatval($rs[0]['qta']);
    $subtotale = $rs[0]['subtotale'];

    // Leggo l'idordine
    $query = 'SELECT idordine FROM dt_righe_ddt WHERE id='.prepare($idrigaddt);
    $rs = $dbo->fetchArray($query);
    $idordine = $rs[0]['idordine'];

    $non_rimovibili = seriali_non_rimuovibili('id_riga_ddt', $idrigaddt, $dir);
    if (!empty($non_rimovibili)) {
        return false;
    }

    // Ddt di vendita
    if ($dir == 'entrata') {
        add_movimento_magazzino($idarticolo, $qta, ['idddt' => $idddt]);

        // Se il ddt è stato generato da un ordine tolgo questa quantità dalla quantità evasa
        if (!empty($idordine)) {
            $dbo->query('UPDATE or_righe_ordini SET qta_evasa=qta_evasa-'.$qta.' WHERE idarticolo='.prepare($idarticolo).' AND idordine='.prepare($idordine));
        }
    }

    // Ddt di acquisto
    else {
        add_movimento_magazzino($idarticolo, -$qta, ['idddt' => $idddt]);

        // Se il ddt è stato generato da un ordine tolgo questa quantità dalla quantità evasa
        if (!empty($idordine)) {
            $dbo->query('UPDATE or_righe_ordini SET qta_evasa=qta_evasa-'.$qta.' WHERE idarticolo='.prepare($idarticolo).' AND idordine='.prepare($idordine));
        }
    }

    $dbo->query($query);

    // Elimino la riga dal ddt
    $dbo->query('DELETE FROM `dt_righe_ddt` WHERE id='.prepare($idrigaddt).' AND idddt='.prepare($idddt));

    //Aggiorno lo stato dell'ordine
    if (setting('Cambia automaticamente stato ordini fatturati') && !empty($idordine)) {
        $dbo->query('UPDATE or_ordini SET idstatoordine=(SELECT id FROM or_statiordine WHERE descrizione="'.get_stato_ordine($idordine).'") WHERE id = '.prepare($idordine));
    }

    // Elimino i seriali utilizzati dalla riga
    $dbo->query('DELETE FROM `mg_prodotti` WHERE id_articolo = '.prepare($idarticolo).' AND id_riga_ddt = '.prepare($idrigaddt));

    return true;
}

/**
 * Calcolo imponibile ddt (totale_righe - sconto).
 */
function get_imponibile_ddt($idddt)
{
    $dbo = database();

    $query = 'SELECT SUM(subtotale-sconto) AS imponibile FROM dt_righe_ddt GROUP BY idddt HAVING idddt='.prepare($idddt);
    $rs = $dbo->fetchArray($query);

    return $rs[0]['imponibile'];
}

/**
 * Calcolo totale ddt (imponibile + iva).
 */
function get_totale_ddt($idddt)
{
    $dbo = database();

    // Sommo l'iva di ogni riga al totale
    $query = 'SELECT SUM(iva) AS iva FROM dt_righe_ddt GROUP BY idddt HAVING idddt='.prepare($idddt);
    $rs = $dbo->fetchArray($query);

    // Aggiungo la rivalsa inps se c'è
    $query2 = 'SELECT rivalsainps FROM dt_ddt WHERE id='.prepare($idddt);
    $rs2 = $dbo->fetchArray($query2);

    return get_imponibile_ddt($idddt) + $rs[0]['iva'] + $rs2[0]['rivalsainps'];
}

/**
 * Calcolo netto a pagare ddt (totale - ritenute - bolli).
 */
function get_netto_ddt($idddt)
{
    $dbo = database();

    $query = 'SELECT ritenutaacconto,bollo FROM dt_ddt WHERE id='.prepare($idddt);
    $rs = $dbo->fetchArray($query);

    return get_totale_ddt($idddt) - $rs[0]['ritenutaacconto'] + $rs[0]['bollo'];
}

/**
 * Calcolo iva detraibile ddt.
 */
function get_ivadetraibile_ddt($idddt)
{
    $dbo = database();

    $query = 'SELECT SUM(iva)-SUM(iva_indetraibile) AS iva_detraibile FROM dt_righe_ddt GROUP BY idddt HAVING idddt='.prepare($idddt);
    $rs = $dbo->fetchArray($query);

    return $rs[0]['iva_detraibile'];
}

/**
 * Calcolo iva indetraibile ddt.
 */
function get_ivaindetraibile_ddt($idddt)
{
    $dbo = database();

    $query = 'SELECT SUM(iva_indetraibile) AS iva_indetraibile FROM dt_righe_ddt GROUP BY idddt HAVING idddt='.prepare($idddt);
    $rs = $dbo->fetchArray($query);

    return $rs[0]['iva_indetraibile'];
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
                $idrivalsainps = setting('Percentuale rivalsa INPS');
            }
        }

        $query = "SELECT percentuale FROM co_rivalsainps WHERE id='".$idrivalsainps."'";
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
            $bolli = str_replace(',', '.', setting('Importo marca da bollo'));
            if (abs($bolli) > 0 && abs($netto_a_pagare) > abs(setting("Soglia minima per l'applicazione della marca da bollo"))) {
                $marca_da_bollo = str_replace(',', '.', $bolli);
            } else {
                $marca_da_bollo = 0.00;
            }

            // Se l'importo è negativo può essere una nota di credito, quindi cambio segno alla marca da bollo
            if ($netto_a_pagare < 0) {
                $marca_da_bollo *= -1;
            }
        }

        $dbo->query("UPDATE dt_ddt SET ritenutaacconto='$ritenutaacconto', bollo='$marca_da_bollo' WHERE id='$idddt'");
    } else {
        $dbo->query("UPDATE dt_ddt SET ritenutaacconto='0', bollo='0', rivalsainps='0', iva_rivalsainps='0' WHERE id='$idddt'");
    }
}

/**
 * Questa funzione aggiunge un articolo nel ddt
 * $iddocumento	integer		id dell'ordine
 * $idarticolo		integer		id dell'articolo da inserire nell'ordine
 * $idiva			integer		id del codice iva associato all'articolo
 * $qta			float		quantità dell'articolo nell'ordine
 * $prezzo			float		prezzo totale degli articoli (prezzounitario*qtà).
 */
function add_articolo_inddt($idddt, $idarticolo, $descrizione, $idiva, $qta, $idum, $prezzo, $sconto = 0, $sconto_unitario = 0, $tipo_sconto = 'UNT')
{
    global $dir;
    global $idordine;

    $dbo = database();

    // Lettura unità di misura dell'articolo
    if (empty($idum)) {
        $rs = $dbo->fetchArray('SELECT um FROM mg_articoli WHERE id='.prepare($idarticolo));
        $um = $rs[0]['valore'];
    } else {
        $um = $idum;
    }

    // Lettura iva dell'articolo
    $rs2 = $dbo->fetchArray('SELECT percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva));
    $iva = ($prezzo - $sconto) / 100 * $rs2[0]['percentuale'];
    $iva_indetraibile = $iva / 100 * $rs2[0]['indetraibile'];

    if ($qta > 0) {
        $rsart = $dbo->fetchArray('SELECT abilita_serial FROM mg_articoli WHERE id='.prepare($idarticolo));

        $dbo->query('INSERT INTO dt_righe_ddt(idddt, idarticolo, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, qta, abilita_serial, um, `order`) VALUES ('.prepare($idddt).', '.prepare($idarticolo).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($prezzo).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($qta).', '.prepare($rsart[0]['abilita_serial']).', '.prepare($um).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM dt_righe_ddt AS t WHERE idddt='.prepare($idddt).'))');

        $idriga = $dbo->lastInsertedID();

        /*
            Ddt cliente
        */
        if ($dir == 'entrata') {
            // Decremento la quantità dal magazzino centrale
            add_movimento_magazzino($idarticolo, -$qta, ['idddt' => $idddt]);
        }
        /*
            Ddt fornitore
        */
        elseif ($dir == 'uscita') {
            // Decremento la quantità dal magazzino centrale
            add_movimento_magazzino($idarticolo, $qta, ['idddt' => $idddt]);
        }

        // Inserisco il riferimento dell'ordine alla riga
        $dbo->query('UPDATE dt_righe_ddt SET idordine='.prepare($idordine).' WHERE id='.prepare($idriga));
    }

    return $idriga;
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
