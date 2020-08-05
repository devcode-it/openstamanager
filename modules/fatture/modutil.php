<?php

use Modules\Fatture\Fattura;
use Util\Generator;

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
 *
 * @deprecated 2.4.17
 */
function elimina_scadenze($iddocumento)
{
    $fattura = Fattura::find($iddocumento);

    $fattura->rimuoviScadenze();
}

/**
 * Funzione per ricalcolare lo scadenzario di una determinata fattura
 * $iddocumento	string		E' l'id del documento di cui ricalcolare lo scadenzario
 * $pagamento		string		Nome del tipo di pagamento. Se è vuoto lo leggo da co_pagamenti_documenti, perché significa che devo solo aggiornare gli importi.
 * $pagato boolean Indica se devo segnare l'importo come pagato.
 *
 * @deprecated 2.4.17
 */
function aggiungi_scadenza($iddocumento, $pagamento = '', $pagato = false)
{
    $fattura = Fattura::find($iddocumento);

    $fattura->registraScadenze($pagato);
}

/**
 * Elimina i movimenti collegati ad una fattura.
 * Se il flag $prima_nota è impostato a 1 elimina solo i movimenti di Prima Nota, altrimenti rimuove quelli automatici.
 *
 * @param $iddocumento
 * @param int $prima_nota
 *
 * @deprecated 2.4.17
 */
function elimina_movimenti($id_documento, $prima_nota = 0)
{
    $dbo = database();

    $idmastrino = $dbo->fetchOne('SELECT idmastrino FROM co_movimenti WHERE iddocumento='.prepare($id_documento).' AND primanota='.prepare($prima_nota))['idmastrino'];

    $query2 = 'DELETE FROM co_movimenti WHERE idmastrino='.prepare($idmastrino).' AND primanota='.prepare($prima_nota);
    $dbo->query($query2);
}

/**
 * Funzione per aggiungere la fattura in prima nota
 * $iddocumento	string		E' l'id del documento da collegare alla prima nota
 * $dir			string		Direzione dell'importo (entrata, uscita)
 * $primanota		boolean		Indica se il movimento è un movimento di prima nota o un movimento normale (di default movimento normale).
 *
 * @deprecated 2.4.17
 */
function aggiungi_movimento($iddocumento, $dir, $primanota = 0)
{
    $dbo = database();

    $fattura = Modules\Fatture\Fattura::find($iddocumento);

    // Totale marca da bollo, inps, ritenuta, idagente
    $query = 'SELECT data, bollo, ritenutaacconto, rivalsainps, split_payment FROM co_documenti WHERE id='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);
    $totale_bolli = $rs[0]['bollo'];
    $totale_ritenutaacconto = $rs[0]['ritenutaacconto'];
    $totale_ritenutacontributi = $fattura->totale_ritenuta_contributi;
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
            $idconto_controparte = setting('Conto per Riepilogativo fornitori');
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
            $idconto_controparte = setting('Conto per Riepilogativo clienti');
        }
    }

    // Lettura info fattura
    $query = 'SELECT *, co_documenti.data_competenza, co_documenti.note, co_documenti.idpagamento, co_documenti.id AS iddocumento, co_statidocumento.descrizione AS `stato`, co_tipidocumento.descrizione AS `descrizione_tipodoc` FROM ((co_documenti LEFT OUTER JOIN co_statidocumento ON co_documenti.idstatodocumento=co_statidocumento.id) INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);
    $n = sizeof($rs);
    $data = $rs[0]['data_competenza'];
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

    $query2 = 'INSERT INTO co_movimenti(idmastrino, data, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_controparte).', '.prepare(($importo_cliente + $totale_bolli) * $segno_mov1_cliente).', '.prepare($primanota).' )';
    $dbo->query($query2);

    // 2) Aggiungo il totale sul conto dei ricavi/spese scelto
    // Lettura descrizione conto ricavi/spese per ogni riga del documento
    $righe = $dbo->fetchArray('SELECT idconto, SUM(subtotale - sconto) AS imponibile FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento).' GROUP BY idconto');

    foreach ($righe as $riga) {
        // Retrocompatibilità
        $idconto_riga = !empty($riga['idconto']) ? $riga['idconto'] : $idconto;

        $query2 = 'INSERT INTO co_movimenti(idmastrino, data, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_riga).', '.prepare($riga['imponibile'] * $segno_mov2_ricavivendite).', '.prepare($primanota).')';
        $dbo->query($query2);
    }

    // 3) Aggiungo il totale sul conto dell'iva
    // Lettura id conto iva
    if ($iva_fattura != 0 && !$split_payment) {
        $descrizione_conto_iva = ($dir == 'entrata') ? 'Iva su vendite' : 'Iva su acquisti';
        $idconto_iva = setting('Conto per '.$descrizione_conto_iva);

        $query2 = 'INSERT INTO co_movimenti(idmastrino, data, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_iva).', '.prepare($iva_fattura * $segno_mov3_iva).', '.prepare($primanota).')';
        $dbo->query($query2);
    }

    // Lettura id conto iva indetraibile
    if ($iva_indetraibile_fattura != 0 && !$split_payment) {
        $idconto_iva2 = setting('Conto per Iva indetraibile');

        $query2 = 'INSERT INTO co_movimenti(idmastrino, data,  iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).',  '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_iva2).', '.prepare($iva_indetraibile_fattura * $segno_mov3_iva).', '.prepare($primanota).')';
        $dbo->query($query2);
    }

    // 4) Aggiungo la rivalsa INPS se c'è
    // Lettura id conto inps
    if ($totale_rivalsainps != 0) {
        $idconto_inps = setting('Conto per Erario c/INPS');

        $query2 = 'INSERT INTO co_movimenti(idmastrino, data,  iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_inps).', '.prepare($totale_rivalsainps * $segno_mov4_inps).', '.prepare($primanota).')';
        $dbo->query($query2);
    }

    // 5) Aggiungo la ritenuta d'acconto se c'è
    // Lettura id conto ritenuta e la storno subito
    if ($totale_ritenutaacconto != 0) {
        $idconto_ritenutaacconto = setting("Conto per Erario c/ritenute d'acconto");

        // DARE nel conto ritenuta
        $query2 = 'INSERT INTO co_movimenti(idmastrino, data,  iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).',  '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_ritenutaacconto).', '.prepare($totale_ritenutaacconto * $segno_mov5_ritenutaacconto).', '.prepare($primanota).')';
        $dbo->query($query2);

        // AVERE nel riepilogativo clienti
        $query2 = 'INSERT INTO co_movimenti(idmastrino, data, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).',  '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_controparte).', '.prepare(($totale_ritenutaacconto * $segno_mov5_ritenutaacconto) * -1).', '.prepare($primanota).')';
        $dbo->query($query2);
    }

    // 6) Aggiungo la ritenuta enasarco se c'è
    // Lettura id conto ritenuta e la storno subito
    if ($totale_ritenutacontributi != 0) {
        $idconto_ritenutaenasarco = setting('Conto per Erario c/enasarco');

        // DARE nel conto ritenuta
        $query2 = 'INSERT INTO co_movimenti(idmastrino, data, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_ritenutaenasarco).', '.prepare($totale_ritenutacontributi * $segno_mov5_ritenutaacconto).', '.prepare($primanota).')';
        $dbo->query($query2);

        // AVERE nel riepilogativo clienti
        $query2 = 'INSERT INTO co_movimenti(idmastrino, data, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).',  '.prepare($iddocumento).", '', ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_controparte).', '.prepare(($totale_ritenutacontributi * $segno_mov5_ritenutaacconto) * -1).', '.prepare($primanota).')';
        $dbo->query($query2);
    }
}

/**
 * Funzione per generare un nuovo codice per il mastrino.
 *
 * @deprecated 2.4.17
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
 *
 * @deprecated 2.4.17
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
 *
 * @deprecated 2.4.11
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
 * Verifica che il numero_esterno della fattura indicata sia correttamente impostato, a partire dai valori delle fatture ai giorni precedenti.
 * Restituisce il numero_esterno mancante in caso di numero errato.
 *
 * @return bool|string
 */
function verifica_numero(Fattura $fattura)
{
    if (empty($fattura->numero_esterno)) {
        return null;
    }

    $id_segment = $fattura->id_segment;
    $data = $fattura->data;

    $documenti = Fattura::where('id_segment', $id_segment)
        ->where('data', $data)
        ->get();

    // Recupero maschera per questo segmento
    $maschera = Generator::getMaschera($id_segment);

    $ultimo = Generator::getPreviousFrom($maschera, 'co_documenti', 'numero_esterno', [
        'data < '.prepare(date('Y-m-d', strtotime($data))),
        'YEAR(data) = '.prepare(date('Y', strtotime($data))),
        'id_segment = '.prepare($id_segment),
    ]);

    do {
        $numero = Generator::generate($maschera, $ultimo, 1, Generator::dateToPattern($data));

        $filtered = $documenti->reject(function ($item, $key) use ($numero) {
            return $item->numero_esterno == $numero;
        });

        if ($documenti->count() == $filtered->count()) {
            return $numero;
        }

        $documenti = $filtered;
        $ultimo = $numero;
    } while ($numero != $fattura->numero_esterno);

    return null;
}
