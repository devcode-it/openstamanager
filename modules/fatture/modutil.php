<?php

use Modules\Fatture\Fattura;

/**
 * Funzione per generare un nuovo numero per la fattura.
 *
 * @deprecated 2.4.5
 */
function get_new_numerofattura($data)
{
    global $dir;
    global $id_segment;

    return Fattura::getNextNumero($data, $dir, $id_segment);
}

/**
 * Funzione per calcolare il numero secondario successivo utilizzando la maschera dalle impostazioni.
 *
 * @deprecated 2.4.5
 */
function get_new_numerosecondariofattura($data)
{
    global $dir;
    global $id_segment;

    return Fattura::getNextNumeroSecondario($data, $dir, $id_segment);
}

/**
 * Calcolo imponibile fattura (totale_righe - sconto).
 *
 * @deprecated 2.4.5
 */
function get_imponibile_fattura($iddocumento)
{
    $fattura = Fattura::find($iddocumento);

    return $fattura->imponibile;
}

/**
 * Calcolo totale fattura (imponibile + iva).
 *
 * @deprecated 2.4.5
 */
function get_totale_fattura($iddocumento)
{
    $fattura = Fattura::find($iddocumento);

    return $fattura->totale;
}

/**
 * Calcolo netto a pagare fattura (totale - ritenute - bolli).
 *
 * @deprecated 2.4.5
 */
function get_netto_fattura($iddocumento)
{
    $fattura = Fattura::find($iddocumento);

    return $fattura->netto;
}

/**
 * Calcolo iva detraibile fattura.
 *
 * @deprecated 2.4.5
 */
function get_ivadetraibile_fattura($iddocumento)
{
    $fattura = Fattura::find($iddocumento);

    return $fattura->iva_detraibile;
}

/**
 * Calcolo iva indetraibile fattura.
 *
 * @deprecated 2.4.5
 */
function get_ivaindetraibile_fattura($iddocumento)
{
    $fattura = Fattura::find($iddocumento);

    return $fattura->iva_indetraibile;
}

/**
 * Elimina una scadenza in base al codice documento.
 */
function elimina_scadenza($iddocumento)
{
    $fattura = Fattura::find($iddocumento);

    $fattura->rimuoviScadenze();
}

/**
 * Funzione per ricalcolare lo scadenzario di una determinata fattura
 * $iddocumento	string		E' l'id del documento di cui ricalcolare lo scadenzario
 * $pagamento		string		Nome del tipo di pagamento. Se è vuoto lo leggo da co_pagamenti_documenti, perché significa che devo solo aggiornare gli importi.
 * $pagato boolean Indica se devo segnare l'importo come pagato.
 */
function aggiungi_scadenza($iddocumento, $pagamento = '', $pagato = false)
{
    $fattura = Fattura::find($iddocumento);

    $fattura->registraScadenze($pagato);
}

/**
 * Funzione per aggiornare lo stato dei pagamenti nello scadenziario.
 *
 * @param $iddocumento int			ID della fattura
 * @param $totale_pagato float		Totale importo pagato
 * @param $data_pagamento datetime	Data in cui avviene il pagamento (yyyy-mm-dd)
 */
function aggiorna_scadenziario($iddocumento, $totale_pagato, $data_pagamento, $idscadenza = '')
{
    $dbo = database();

    if ($totale_pagato > 0) {
        // Lettura righe scadenziario
        if ($idscadenza != '') {
            $add_query = 'AND id='.prepare($idscadenza);
        }

        $query = "SELECT * FROM co_scadenziario WHERE iddocumento='$iddocumento' AND ABS(pagato) < ABS(da_pagare) ".$add_query.' ORDER BY scadenza ASC';
        $rs = $dbo->fetchArray($query);
        $rimanente_da_pagare = abs($rs[0]['pagato']) + $totale_pagato;

        // Verifico se la fattura è di acquisto o di vendita per scegliere che segno mettere nel totale
        $query2 = 'SELECT dir FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id='.prepare($iddocumento);
        $rs2 = $dbo->fetchArray($query2);
        $dir = $rs2[0]['dir'];

        // Ciclo tra le rate dei pagamenti per inserire su `pagato` l'importo effettivamente pagato.
        // Nel caso il pagamento superi la rata, devo distribuirlo sulle rate successive
        for ($i = 0; $i < sizeof($rs); ++$i) {
            if ($rimanente_da_pagare > 0) {
                // ...riempio il pagato della rata con il totale della rata stessa se ho ricevuto un pagamento superiore alla rata stessa
                if (abs($rimanente_da_pagare) >= abs($rs[$i]['da_pagare'])) {
                    $pagato = abs($rs[$i]['da_pagare']);
                    $rimanente_da_pagare -= abs($rs[$i]['da_pagare']);
                } else {
                    // Se si inserisce una somma maggiore al dovuto, tengo valido il rimanente per saldare il tutto...
                    if (abs($rimanente_da_pagare) > abs($rs[$i]['da_pagare'])) {
                        $pagato = abs($rs[$i]['da_pagare']);
                        $rimanente_da_pagare -= abs($rs[$i]['da_pagare']);
                    }

                    // ...altrimenti aggiungo l'importo pagato
                    else {
                        $pagato = abs($rimanente_da_pagare);
                        $rimanente_da_pagare -= abs($rimanente_da_pagare);
                    }
                }

                if ($dir == 'uscita') {
                    $rimanente_da_pagare = -$rimanente_da_pagare;
                }

                if ($pagato > 0) {
                    if ($dir == 'uscita') {
                        $dbo->query('UPDATE co_scadenziario SET pagato='.prepare(-$pagato).', data_pagamento='.prepare($data_pagamento).' WHERE id='.prepare($rs[$i]['id']));
                    } else {
                        $dbo->query('UPDATE co_scadenziario SET pagato='.prepare($pagato).', data_pagamento='.prepare($data_pagamento).' WHERE id='.prepare($rs[$i]['id']));
                    }
                }
            }
        }
    } else {
        // Lettura righe scadenziario
        $query = "SELECT * FROM co_scadenziario WHERE iddocumento='$iddocumento' AND ABS(pagato)>0  ORDER BY scadenza DESC";
        $rs = $dbo->fetchArray($query);
        $residuo_pagato = abs($totale_pagato);
        // Verifico se la fattura è di acquisto o di vendita per scegliere che segno mettere nel totale
        $query2 = 'SELECT dir FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id='.prepare($iddocumento);
        $rs2 = $dbo->fetchArray($query2);
        $dir = $rs2[0]['dir'];
        // Ciclo tra le rate dei pagamenti per inserire su `pagato` l'importo effettivamente pagato.
        // Nel caso il pagamento superi la rata, devo distribuirlo sulle rate successive
        for ($i = 0; $i < sizeof($rs); ++$i) {
            if ($residuo_pagato > 0) {
                // Se si inserisce una somma maggiore al dovuto, tengo valido il rimanente per saldare il tutto...
                if ($residuo_pagato <= abs($rs[$i]['pagato'])) {
                    $pagato = 0;
                    $residuo_pagato -= abs($rs[$i]['pagato']);
                }
                // ...altrimenti aggiungo l'importo pagato
                else {
                    $pagato = abs($residuo_pagato);
                    $residuo_pagato -= abs($residuo_pagato);
                }

                if ($dir == 'uscita') {
                    $residuo_pagato = -$residuo_pagato;
                }

                if ($pagato >= 0) {
                    if ($dir == 'uscita') {
                        $dbo->query('UPDATE co_scadenziario SET pagato='.prepare(-$pagato).', data_pagamento='.prepare($data_pagamento).' WHERE id='.prepare($rs[$i]['id']));
                    } else {
                        $dbo->query('UPDATE co_scadenziario SET pagato='.prepare($pagato).', data_pagamento='.prepare($data_pagamento).' WHERE id='.prepare($rs[$i]['id']));
                    }
                }
            }
        }
    }
}

/**
 * Elimina i movimenti collegati ad una fattura.
 */
function elimina_movimento($iddocumento, $anche_prima_nota = 0)
{
    $dbo = database();

    $query2 = 'DELETE FROM co_movimenti WHERE iddocumento='.prepare($iddocumento).' AND primanota='.prepare($anche_prima_nota);
    $dbo->query($query2);
}

/**
 * Funzione per aggiungere la fattura in prima nota
 * $iddocumento	string		E' l'id del documento da collegare alla prima nota
 * $dir			string		Direzione dell'importo (entrata, uscita)
 * $primanota		boolean		Indica se il movimento è un movimento di prima nota o un movimento normale (di default movimento normale).
 */
function aggiungi_movimento($iddocumento, $dir, $primanota = 0)
{
    $dbo = database();

    // Totale marca da bollo, inps, ritenuta, idagente
    $query = 'SELECT data, bollo, ritenutaacconto, rivalsainps, split_payment FROM co_documenti WHERE id='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);
    $totale_bolli = $rs[0]['bollo'];
    $totale_ritenutaacconto = $rs[0]['ritenutaacconto'];
    $totale_rivalsainps = $rs[0]['rivalsainps'];
    $data_documento = $rs[0]['data'];
    $split_payment = $rs[0]['split_payment'];

    $netto_fattura = get_netto_fattura($iddocumento);
    $totale_fattura = get_totale_fattura($iddocumento);
    $imponibile_fattura = get_imponibile_fattura($iddocumento);

    // Calcolo l'iva della rivalsa inps
    $iva_rivalsainps = 0;

    $rsr = $dbo->fetchArray('SELECT idiva, rivalsainps FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento));

    for ($r = 0; $r < sizeof($rsr); ++$r) {
        $qi = 'SELECT percentuale FROM co_iva WHERE id='.prepare($rsr[$r]['idiva']);
        $rsi = $dbo->fetchArray($qi);
        $iva_rivalsainps += $rsr[$r]['rivalsainps'] / 100 * $rsi[0]['percentuale'];
    }

    // Lettura iva indetraibile fattura
    $query = 'SELECT SUM(iva_indetraibile) AS iva_indetraibile FROM co_righe_documenti GROUP BY iddocumento HAVING iddocumento='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);
    $iva_indetraibile_fattura = $rs[0]['iva_indetraibile'];

    // Lettura iva delle righe in fattura
    $query = 'SELECT iva FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);
    $iva_fattura = sum(array_column($rs, 'iva'), null) + $iva_rivalsainps - $iva_indetraibile_fattura;

    // Imposto i segni + e - in base se la fattura è di acquisto o vendita
    if ($dir == 'uscita') {
        $segno_mov1_cliente = -1;
        $segno_mov2_ricavivendite = 1;
        $segno_mov3_iva = 1;

        $segno_mov4_inps = 1;
        $segno_mov5_ritenutaacconto = -1;

        // Lettura conto fornitore
        $query = 'SELECT idconto_fornitore FROM an_anagrafiche INNER JOIN co_documenti ON an_anagrafiche.idanagrafica=co_documenti.idanagrafica WHERE co_documenti.id='.prepare($iddocumento);
        $rs = $dbo->fetchArray($query);
        $idconto_controparte = $rs[0]['idconto_fornitore'];

        if ($idconto_controparte == '') {
            $query = "SELECT id FROM co_pianodeiconti3 WHERE descrizione='Riepilogativo fornitori'";
            $rs = $dbo->fetchArray($query);
            $idconto_controparte = $rs[0]['idconto_fornitore'];
        }
    } else {
        $segno_mov1_cliente = 1;
        $segno_mov2_ricavivendite = -1;
        $segno_mov3_iva = -1;

        $segno_mov4_inps = -1;
        $segno_mov5_ritenutaacconto = 1;

        // Lettura conto cliente
        $query = 'SELECT idconto_cliente FROM an_anagrafiche INNER JOIN co_documenti ON an_anagrafiche.idanagrafica=co_documenti.idanagrafica WHERE co_documenti.id='.prepare($iddocumento);
        $rs = $dbo->fetchArray($query);
        $idconto_controparte = $rs[0]['idconto_cliente'];

        if ($idconto_controparte == '') {
            $query = "SELECT id FROM co_pianodeiconti3 WHERE descrizione='Riepilogativo clienti'";
            $rs = $dbo->fetchArray($query);
            $idconto_controparte = $rs[0]['idconto_cliente'];
        }
    }

    // Lettura info fattura
    $query = 'SELECT *, co_documenti.note, co_documenti.idpagamento, co_documenti.id AS iddocumento, co_statidocumento.descrizione AS `stato`, co_tipidocumento.descrizione AS `descrizione_tipodoc` FROM ((co_documenti LEFT OUTER JOIN co_statidocumento ON co_documenti.idstatodocumento=co_statidocumento.id) INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);
    $n = sizeof($rs);
    $data = $rs[0]['data'];
    $idanagrafica = $rs[0]['idanagrafica'];
    $ragione_sociale = $rs[0]['ragione_sociale'];
    $stato = $rs[0]['stato'];

    $idmastrino = get_new_idmastrino();

    // Prendo il numero doc. esterno se c'è, altrimenti quello normale
    if (!empty($rs[0]['numero_esterno'])) {
        $numero = $rs[0]['numero_esterno'];
    } else {
        $numero = $rs[0]['numero'];
    }

    // Abbreviazioni contabili dei movimenti
    $tipodoc = '';
    if ($rs[0]['descrizione_tipodoc'] == 'Nota di credito') {
        $tipodoc = 'Nota di credito';
    } elseif ($rs[0]['descrizione_tipodoc'] == 'Nota di debito') {
        $tipodoc = 'Nota di debito';
    } else {
        $tipodoc = 'Fattura';
    }

    $descrizione = $tipodoc.' num. '.$numero;

    /*
        Il mastrino si apre con almeno 3 righe di solito (esempio fattura di vendita):
        1) dare imponibile+iva al conto cliente
        2) avere imponibile sul conto dei ricavi
        3) avere iva sul conto dell'iva a credito (ed eventuale iva indetraibile sul rispettivo conto)

        aggiuntivo:
        4) eventuale rivalsa inps
        5) eventuale ritenuta d'acconto
    */
    // 1) Aggiungo la riga del conto cliente
    $importo_cliente = $totale_fattura;

    if ($split_payment) {
        $importo_cliente = sum($importo_cliente, -$iva_fattura, 2);
    }

    $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_controparte).', '.prepare(($importo_cliente + $totale_bolli) * $segno_mov1_cliente).', '.prepare($primanota).' )';
    $dbo->query($query2);

    // 2) Aggiungo il totale sul conto dei ricavi/spese scelto
    // Lettura descrizione conto ricavi/spese per ogni riga del documento
    $righe = $dbo->fetchArray('SELECT idconto, SUM(subtotale - sconto) AS imponibile FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento).' GROUP BY idconto');

    foreach ($righe as $riga) {
        // Retrocompatibilità
        $idconto_riga = !empty($riga['idconto']) ? $riga['idconto'] : $idconto;

        $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_riga).', '.prepare($riga['imponibile'] * $segno_mov2_ricavivendite).', '.prepare($primanota).')';
        $dbo->query($query2);
    }

    // 3) Aggiungo il totale sul conto dell'iva
    // Lettura id conto iva
    if ($iva_fattura != 0 && !$split_payment) {
        $descrizione_conto_iva = ($dir == 'entrata') ? 'Iva su vendite' : 'Iva su acquisti';
        $query = 'SELECT id, descrizione FROM co_pianodeiconti3 WHERE descrizione='.prepare($descrizione_conto_iva);
        $rs = $dbo->fetchArray($query);
        $idconto_iva = $rs[0]['id'];
        $descrizione_conto_iva = $rs[0]['descrizione'];

        $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_iva).', '.prepare($iva_fattura * $segno_mov3_iva).', '.prepare($primanota).')';
        $dbo->query($query2);
    }

    // Lettura id conto iva indetraibile
    if ($iva_indetraibile_fattura != 0 && !$split_payment) {
        $descrizione_conto_iva2 = 'Iva indetraibile';
        $query = 'SELECT id, descrizione FROM co_pianodeiconti3 WHERE descrizione='.prepare($descrizione_conto_iva2);
        $rs = $dbo->fetchArray($query);
        $idconto_iva2 = $rs[0]['id'];
        $descrizione_conto_iva2 = $rs[0]['descrizione'];

        $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_iva2).', '.prepare($iva_indetraibile_fattura * $segno_mov3_iva).', '.prepare($primanota).')';
        $dbo->query($query2);
    }

    // 4) Aggiungo la rivalsa INPS se c'è
    // Lettura id conto inps
    if ($totale_rivalsainps != 0) {
        $query = "SELECT id, descrizione FROM co_pianodeiconti3 WHERE descrizione='Erario c/INPS'";
        $rs = $dbo->fetchArray($query);
        $idconto_inps = $rs[0]['id'];
        $descrizione_conto_inps = $rs[0]['descrizione'];

        $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_inps).', '.prepare($totale_rivalsainps * $segno_mov4_inps).', '.prepare($primanota).')';
        $dbo->query($query2);
    }

    // 5) Aggiungo la ritenuta d'acconto se c'è
    // Lettura id conto ritenuta e la storno subito
    if ($totale_ritenutaacconto != 0) {
        $query = "SELECT id, descrizione FROM co_pianodeiconti3 WHERE descrizione=\"Erario c/ritenute d'acconto\"";
        $rs = $dbo->fetchArray($query);
        $idconto_ritenutaacconto = $rs[0]['id'];
        $descrizione_conto_ritenutaacconto = $rs[0]['descrizione'];

        // DARE nel conto ritenuta
        $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_ritenutaacconto).', '.prepare($totale_ritenutaacconto * $segno_mov5_ritenutaacconto).', '.prepare($primanota).')';
        $dbo->query($query2);

        // AVERE nel riepilogativo clienti
        $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_controparte).', '.prepare(($totale_ritenutaacconto * $segno_mov5_ritenutaacconto) * -1).', '.prepare($primanota).')';
        $dbo->query($query2);
    }
}

/**
 * Funzione per generare un nuovo codice per il mastrino.
 */
function get_new_idmastrino($table = 'co_movimenti')
{
    $dbo = database();

    $query = 'SELECT MAX(idmastrino) AS maxidmastrino FROM '.$table;
    $rs = $dbo->fetchArray($query);

    return intval($rs[0]['maxidmastrino']) + 1;
}

/**
 * Ricalcola i costi aggiuntivi in fattura (rivalsa inps, ritenuta d'acconto, marca da bollo)
 * Deve essere eseguito ogni volta che si aggiunge o toglie una riga
 * $iddocumento		int		ID della fattura.
 */
function ricalcola_costiagg_fattura($iddocumento)
{
    global $dir;

    $fattura = Fattura::find($iddocumento);
    $fattura->save();
}

/**
 * Questa funzione aggiunge un articolo in fattura. E' comoda quando si devono inserire
 * degli interventi con articoli collegati o preventivi che hanno interventi con articoli collegati!
 * $iddocumento	integer		id della fattura
 * $idarticolo		integer		id dell'articolo da inserire in fattura
 * $idiva			integer		id del codice iva associato all'articolo
 * $qta			float		quantità dell'articolo in fattura
 * $prezzo			float		prezzo totale dell'articolo (prezzounitario*qtà)
 * $idintervento	integer		id dell'intervento da cui arriva l'articolo (per non creare casini quando si rimuoverà un articolo dalla fattura).
 */
function add_articolo_infattura($iddocumento, $idarticolo, $descrizione, $idiva, $qta, $prezzo, $sconto = 0, $sconto_unitario = 0, $tipo_sconto = 'UNT', $idintervento = 0, $idconto = 0, $idum = 0, $idrivalsainps = '', $idritenutaacconto = '', $calcolo_ritenuta_acconto = '')
{
    global $dir;
    global $idddt;
    global $idordine;
    global $idcontratto;

    $dbo = database();

    if (empty($idddt)) {
        $idddt = 0;
    }

    if (empty($idordine)) {
        $idordine = 0;
    }

    if (empty($idcontratto)) {
        $idcontratto = 0;
    }

    // Lettura unità di misura dell'articolo
    if (empty($idum)) {
        $query = 'SELECT um FROM mg_articoli WHERE id='.prepare($idarticolo);
        $rs = $dbo->fetchArray($query);
        $um = $rs[0]['valore'];
    } else {
        $um = $idum;
    }

    // Lettura iva dell'articolo
    $rs2 = $dbo->fetchArray('SELECT * FROM co_iva WHERE id='.prepare($idiva));
    $iva = ($prezzo - $sconto) / 100 * $rs2[0]['percentuale'];
    $desc_iva = $rs2[0]['descrizione'];

    if (!empty($idrivalsainps)) {
        // Calcolo rivalsa inps
        $rs = $dbo->fetchArray('SELECT * FROM co_rivalse WHERE id='.prepare($idrivalsainps));
        $rivalsainps = ($prezzo - $sconto) / 100 * $rs[0]['percentuale'];
    }

    if (!empty($idritenutaacconto)) {
        // Calcolo ritenuta d'acconto
        $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare($idritenutaacconto);
        $rs = $dbo->fetchArray($query);
        if ($calcolo_ritenuta_acconto == 'IMP') {
            $ritenutaacconto = ($prezzo - $sconto) / 100 * $rs[0]['percentuale'];
        } elseif ($calcolo_ritenuta_acconto == 'IMP+RIV') {
            $ritenutaacconto = ($prezzo - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];
        }
    }

    if ($qta != 0) {
        $rsart = $dbo->fetchArray('SELECT abilita_serial, idconto_vendita, idconto_acquisto FROM mg_articoli WHERE id='.prepare($idarticolo));

        $default_idconto = ($dir == 'entrata') ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');
        if ($idconto == $default_idconto) {
            $idconto = $rsart[0]['idconto_'.($dir == 'entrata' ? 'vendita' : 'acquisto')];
        }
        $idconto = empty($idconto) ? $default_idconto : $idconto;

        $dbo->query('INSERT INTO co_righe_documenti(iddocumento, idarticolo, idintervento, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, qta, abilita_serial, idconto, um, `order`, idritenutaacconto, ritenutaacconto, idrivalsainps, rivalsainps,	calcolo_ritenuta_acconto) VALUES ('.prepare($iddocumento).', '.prepare($idarticolo).', '.(!empty($idintervento) ? prepare($idintervento) : 'NULL').', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($prezzo).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($qta).', '.prepare($rsart[0]['abilita_serial']).', '.prepare($idconto).', '.prepare($um).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($iddocumento).'), '.prepare($idritenutaacconto).', '.prepare($ritenutaacconto).', '.prepare($idrivalsainps).', '.prepare($rivalsainps).', '.prepare($calcolo_ritenuta_acconto).')');
        $idriga = $dbo->lastInsertedID();

        /*
            Fatture di vendita
        */
        if ($dir == 'entrata') {
            // Se il documento non è generato da un ddt o intervento allora movimento il magazzino
            if (empty($idddt) && empty($idintervento)) {
                add_movimento_magazzino($idarticolo, -$qta, ['iddocumento' => $iddocumento]);
            }
        }
        /*
            Fatture di acquisto
        */
        elseif ($dir == 'uscita') {
            // Se il documento non è generato da un ddt allora movimento il magazzino
            if (empty($idddt)) {
                add_movimento_magazzino($idarticolo, $qta, ['iddocumento' => $iddocumento]);
            }
        }

        // Inserisco il riferimento del ddt alla riga
        $dbo->query('UPDATE co_righe_documenti SET idddt='.prepare($idddt).' WHERE id='.prepare($idriga));

        // Inserisco il riferimento dell'ordine alla riga
        $dbo->query('UPDATE co_righe_documenti SET idordine='.prepare($idordine).' WHERE id='.prepare($idriga));

        // Inserisco il riferimento del contratto alla riga
        $dbo->query('UPDATE co_righe_documenti SET idcontratto='.prepare($idcontratto).' WHERE id='.prepare($idriga));
    }

    return $idriga;
}

/**
 * Questa funzione rimuove un articolo dalla fattura data e lo riporta in magazzino nel primo lotto libero
 * a partire dal lotto più vecchio
 * 	$idarticolo		integer		codice dell'articolo da scollegare dalla fattura
 * 	$iddocumento 	integer		codice della fattura da cui scollegare l'articolo.
 */
function rimuovi_articolo_dafattura($idarticolo, $iddocumento, $idrigadocumento)
{
    global $dir;

    $dbo = database();

    // Leggo la quantità di questo articolo in fattura
    $query = 'SELECT qta, idintervento, idpreventivo, idordine, idddt, subtotale, descrizione FROM co_righe_documenti WHERE id='.prepare($idrigadocumento);
    $rs = $dbo->fetchArray($query);
    $idintervento = $rs[0]['idintervento'];
    $idpreventivo = $rs[0]['idpreventivo'];
    $idddt = $rs[0]['idddt'];
    $idordine = $rs[0]['idordine'];
    $qta = $rs[0]['qta'];
    $subtotale = $rs[0]['subtotale'];

    $descrizione = $rs[0]['descrizione'];

    $lotto = $rs[0]['lotto'];
    $serial = $rs[0]['serial'];
    $altro = $rs[0]['altro'];

    $non_rimovibili = seriali_non_rimuovibili('id_riga_documento', $idrigadocumento, $dir);
    if (!empty($non_rimovibili)) {
        return false;
    }

    // Se l'articolo è stato aggiunto in fattura perché era collegato ad un intervento o
    // preventivo o ddt o ordine non devo riportarlo in magazzino quando lo tolgo dalla fattura, perché
    // se lo scollegassi poi anche dall'intervento aggiungerei in magazzino la quantità 2 volte!!
    if ($qta > 0) {
        if (empty($idintervento) && empty($idddt)) {
            // Fatture di vendita
            if ($dir == 'entrata') {
                add_movimento_magazzino($idarticolo, $qta, ['iddocumento' => $iddocumento]);
            }

            // Fatture di acquisto
            else {
                add_movimento_magazzino($idarticolo, -$qta, ['iddocumento' => $iddocumento]);
            }
        }

        // TODO: possibile ambiguità tra righe molto simili tra loro
        // Se l'articolo è stato inserito in fattura tramite un ddt devo sanare la qta_evasa
        if (!empty($idddt)) {
            $dbo->query('UPDATE dt_righe_ddt SET qta_evasa=qta_evasa-'.$qta.' WHERE qta='.prepare($qta).' AND idarticolo='.prepare($idarticolo).' AND idddt='.prepare($idddt));
        }

        // TODO: possibile ambiguità tra righe molto simili tra loro
        // Se l'articolo è stato inserito in fattura tramite un ordine devo sanare la qta_evasa
        if (!empty($idordine)) {
            $dbo->query('UPDATE or_righe_ordini SET qta_evasa=qta_evasa-'.$qta.' WHERE qta='.prepare($qta).' AND idarticolo='.prepare($idarticolo).' AND idordine='.prepare($idordine));
        }
    }

    // Elimino la riga dal documento
    $dbo->query('DELETE FROM `co_righe_documenti` WHERE id='.prepare($idrigadocumento).' AND iddocumento='.prepare($iddocumento));

    // Aggiorno lo stato dell'ordine
    if (setting('Cambia automaticamente stato ordini fatturati') && !empty($idordine)) {
        $dbo->query('UPDATE or_ordini SET idstatoordine=(SELECT id FROM or_statiordine WHERE descrizione="'.get_stato_ordine($idordine).'") WHERE id = '.prepare($idordine));
    }

    // Aggiorno lo stato del ddt
    if (setting('Cambia automaticamente stato ddt fatturati') && !empty($idddt)) {
        $dbo->query('UPDATE dt_ddt SET idstatoddt=(SELECT id FROM dt_statiddt WHERE descrizione="'.get_stato_ddt($idddt).'") WHERE id = '.prepare($idddt));
    }

    // Elimino i movimenti avvenuti nel magazzino per questo articolo lotto, serial, altro
    $dbo->query('DELETE FROM `mg_movimenti` WHERE idarticolo = '.prepare($idarticolo).' AND iddocumento = '.prepare($iddocumento).' AND id = '.prepare($idrigadocumento));

    // Elimino i seriali utilizzati dalla riga
    $dbo->query('DELETE FROM `mg_prodotti` WHERE id_articolo = '.prepare($idarticolo).' AND id_riga_documento = '.prepare($idrigadocumento));

    return true;
}

function rimuovi_riga_fattura($id_documento, $id_riga, $dir)
{
    $dbo = database();

    // Leggo la quantità di questo articolo in fattura
    $riga = $dbo->fetchOne('SELECT * FROM co_righe_documenti WHERE id='.prepare($id_riga));

    $non_rimovibili = seriali_non_rimuovibili('id_riga_documento', $id_riga, $dir);
    if (!empty($non_rimovibili)) {
        return false;
    }

    $serials = $dbo->fetchArray('SELECT serial FROM mg_prodotti WHERE serial IS NOT NULL AND id_riga_documento='.prepare($id_riga));

    // Elimino la riga dal documento
    $dbo->query('DELETE FROM `co_righe_documenti` WHERE id='.prepare($id_riga).' AND iddocumento='.prepare($id_documento));

    if (empty($riga['qta'])) {
        return true;
    }

    // Operazioni per la rimozione degli articoli
    if (!empty($riga['idarticolo'])) {
        // Movimentazione articoli se da interventi o ddt
        if (empty($riga['idintervento']) && empty($riga['idddt'])) {
            add_movimento_magazzino($riga['idarticolo'], ($dir == 'entrata') ? $riga['qta'] : -$riga['qta'], ['iddocumento' => $id_documento]);
        }

        // Se l'articolo è stato inserito in fattura tramite un preventivo devo sanare la qta_evasa
        if (!empty($riga['idpreventivo'])) {
            $dbo->query('UPDATE co_righe_preventivi SET qta_evasa=qta_evasa-'.$riga['qta'].' WHERE qta='.prepare($riga['qta']).' AND idarticolo='.prepare($riga['idarticolo']).' AND idpreventivo='.prepare($riga['idpreventivo']).' AND qta_evasa > 0 LIMIT 1');
        }

        // Se l'articolo è stato inserito in fattura tramite un ddt devo sanare la qta_evasa
        if (!empty($riga['idddt'])) {
            $dbo->query('UPDATE dt_righe_ddt SET qta_evasa=qta_evasa-'.$riga['qta'].' WHERE qta='.prepare($riga['qta']).' AND idarticolo='.prepare($riga['idarticolo']).' AND idddt='.prepare($riga['idddt']).' AND qta_evasa > 0 LIMIT 1');
        }

        // Se l'articolo è stato inserito in fattura tramite un ordine devo sanare la qta_evasa
        elseif (!empty($riga['idordine'])) {
            $dbo->query('UPDATE or_righe_ordini SET qta_evasa=qta_evasa-'.$riga['qta'].' WHERE qta='.prepare($riga['qta']).' AND idarticolo='.prepare($riga['idarticolo']).' AND idordine='.prepare($riga['idordine']).' AND qta_evasa > 0 LIMIT 1');
        }
    }

    // Nota di credito
    if (!empty($riga['ref_riga_documento'])) {
        $dbo->query('UPDATE co_righe_documenti SET qta_evasa = qta_evasa+'.$riga['qta'].' WHERE id='.prepare($riga['ref_riga_documento']));

        if (!empty($riga['idarticolo'])) {
            $serials = array_column($serials, 'serial');
            $serials = array_clean($serials);

            $dbo->attach('mg_prodotti', ['id_riga_documento' => $riga['ref_riga_documento'], 'dir' => $dir, 'id_articolo' => $riga['idarticolo']], ['serial' => $serials]);
        }
    }

    // Rimozione articoli collegati ad un preventivo importato con riga unica
    if (empty($riga['idarticolo']) && $riga['idpreventivo']) {
        //rimetto a magazzino gli articoli collegati al preventivo
        $rsa = $dbo->fetchArray('SELECT id, idarticolo, qta FROM co_righe_preventivi WHERE idpreventivo = '.prepare($riga['idpreventivo']));
        for ($i = 0; $i < sizeof($rsa); ++$i) {
            if ($riga['is_preventivo']) {
                if (!empty($rsa[$i]['idarticolo'])) {
                    add_movimento_magazzino($rsa[$i]['idarticolo'], $rsa[$i]['qta'], ['iddocumento' => $id_documento]);
                }
            } else {
                $qta_evasa = $rsa[$i]['qta_evasa'] + $riga['qta'];
                // Ripristino le quantità da evadere nel preventivo
                $dbo->update('co_righe_preventivi',
                    [
                        'qta_evasa' => $qta_evasa,
                    ],
                    [
                        'id' => $rsa[$i]['id'],
                    ]
                );
            }
        }
    }

    // Rimozione articoli collegati ad un contratto importato con riga unica
    if (empty($riga['idarticolo']) && $riga['idcontratto']) {
        //rimetto a magazzino gli articoli collegati al contratto
        $rsa = $dbo->fetchArray('SELECT id, idarticolo, qta FROM co_righe_contratti WHERE idcontratto = '.prepare($riga['idcontratto']));
        for ($i = 0; $i < sizeof($rsa); ++$i) {
            if ($riga['is_contratto']) {
                if (!empty($rsa[$i]['idarticolo'])) {
                    add_movimento_magazzino($rsa[$i]['idarticolo'], $rsa[$i]['qta'], ['iddocumento' => $id_documento]);
                }
            } else {
                $qta_evasa = $rsa[$i]['qta_evasa'] + $riga['qta'];
                // Ripristino le quantità da evadere nel contratto
                $dbo->update('co_righe_contratti',
                    [
                        'qta_evasa' => $qta_evasa,
                    ],
                    [
                        'id' => $rsa[$i]['id'],
                    ]
                );
            }
        }
    }

    //Rimozione righe generiche
    if (empty($riga['idarticolo'])) {
        // TODO: possibile ambiguità tra righe molto simili tra loro
        // Se l'articolo è stato inserito in fattura tramite un ddt devo sanare la qta_evasa
        if (!empty($riga['idddt'])) {
            $dbo->query('UPDATE dt_righe_ddt SET qta_evasa=qta_evasa-'.$riga['qta'].' WHERE qta='.prepare($riga['qta']).' AND descrizione='.prepare($riga['descrizione']).' AND idddt='.prepare($riga['idddt']));
        }

        // TODO: possibile ambiguità tra righe molto simili tra loro
        // Se l'articolo è stato inserito in fattura tramite un ordine devo sanare la qta_evasa
        if (!empty($riga['idordine'])) {
            $dbo->query('UPDATE or_righe_ordini SET qta_evasa=qta_evasa-'.$riga['qta'].' WHERE qta='.prepare($riga['qta']).' AND descrizione='.prepare($riga['descrizione']).' AND idordine='.prepare($riga['idordine']));
        }
    }

    // Aggiorno lo stato dell'ordine
    if (!empty($riga['idordine']) && setting('Cambia automaticamente stato ordini fatturati')) {
        $dbo->query('UPDATE or_ordini SET idstatoordine = (SELECT id FROM or_statiordine WHERE descrizione = '.prepare(get_stato_ordine($riga['idordine'])).') WHERE id = '.prepare($riga['idordine']));
    }

    // Aggiorno lo stato del ddt
    if (!empty($riga['idddt']) && setting('Cambia automaticamente stato ddt fatturati')) {
        $dbo->query('UPDATE dt_ddt SET idstatoddt = (SELECT id FROM dt_statiddt WHERE descrizione = '.prepare(get_stato_ddt($riga['idddt'])).') WHERE id = '.prepare($riga['idddt']));
    }

    // Elimino i movimenti avvenuti nel magazzino per questo articolo lotto, serial, altro
    $dbo->query('DELETE FROM `mg_movimenti` WHERE idarticolo = '.prepare($riga['idarticolo']).' AND iddocumento = '.prepare($id_documento).' AND id = '.prepare($id_riga));

    // Elimino i seriali utilizzati dalla riga
    $dbo->query('DELETE FROM `mg_prodotti` WHERE id_articolo = '.prepare($riga['idarticolo']).' AND id_riga_documento = '.prepare($id_riga));

    return true;
}
