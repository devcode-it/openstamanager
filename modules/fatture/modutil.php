<?php

/**
 * Funzione per generare un nuovo numero per la fattura.
 */
function get_new_numerofattura($data)
{
    global $dbo;
    global $dir;
    global $id_segment;

    if ($dir == 'uscita') {
        // recupero maschera per questo segmento
        $rs_maschera = $dbo->fetchArray("SELECT pattern FROM zz_segments WHERE id = '".$id_segment."'");
        // esempio: ####/YY
        $maschera = $rs_maschera[0]['pattern'];

        // estraggo blocchi di caratteri standard da sostituire
        preg_match('/[#]+/', $maschera, $m1);
        preg_match('/[Y]+/', $maschera, $m2);

        $query = "SELECT numero FROM co_documenti WHERE DATE_FORMAT(data,'%Y') = ".prepare(date('Y', strtotime($data)))." AND id_segment = '".$id_segment."'";

        $pos1 = strpos($maschera, $m1[0]);
        if ($pos1 == 0):
            $query .= ' ORDER BY CAST(numero AS UNSIGNED) DESC LIMIT 0,1'; else:
            $query .= ' ORDER BY numero DESC LIMIT 0,1';
        endif;

        $rs_ultima_fattura = $dbo->fetchArray($query);

        //$numero = get_next_code( $rs_ultima_fattura[0]['numero'], 1, $maschera );
        $numero = Util\Generator::generate($maschera, $rs_ultima_fattura[0]['numero']);

        // sostituisco anno nella maschera
        $anno = substr(date('Y', strtotime($data)), -strlen($m2[0])); // nel caso ci fosse YY
        $numero = str_replace($m2[0], $anno, $numero);

    /*echo $numero;
    echo $maschera;
    echo $query;
    exit;*/
    } else {
        $query = "SELECT IFNULL(MAX(numero),'0') AS max_numerofattura FROM co_documenti WHERE DATE_FORMAT( data, '%Y' ) = ".prepare(date('Y', strtotime($data))).' AND idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir = '.prepare($dir).') ORDER BY CAST(numero AS UNSIGNED) DESC LIMIT 0, 1';
        $rs = $dbo->fetchArray($query);

        $numero = $rs[0]['max_numerofattura'] + 1;
    }

    return $numero;
}

/**
 * Funzione per calcolare il numero secondario successivo utilizzando la maschera dalle impostazioni.
 */
function get_new_numerosecondariofattura($data)
{
    global $dbo;
    global $dir;
    global $idtipodocumento;
    global $id_segment;

    // recupero maschera per questo segmento
    $rs_maschera = $dbo->fetchArray("SELECT pattern FROM zz_segments WHERE id = '".$id_segment."'");
    // esempio: ####/YY
    $maschera = $rs_maschera[0]['pattern'];

    // estraggo blocchi di caratteri standard da sostituire
    preg_match('/[#]+/', $maschera, $m1);
    preg_match('/[Y]+/', $maschera, $m2);

    $query = "SELECT numero_esterno FROM co_documenti WHERE DATE_FORMAT(data,'%Y') = ".prepare(date('Y', strtotime($data)))." AND id_segment='".$id_segment."'";
    // Marzo 2017
    // nel caso ci fossero lettere prima della maschera ### per il numero (es. FT-0001-2017)
    // è necessario l'ordinamento alfabetico "ORDER BY numero_esterno" altrimenti
    // nel caso di maschere del tipo 001-2017 è necessario l'ordinamento numerico "ORDER BY CAST(numero_esterno AS UNSIGNED)"
    $pos1 = strpos($maschera, $m1[0]);
    if ($pos1 == 0):
        $query .= ' ORDER BY CAST(numero_esterno AS UNSIGNED) DESC LIMIT 0,1'; else:
        $query .= ' ORDER BY numero_esterno DESC LIMIT 0,1';
    endif;

    $rs_ultima_fattura = $dbo->fetchArray($query);

    //$numero_esterno = get_next_code( $rs_ultima_fattura[0]['numero_esterno'], 1, $maschera );
    $numero_esterno = Util\Generator::generate($maschera, $rs_ultima_fattura[0]['numero_esterno']);

    /*echo $id_segment."<br>";
    echo $query."<br>";
    echo  $rs_ultima_fattura[0]['numero_esterno']."<br>";
    echo $maschera."<br>";
    echo $numero_esterno."<br>";
    exit;*/

    // sostituisco anno nella maschera
    $anno = substr(date('Y', strtotime($data)), -strlen($m2[0])); // nel caso ci fosse YY
    $numero_esterno = str_replace($m2[0], $anno, $numero_esterno);

    return $numero_esterno;
}

/**
 * Elimina una scadenza in base al codice documento.
 */
function elimina_scadenza($iddocumento)
{
    global $dbo;

    $query2 = 'DELETE FROM co_scadenziario WHERE iddocumento='.prepare($iddocumento);
    $dbo->query($query2);
}

/**
 * Funzione per ricalcolare lo scadenziario di una determinata fattura
 * $iddocumento	string		E' l'id del documento di cui ricalcolare lo scadenziario
 * $pagamento		string		Nome del tipo di pagamento. Se è vuoto lo leggo da co_pagamenti_documenti, perché significa che devo solo aggiornare gli importi.
 */
function aggiungi_scadenza($iddocumento, $pagamento = '')
{
    global $dbo;

    $totale_da_pagare = 0.00;
    $totale_fattura = get_totale_fattura($iddocumento);
    $netto_fattura = get_netto_fattura($iddocumento);
    $imponibile_fattura = get_imponibile_fattura($iddocumento);
    $totale_iva = sum(abs($totale_fattura), -abs($imponibile_fattura));

    // Lettura data di emissione fattura
    $query3 = 'SELECT ritenutaacconto, data FROM co_documenti WHERE id='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query3);
    $data = $rs[0]['data'];
    $ritenutaacconto = $rs[0]['ritenutaacconto'];

    // Verifico se la fattura è di acquisto o di vendita per scegliere che segno mettere nel totale
    $query2 = 'SELECT dir FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id='.prepare($iddocumento);
    $rs2 = $dbo->fetchArray($query2);
    $dir = $rs2[0]['dir'];

    /*
        Inserisco la nuova scadenza (anche più di una riga per pagamenti multipli
    */
    // Se il pagamento non è specificato lo leggo dal documento
    if ($pagamento == '') {
        $query = 'SELECT descrizione FROM co_pagamenti WHERE id=(SELECT idpagamento FROM co_documenti WHERE id='.prepare($iddocumento).')';
        $rs = $dbo->fetchArray($query);
        $pagamento = $rs[0]['descrizione'];
    }

    $query4 = 'SELECT * FROM co_pagamenti WHERE descrizione='.prepare($pagamento);
    $rs = $dbo->fetchArray($query4);
    for ($i = 0; $i < sizeof($rs); ++$i) {
        // X giorni esatti
        if ($rs[$i]['giorno'] == 0) {
            $scadenza = date('Y-m-d', strtotime($data.' +'.$rs[$i]['num_giorni'].' day'));
        }

        // Ultimo del mese
        elseif ($rs[$i]['giorno'] < 0) {
            $date = new DateTime($data);

            $add = floor($rs[$i]['num_giorni'] / 30);
            for ($c = 0; $c < $add; ++$c) {
                $date->modify('last day of next month');
            }

            // Ultimo del mese più X giorni
            $giorni = -$rs[$i]['giorno'] - 1;
            if ($giorni > 0) {
                $date->modify('+'.($giorni).' day');
            }

            $scadenza = $date->format('Y-m-d');
        }

        // Giorno preciso del mese
        else {
            $scadenza = date('Y-m-'.$rs[$i]['giorno'], strtotime($data.' +'.$rs[$i]['num_giorni'].' day'));
        }

        // All'ultimo ciclo imposto come cifra da pagare il totale della fattura meno gli importi già inseriti in scadenziario per evitare di inserire cifre arrotondate "male"
        if ($i == (sizeof($rs) - 1)) {
            $da_pagare = sum($netto_fattura, -$totale_da_pagare);
        }

        // Totale da pagare (totale x percentuale di pagamento nei casi pagamenti multipli)
        else {
            $da_pagare = sum($netto_fattura / 100 * $rs[$i]['prc'], 0);
        }
        $totale_da_pagare = sum($da_pagare, $totale_da_pagare);

        if ($dir == 'uscita') {
            $da_pagare = -$da_pagare;
        }

        $dbo->query('INSERT INTO co_scadenziario(iddocumento, data_emissione, scadenza, da_pagare, pagato, tipo) VALUES('.prepare($iddocumento).', '.prepare($data).', '.prepare($scadenza).', '.prepare($da_pagare).", 0, 'fattura')");
    }

    // Se c'è una ritenuta d'acconto, la aggiungo allo scadenzario
    if ($dir == 'uscita' && $ritenutaacconto > 0) {
        $dbo->query('INSERT INTO co_scadenziario(iddocumento, data_emissione, scadenza, da_pagare, pagato, tipo) VALUES('.prepare($iddocumento).', '.prepare($data).', '.prepare(date('Y-m', strtotime($data.' +1 month')).'-15').', '.prepare(-$ritenutaacconto).", 0, 'ritenutaacconto')");
    }

    return true;
}

/**
 * Funzione per aggiornare lo stato dei pagamenti nello scadenziario
 * $iddocumento			int			ID della fattura
 * $totale_pagato			float		Totale importo pagato
 * $data_pagamento			datetime	Data in cui avviene il pagamento (yyyy-mm-dd).
 */
function aggiorna_scadenziario($iddocumento, $totale_pagato, $data_pagamento)
{
    global $dbo;

    // Lettura righe scadenziario
    $query = "SELECT * FROM co_scadenziario WHERE iddocumento='$iddocumento' AND ABS(pagato) < ABS(da_pagare) ORDER BY scadenza ASC";
    $rs = $dbo->fetchArray($query);
    $netto_fattura = get_netto_fattura($iddocumento);
    $rimanente = $netto_fattura;
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
                    $rimanente_da_pagare -= abs($rs[$i]['da_pagare']) - abs($rs[$i]['pagato']);
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
}

/**
 * Elimina i movimenti collegati ad una fattura.
 */
function elimina_movimento($iddocumento, $anche_prima_nota = 0)
{
    global $dbo;

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
    global $dbo;

    // Totale marca da bollo, inps, ritenuta, idagente
    $query = 'SELECT data, bollo, ritenutaacconto, rivalsainps FROM co_documenti WHERE id='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);
    $totale_bolli = $rs[0]['bollo'];
    $totale_ritenutaacconto = $rs[0]['ritenutaacconto'];
    $totale_rivalsainps = $rs[0]['rivalsainps'];
    $data_documento = $rs[0]['data'];

    $netto_fattura = get_netto_fattura($iddocumento);
    $totale_fattura = get_totale_fattura($iddocumento);
    $imponibile_fattura = get_imponibile_fattura($iddocumento);

    // Leggo l'iva predefinita per calcolare l'iva aggiuntiva sulla rivalsa inps
    $qi = 'SELECT percentuale FROM co_iva WHERE id='.prepare(get_var('Iva predefinita'));
    $rsi = $dbo->fetchArray($qi);
    $iva_rivalsainps = $totale_rivalsainps / 100 * $rsi[0]['percentuale'];

    // Lettura iva indetraibile fattura
    $query = 'SELECT SUM(iva_indetraibile) AS iva_indetraibile FROM co_righe_documenti GROUP BY iddocumento HAVING iddocumento='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);
    $iva_indetraibile_fattura = $rs[0]['iva_indetraibile'];

    // Lettura iva delle righe in fattura
    $query = 'SELECT SUM(iva) AS iva FROM co_righe_documenti GROUP BY iddocumento HAVING iddocumento='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);
    $iva_fattura = $rs[0]['iva'] + $iva_rivalsainps - $iva_indetraibile_fattura;

    // Imposto i segni + e - in base se la fattura è di acquisto o vendita
    if ($dir == 'uscita') {
        $segno_mov1_cliente = -1;
        $segno_mov2_ricavivendite = 1;
        $segno_mov3_iva = 1;

        $segno_mov4_inps = 1;
        $segno_mov5_ritenutaacconto = -1;
        $segno_mov6_bollo = 1;

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
        $segno_mov6_bollo = -1;

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
    $idconto = $rs[0]['idconto'];

    // Scrivo il movimento solo se è stato selezionato un conto
    if ($idconto != '') {
        $idmastrino = get_new_idmastrino();

        // Prendo il numero doc. esterno se c'è, altrimenti quello normale
        if (!empty($rs[0]['numero_esterno'])) {
            $numero = $rs[0]['numero_esterno'];
        } else {
            $numero = $rs[0]['numero'];
        }

        $descrizione = $rs[0]['descrizione_tipodoc']." numero $numero";

        /*
            Il mastrino si apre con almeno 3 righe di solito (esempio fattura di vendita):
            1) dare imponibile+iva al conto cliente
            2) avere imponibile sul conto dei ricavi
            3) avere iva sul conto dell'iva a credito (ed eventuale iva indetraibile sul rispettivo conto)

            aggiuntivo:
            4) eventuale rivalsa inps
            5) eventuale ritenuta d'acconto
            6) eventuale marca da bollo
        */
        // 1) Aggiungo la riga del conto cliente
        $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica,  descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '',  ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_controparte).', '.prepare(($totale_fattura + $totale_bolli) * $segno_mov1_cliente).', '.prepare($primanota).' )';
        $dbo->query($query2);

        // 2) Aggiungo il totale sul conto dei ricavi/spese scelto
        // Lettura descrizione conto ricavi/spese per ogni riga del documento
        $righe = $dbo->fetchArray('SELECT idconto, SUM(subtotale - sconto) AS imponibile FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento).' GROUP BY idconto');

        foreach ($righe as $riga) {
            // Retrocompatibilità
            $idconto_riga = !empty($riga['idconto']) ? $riga['idconto'] : $idconto;

            $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica,  descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '',  ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_riga).', '.prepare($riga['imponibile'] * $segno_mov2_ricavivendite).', '.prepare($primanota).')';
            $dbo->query($query2);
        }

        // 3) Aggiungo il totale sul conto dell'iva
        // Lettura id conto iva
        if ($iva_fattura != 0) {
            $descrizione_conto_iva = ($dir == 'entrata') ? 'Iva su vendite' : 'Iva su acquisti';
            $query = 'SELECT id, descrizione FROM co_pianodeiconti3 WHERE descrizione='.prepare($descrizione_conto_iva);
            $rs = $dbo->fetchArray($query);
            $idconto_iva = $rs[0]['id'];
            $descrizione_conto_iva = $rs[0]['descrizione'];

            $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica,  descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '',  ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_iva).', '.prepare($iva_fattura * $segno_mov3_iva).', '.prepare($primanota).')';
            $dbo->query($query2);
        }

        // Lettura id conto iva indetraibile
        if ($iva_indetraibile_fattura != 0) {
            $descrizione_conto_iva2 = 'Iva indetraibile';
            $query = 'SELECT id, descrizione FROM co_pianodeiconti3 WHERE descrizione='.prepare($descrizione_conto_iva2);
            $rs = $dbo->fetchArray($query);
            $idconto_iva2 = $rs[0]['id'];
            $descrizione_conto_iva2 = $rs[0]['descrizione'];

            $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica,  descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '',  ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_iva2).', '.prepare($iva_indetraibile_fattura * $segno_mov3_iva).', '.prepare($primanota).')';
            $dbo->query($query2);
        }

        // 4) Aggiungo la rivalsa INPS se c'è
        // Lettura id conto inps
        if ($totale_rivalsainps != 0) {
            $query = "SELECT id, descrizione FROM co_pianodeiconti3 WHERE descrizione='Erario c/INPS'";
            $rs = $dbo->fetchArray($query);
            $idconto_inps = $rs[0]['id'];
            $descrizione_conto_inps = $rs[0]['descrizione'];

            $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica,  descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '',  ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_inps).', '.prepare($totale_rivalsainps * $segno_mov4_inps).', '.prepare($primanota).')';
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
            $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica,  descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '',  ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_ritenutaacconto).', '.prepare($totale_ritenutaacconto * $segno_mov5_ritenutaacconto).', '.prepare($primanota).')';
            $dbo->query($query2);

            // AVERE nel riepilogativo clienti
            $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica,  descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '',  ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_controparte).', '.prepare(($totale_ritenutaacconto * $segno_mov5_ritenutaacconto) * -1).', '.prepare($primanota).')';
            $dbo->query($query2);
        }

        // 6) Aggiungo la marca da bollo se c'è
        // Lettura id conto marca da bollo
        if ($totale_bolli != 0) {
            $query = "SELECT id, descrizione FROM co_pianodeiconti3 WHERE descrizione='Rimborso spese marche da bollo'";
            $rs = $dbo->fetchArray($query);
            $idconto_bolli = $rs[0]['id'];
            $descrizione_conto_bolli = $rs[0]['descrizione'];

            $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica,  descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).", '',  ".prepare($descrizione.' del '.date('d/m/Y', strtotime($data)).' ('.$ragione_sociale.')').', '.prepare($idconto_bolli).', '.prepare($totale_bolli * $segno_mov6_bollo).', '.prepare($primanota).')';
            $dbo->query($query2);
        }
    }
}

/**
 * Funzione per generare un nuovo codice per il mastrino.
 */
function get_new_idmastrino()
{
    global $dbo;

    $query = 'SELECT MAX(idmastrino) AS maxidmastrino FROM co_movimenti';
    $rs = $dbo->fetchArray($query);

    return intval($rs[0]['maxidmastrino']) + 1;
}

/**
 * Calcolo imponibile fattura (totale_righe - sconto).
 */
function get_imponibile_fattura($iddocumento)
{
    global $dbo;

    $query = 'SELECT SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto) AS imponibile FROM co_righe_documenti GROUP BY iddocumento HAVING iddocumento='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);

    return $rs[0]['imponibile'];
}

/**
 * Calcolo totale fattura (imponibile + iva).
 */
function get_totale_fattura($iddocumento)
{
    global $dbo;

    // Sommo l'iva di ogni riga al totale
    $query = 'SELECT SUM(iva) AS iva FROM co_righe_documenti GROUP BY iddocumento HAVING iddocumento='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);

    // Aggiungo la rivalsa inps se c'è
    $query2 = 'SELECT rivalsainps FROM co_documenti WHERE id='.prepare($iddocumento);
    $rs2 = $dbo->fetchArray($query2);

    // Leggo l'iva predefinita per calcolare l'iva aggiuntiva sulla rivalsa inps
    $qi = 'SELECT percentuale FROM co_iva WHERE id='.prepare(get_var('Iva predefinita'));
    $rsi = $dbo->fetchArray($qi);
    $iva_rivalsainps = $rs2[0]['rivalsainps'] / 100 * $rsi[0]['percentuale'];

    return get_imponibile_fattura($iddocumento) + $rs[0]['iva'] + $iva_rivalsainps + $rs2[0]['rivalsainps'];
}

/**
 * Calcolo netto a pagare fattura (totale - ritenute - bolli).
 */
function get_netto_fattura($iddocumento)
{
    global $dbo;

    $query = 'SELECT ritenutaacconto, bollo FROM co_documenti WHERE id='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);

    return get_totale_fattura($iddocumento) - $rs[0]['ritenutaacconto'] + $rs[0]['bollo'];
}

/**
 * Calcolo iva detraibile fattura.
 */
function get_ivadetraibile_fattura($iddocumento)
{
    global $dbo;

    $query = 'SELECT SUM(iva)-SUM(iva_indetraibile) AS iva_detraibile FROM co_righe_documenti GROUP BY iddocumento HAVING iddocumento='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);

    return $rs[0]['iva_detraibile'];
}

/**
 * Calcolo iva indetraibile fattura.
 */
function get_ivaindetraibile_fattura($iddocumento)
{
    global $dbo;

    $query = 'SELECT SUM(iva_indetraibile) AS iva_indetraibile FROM co_righe_documenti GROUP BY iddocumento HAVING iddocumento='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);

    return $rs[0]['iva_indetraibile'];
}

/**
 * Ricalcola i costi aggiuntivi in fattura (rivalsa inps, ritenuta d'acconto, marca da bollo)
 * Deve essere eseguito ogni volta che si aggiunge o toglie una riga
 * $iddocumento		int		ID della fattura
 * $idrivalsainps		int		ID della rivalsa inps da applicare. Se omesso viene utilizzata quella impostata di default
 * $idritenutaacconto	int		ID della ritenuta d'acconto da applicare. Se omesso viene utilizzata quella impostata di default
 * $bolli				float	Costi aggiuntivi delle marche da bollo. Se omesso verrà usata la cifra predefinita.
 */
function ricalcola_costiagg_fattura($iddocumento, $idrivalsainps = '', $idritenutaacconto = '', $bolli = '')
{
    global $dbo;
    global $dir;

    // Se ci sono righe in fattura faccio i conteggi, altrimenti azzero gli sconti e le spese aggiuntive (inps, ritenuta, marche da bollo)
    $query = 'SELECT COUNT(id) AS righe FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento);
    $rs = $dbo->fetchArray($query);
    if ($rs[0]['righe'] > 0) {
        $totale_imponibile = get_imponibile_fattura($iddocumento);
        $totale_fattura = get_totale_fattura($iddocumento);

        // Leggo gli id dei costi aggiuntivi
        if ($dir == 'uscita') {
            $query2 = 'SELECT bollo FROM co_documenti WHERE id='.prepare($iddocumento);
            $rs2 = $dbo->fetchArray($query2);
            $bollo = $rs2[0]['bollo'];
        }

        $query = 'SELECT SUM(rivalsainps) AS rivalsainps, SUM(ritenutaacconto) AS ritenutaacconto FROM co_righe_documenti GROUP BY iddocumento HAVING iddocumento='.prepare($iddocumento);
        $rs = $dbo->fetchArray($query);
        $rivalsainps = $rs[0]['rivalsainps'];
        $ritenutaacconto = $rs[0]['ritenutaacconto'];

        if ($dir == 'entrata') {
            // Leggo l'iva predefinita per calcolare l'iva aggiuntiva sulla rivalsa inps
            $qi = 'SELECT percentuale FROM co_iva WHERE id='.prepare(get_var('Iva predefinita'));
            $rsi = $dbo->fetchArray($qi);
            $iva_rivalsainps = $rivalsainps / 100 * $rsi[0]['percentuale'];
        } else {
            // Leggo l'iva predefinita per calcolare l'iva aggiuntiva sulla rivalsa inps
            $qi = 'SELECT percentuale FROM co_iva WHERE id='.prepare(get_var('Iva predefinita'));
            $rsi = $dbo->fetchArray($qi);
            $iva_rivalsainps = $rivalsainps / 100 * $rsi[0]['percentuale'];
        }

        // Leggo la ritenuta d'acconto se c'è
        $totale_fattura = get_totale_fattura($iddocumento);

        $query = 'SELECT percentuale FROM co_ritenutaacconto WHERE id='.prepare($idritenutaacconto);
        $rs = $dbo->fetchArray($query);
        $netto_a_pagare = $totale_fattura - $ritenutaacconto;

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

        $dbo->query('UPDATE co_documenti SET ritenutaacconto='.prepare($ritenutaacconto).', rivalsainps='.prepare($rivalsainps).', iva_rivalsainps='.prepare($iva_rivalsainps).', bollo='.prepare($marca_da_bollo).' WHERE id='.prepare($iddocumento));
    } else {
        $dbo->query("UPDATE co_documenti SET ritenutaacconto='0', bollo='0', rivalsainps='0', iva_rivalsainps='0' WHERE id=".prepare($iddocumento));
    }
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
function add_articolo_infattura($iddocumento, $idarticolo, $descrizione, $idiva, $qta, $prezzo, $sconto = 0, $sconto_unitario = 0, $tipo_sconto = 'UNT', $idintervento = 0, $idconto = 0, $idum = 0)
{
    global $dbo;
    global $dir;

    global $idddt;
    if ($idddt == '') {
        $idddt = 0;
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

    if ($qta > 0) {
        $rsart = $dbo->fetchArray('SELECT abilita_serial FROM mg_articoli WHERE id='.prepare($idarticolo));

        $dbo->query('INSERT INTO co_righe_documenti(iddocumento, idarticolo, idintervento, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, qta, abilita_serial, idconto, um, `order`) VALUES ('.prepare($iddocumento).', '.prepare($idarticolo).', '.(!empty($idintervento) ? prepare($idintervento) : 'NULL').', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($prezzo).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($qta).', '.prepare($rsart[0]['abilita_serial']).', '.prepare($idconto).', '.prepare($um).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($iddocumento).'))');
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
    global $dbo;
    global $dir;

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

    // Elimino i movimenti avvenuti nel magazzino per questo articolo lotto, serial, altro
    $dbo->query('DELETE FROM `mg_movimenti` WHERE idarticolo = '.prepare($idarticolo).' AND iddocumento = '.prepare($iddocumento).' AND id = '.prepare($idrigadocumento));

    // Elimino i seriali utilizzati dalla riga
    $dbo->query('DELETE FROM `mg_prodotti` WHERE id_articolo = '.prepare($idarticolo).' AND id_riga_documento = '.prepare($idrigadocumento));

    return true;
}

function rimuovi_riga($iddocumento, $idriga)
{
}

function aggiorna_sconto($tables, $fields, $id_record, $options = [])
{
    $dbo = Database::getConnection();

    $descrizione = tr('Sconto', [], ['upper' => true]);

    // Rimozione dello sconto precedente
    $dbo->query('DELETE FROM '.$tables['row'].' WHERE sconto_globale = 1 AND '.$fields['row'].'='.prepare($id_record));

    // Individuazione del nuovo sconto
    $sconto = $dbo->select($tables['parent'], ['sconto_globale', 'tipo_sconto_globale'], [$fields['parent'] => $id_record]);
    $sconto[0]['sconto_globale'] = floatval($sconto[0]['sconto_globale']);

    // Aggiorno l'eventuale sconto gestendolo con le righe in fattura
    $iva = 0;

    if (!empty($sconto[0]['sconto_globale'])) {
        if ($sconto[0]['tipo_sconto_globale'] == 'PRC') {
			$rs = $dbo->fetchArray('SELECT SUM(subtotale - sconto) AS imponibile, SUM(iva) AS iva FROM (SELECT '.$tables['row'].'.subtotale, '.$tables['row'].'.sconto, '.$tables['row'].'.iva FROM '.$tables['row'].' WHERE '.$fields['row'].'='.prepare($id_record).') AS t');
            $subtotale = $rs[0]['imponibile'];
            $iva += $rs[0]['iva'] / 100 * $sconto[0]['sconto_globale'];
            $subtotale = -$subtotale / 100 * $sconto[0]['sconto_globale'];

            $descrizione = $descrizione.' '.Translator::numberToLocale($sconto[0]['sconto_globale']).'%';	
        } else {
			$rs = $dbo->fetchArray('SELECT SUM(subtotale - sconto) AS imponibile, SUM(iva) AS iva FROM (SELECT '.$tables['row'].'.subtotale, '.$tables['row'].'.sconto, '.$tables['row'].'.iva FROM '.$tables['row'].' WHERE '.$fields['row'].'='.prepare($id_record).') AS t');
            $subtotale = $rs[0]['imponibile'];
			$iva += $sconto[0]['sconto_globale'] * $rs[0]['iva'] / $subtotale;
			
            $subtotale = -$sconto[0]['sconto_globale'];
        }

        // Calcolo dell'IVA da scontare
        $idiva = get_var('Iva predefinita');
        $rsi = $dbo->select('co_iva', ['descrizione', 'percentuale'], ['id' => $idiva]);

        $values = [
            $fields['row'] => $id_record,
            'descrizione' => $descrizione,
            'subtotale' => $subtotale,
            'qta' => 1,
            'idiva' => $idiva,
            'desc_iva' => $rsi[0]['descrizione'],
            'iva' => -$iva,
            'sconto_globale' => 1,
            '#order' => '(SELECT IFNULL(MAX(`order`) + 1, 0) FROM '.$tables['row'].' AS t WHERE '.$fields['row'].'='.prepare($id_record).')',
        ];

        $dbo->insert($tables['row'], $values);
    }
}

function controlla_seriali($field, $id_riga, $old_qta, $new_qta, $dir)
{
    $dbo = Database::getConnection();

    if ($old_qta >= $new_qta) {
        // Controllo sulla possibilità di rimuovere i seriali (se non utilizzati da documenti di vendita)
        if ($dir == 'uscita' && $new_qta < count(seriali_non_rimuovibili($field, $id_riga, $dir))) {
            return false;
        } else {
            // Controllo sul numero di seriali effettivi da rimuovere
            $count = $dbo->fetchArray('SELECT COUNT(*) AS tot FROM mg_prodotti WHERE '.$field.'='.prepare($id_riga))[0]['tot'];
            if ($new_qta < $count) {
                $deletes = $dbo->fetchArray("SELECT id FROM mg_prodotti WHERE serial NOT IN (SELECT serial FROM mg_prodotti WHERE dir = 'entrata' AND ".$field.'!='.prepare($id_riga).') AND '.$field.'='.prepare($id_riga).' ORDER BY serial DESC LIMIT '.abs($count - $new_qta));

                // Rimozione
                foreach ($deletes as $delete) {
                    $dbo->query('DELETE FROM mg_prodotti WHERE id = '.prepare($delete['id']));
                }
            }
        }
    }

    return true;
}

function seriali_non_rimuovibili($field, $id_riga, $dir)
{
    $dbo = Database::getConnection();

    $results = [];

    if ($dir == 'uscita') {
        $results = $dbo->fetchArray("SELECT serial FROM mg_prodotti WHERE serial IN (SELECT serial FROM mg_prodotti WHERE dir = 'entrata') AND ".$field.'='.prepare($id_riga));
    }

    return $results;
}
