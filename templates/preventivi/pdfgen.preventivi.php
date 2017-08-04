<?php

include_once __DIR__.'/../../core.php';

// carica intervento
$idpreventivo = save($_GET['idpreventivo']);

$show_costi = get_var('Stampa i prezzi sui preventivi');

// Lettura dati preventivo e interventi
$q = "SELECT *, data_bozza AS data FROM co_preventivi WHERE co_preventivi.id='".$idpreventivo."'";
$rspreventivii = $dbo->fetchArray($q);
$idcliente = $rspreventivii[0]['idanagrafica'];

// carica report html
$report = file_get_contents($docroot.'/templates/preventivi/preventivo.html');
$body = file_get_contents($docroot.'/templates/preventivi/preventivo_body.html');

include_once $docroot.'/templates/pdfgen_variables.php';

$totrows = sizeof($rspreventivii);
$totale_km = 0;
$totale_ore = 0;
$totale = 0;
$preventivi = [];
$ore = [];
$km = [];
$ntecnici = [];
$tecnici = [];
$costi_orari = [];
$costi_km = [];
$idinterventi = ['-1'];

if ($totrows > 0) {
    for ($i = 0; $i < $totrows; ++$i) {
        // Lettura numero tecnici collegati all'intervento
        $query = 'SELECT an_anagrafiche.idanagrafica, ragione_sociale FROM in_interventi_tecnici LEFT OUTER JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico=an_anagrafiche.idanagrafica WHERE idintervento="'.$rspreventivii[$i]['idintervento'].'"';
        $rs = $dbo->fetchArray($query);
        $n_tecnici = sizeof($rs);
        $tecnici_full = '';
        for ($j = 0; $j < $n_tecnici; ++$j) {
            $tecnici_full .= '- '.$rs[$j]['ragione_sociale']."<br/>\n";
        }

        // Conteggio ore totali
        $t = datediff('n', $rspreventivii[$i]['ora_dal'], $rspreventivii[$i]['ora_al']);
        $ore_pausa = Translator::numberToLocale($rspreventivii[$i]['ore_pausa'], 2);
        $t = round($t / 60 - $rspreventivii[$i]['ore_pausa'], 1);

        if ($rspreventivii[$i]['data'] != '') {
            $line = '<span>Intervento del <b>'.Translator::dateToLocale($rspreventivii[$i]['data']).":</b><br/><small style='color:#444;'>".str_replace("\n", '<br/>', $rspreventivii[$i]['descrizione'])."</small></span><br/>\n";
            array_push($preventivi, $line);
        }
        array_push($km, floatval($rspreventivii[$i]['km']));
        array_push($ore, $t);
        array_push($ntecnici, $n_tecnici);
        array_push($tecnici, $tecnici_full);
        if ($rspreventivii[$i]['prezzo_ore'] > 0) {
            array_push($ore, $rspreventivii[$i]['prezzo_ore_scontato'] / $rspreventivii[$i]['prezzo_ore']);
        } else {
            array_push($ore, 0);
        }
        array_push($costi_orari, floatval($rspreventivii[$i]['costo_orario']));
        array_push($costi_km, floatval($rspreventivii[$i]['costo_km']));
        if ($rspreventivii[$i]['prezzo_ore_unitario'] > 0) {
            $totale_ore += $rspreventivii[$i]['prezzo_ore_scontato'] / $rspreventivii[$i]['prezzo_ore_unitario'];
        }

        $totale_km += floatval($rspreventivii[$i]['km']);
    }
}

// Sostituisco i valori tra | | con il valore del campo del db
$body .= preg_replace('/|(.+?)|/', $rspreventivii[0]['${1}'], $body);

// Lettura nome referenti collegati all'anagrafica
$query = 'SELECT * FROM an_referenti WHERE id = '.$rspreventivii[0]['idreferente'];
$rs = $dbo->fetchArray($query);
$nome_referente = $rs[0]['nome'];

// Tabella intestazione
$body .= "<table border='0' cellspacing='10' cellpadding='10'>\n";

$body .= "<tr><td align=\"left\" width='356' valign='top'>\n";

$body .= '<big><b>'.$f_citta.', '.$rspreventivii[0]['data']."</b></big><br/><br/>\n";
$body .= '<big><b>PREVENTIVO N<sup>o</sup> '.$rspreventivii[0]['numero'].' DEL '.$rspreventivii[0]['data']."</b></big>\n";

$body .= "</td>\n";
$body .= "<td align=\"left\" width='356' valign='top'>\n";

if ($c_cap != '') {
    $c_cap = $c_cap.' ';
}
if ($c_provincia != '') {
    $c_provincia = ' ('.$c_provincia.')';
}

$body .= '<big style="line-height:5mm" >Spettabile<br/><b>'.$c_ragionesociale.'<br>'.$c_indirizzo.'<br>'.$c_cap.$c_citta.$c_provincia.'<br> P.Iva: '.$c_piva."</b></big>\n";
$body .= "</td>\n";
$body .= "</tr>\n";

if ($nome_referente != '') {
    $body .= "<tr><td align=\"left\" colspan=\"2\" >\n";
    $body .= '<b><u>C.A.</u></b> '.$nome_referente."\n";
    $body .= "</td>\n";
    $body .= "</tr>\n";
}

$body .= "<tr><td align=\"left\" colspan=\"2\" >\n";
$body .= '<span>'.str_replace("\n", '<br/>', $rspreventivii[0]['cdescrizione'])."</span><br/><br/>\n";
$body .= "</td>\n";
$body .= "</tr>\n";

$body .= "</table>\n";

/*
    TABELLA COSTI
*/
$body .= "<table class='table_values' style='table-layout:fixed;' border='0' cellpadding='0'>\n";
$body .= "<col width='340'><col width='50'><col width='90'><col width='75'><col width='90'>\n";
$body .= "<thead>\n";
$body .= "<tr><th width='340'>Descrizione</th>\n";
$body .= "<th width='50' align='center'>Q.tà</th>\n";
$body .= "<th width='90' align='center'>Costo U.</th>\n";
$body .= "<th width='75' align='center'>Iva</th>\n";
$body .= "<th width='90' align='center'>Imponibile</th></tr>\n";
$body .= "</thead>\n";

$body .= "<tbody>\n";

$cifredecimali = get_var('Cifre decimali per importi');

// ARTICOLI
$q_art = "SELECT *, IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo),'') AS codice, (SELECT descrizione FROM co_iva WHERE id=idiva) AS desc_iva FROM `co_righe_preventivi` WHERE idpreventivo='$idpreventivo' ORDER BY id ASC";
$rs_art = $dbo->fetchArray($q_art);
$tot_art = sizeof($rs_art);

$imponibile_articoli = 0.0;
$totale_iva = 0.0;
$totale_sconto = 0.0;

for ($i = 0; $i < $tot_art; ++$i) {
    // descrizione
    $body .= "<tr><td>\n";

    if ($rs_art[$i]['codice'] != '') {
        $body .= $rs_art[$i]['codice'].' - ';
    }
    $body .= nl2br($rs_art[$i]['descrizione']);

    $body .= "</td>\n";

    // q.tà
    $body .= "<td class='table_cell' align=\"right\" valign=\"top\">\n";
    $qta = $rs_art[$i]['qta'];
    $body .= Translator::numberToLocale($rs_art[$i]['qta'], 2)."\n";
    if ($rs_art[$i]['um'] != '') {
        $body .= "<br/>\n<small style='color:#555;'>".$rs_art[$i]['um']."</small>\n";
    }
    $body .= "</td>\n";

    // Costo unitario
    $body .= "<td class='table_cell' align=\"right\" valign=\"top\">\n";
    if ($show_costi) {
        $body .= Translator::numberToLocale($rs_art[$i]['subtotale'] / $rs_art[$i]['qta'], 2)." &euro;\n";
        if ($rs_art[$i]['sconto'] > 0) {
            $body .= "<br/>\n<small style='color:#555;'>- sconto ".Translator::numberToLocale($rs_art[$i]['sconto'], 2)." &euro;</small>\n";
        }
    } else {
        $body .= '-';
    }
    $totale_sconto += ($rs_art[$i]['sconto'] * $qta);
    $body .= "</td>\n";

    $body .= "<td class='table_cell' align=\"right\" valign=\"top\">\n";
    $iva = $rs_art[$i]['iva'];
    $body .= Translator::numberToLocale($iva, 2)." &euro;<br/><small style='color:#777;'>".$rs_art[$i]['desc_iva']."</small>\n";
    $body .= "</td>\n";

    // Imponibile
    $body .= "<td class='table_cell' align=\"right\" valign=\"top\">\n";
    if ($show_costi) {
        $body .= Translator::numberToLocale($rs_art[$i]['subtotale'] - ($qta * $rs_art[$i]['sconto']), 2)." &euro;\n";
    } else {
        $body .= '-';
    }
    $body .= "</td></tr>\n";

    $imponibile_articoli += $rs_art[$i]['subtotale'];
    $totale_iva += $iva;
}

// SCONTO
if (abs($totale_sconto) > 0) {
    $body .= "<tr><td align='right' colspan='4'>\n";
    $body .= "	<b>SCONTO:</b>\n";
    $body .= "</td>\n";
    $body .= "<td align=\"right\" bgcolor=\"#cccccc\">\n";
    $body .= '	<big><b>-	'.Translator::numberToLocale($totale_sconto, 2)." &euro;</b></big>\n";
    $body .= "</td></tr>\n";
}

// Totale iva
$body .= "<tr><td align=\"right\" colspan=\"4\">\n";
$body .= "	<b>TOTALE IMPONIBILE:</b>\n";
$body .= "</td>\n";
$body .= "<td align=\"right\" bgcolor=\"#cccccc\">\n";
$body .= '	<big><b>'.Translator::numberToLocale(($imponibile_articoli - $totale_sconto), 2)." &euro;</b></big>\n";
$body .= "</td></tr>\n";

// Totale iva
$body .= "<tr><td align=\"right\" colspan=\"4\">\n";
$body .= "	<b>TOTALE IVA:</b>\n";
$body .= "</td>\n";
$body .= "<td align=\"right\" bgcolor=\"#cccccc\">\n";
$body .= '<big><b>'.Translator::numberToLocale($totale_iva, 2)." &euro;</b></big>\n";
$body .= "</td></tr>\n";

// Totale complessivo intervento
$body .= "<tr><td align=\"right\" colspan=\"4\">\n";
$body .= "<b>QUOTAZIONE TOTALE:</b>	\n";
$body .= "</td>\n";
$body .= "<td align=\"right\" bgcolor=\"#cccccc\">\n";
$body .= '<big><b>'.Translator::numberToLocale(($imponibile_articoli - $totale_sconto) + $totale_iva, 2)." &euro;</b></big>\n";
$body .= "</td></tr>\n";

$body .= "</tbody>\n";
$body .= "</table><br/><br/>\n";

// CONDIZIONI GENERALI DI FORNITURA

// Lettura pagamenti
$query = 'SELECT * FROM co_pagamenti WHERE id = '.$rspreventivii[0]['idpagamento'];
$rs = $dbo->fetchArray($query);
$pagamento = $rs[0]['descrizione'];

// Lettura resa
$query = 'SELECT * FROM dt_porto WHERE id = '.$rspreventivii[0]['idporto'];
$rs = $dbo->fetchArray($query);
$resa_materiale = $rs[0]['descrizione'];

$rspreventivii[0]['idpagamento'];

$body .= "<nobreak><table border=\"0\" cellpadding='0' cellspacing='10'>\n";
$body .= "<col width='200'><col width='510'>\n";
$body .= "<tr><td align=\"center\" valign=\"middle\" style=\"height:5mm;font-size:14pt;\" bgcolor=\"#dddddd\" colspan=\"2\">\n";
$body .= "<span><b>CONDIZIONI GENERALI DI FORNITURA</b></span>\n";
$body .= "</td></tr>\n";

// PAGAMENTI
$body .= "<tr><td>\n";
$body .= "<big><b><u>PAGAMENTI:</u></b></big>\n";
$body .= "</td>\n";

$body .= "<td>\n";
$body .= '<span><b>'.$pagamento."</b></span>\n";
$body .= "</td></tr>\n";

// RESA MATERIALI
$body .= "<tr><td>\n";
$body .= "<big><b><u>RESA MATERIALI:</u></b></big>\n";
$body .= "</td>\n";

$body .= "<td>\n";
$body .= '<span><b>'.$resa_materiale."</b></span>\n";
$body .= "</td></tr>\n";

// VALIDITA' OFFERTA
$body .= "<tr><td>\n";
$body .= "<big><b><u>VALIDIT&Agrave; OFFERTA:</u></b></big>\n";
$body .= "</td>\n";

$body .= "<td>\n";
$body .= '<span><b>'.$rspreventivii[0]['validita']." giorni</b></span>\n";
$body .= "</td></tr>\n";

// TEMPI CONSEGNA
$body .= "<tr><td>\n";
$body .= "<big><b><u>TEMPI CONSEGNA:</u></b></big>\n";
$body .= "</td>\n";

$body .= "<td>\n";
$body .= '<span><b>'.$rspreventivii[0]['tempi_consegna']." giorni</b></span>\n";
$body .= "</td></tr>\n";

// ESCLUSIONI
$body .= "<tr><td valign='top'>\n";
$body .= "<big><b><u>ESCLUSIONI:</u></b></big>\n";
$body .= "</td>\n";

$body .= "<td>\n";
$body .= '<span><b>'.nl2br($rspreventivii[0]['esclusioni'])."</b></span>\n";
$body .= "</td></tr>\n";

$body .= "<tr>\n";
$body .= "	<td colspan='2'>\n";
$body .= "		<br/><span>In attesa di un Vostro Cortese riscontro, colgo l&rsquo;occasione per porgere Cordiali Saluti.</span>\n";
$body .= "	</td>\n";
$body .= "</tr>\n";

$body .= "</table></nobreak>\n";

$report_name = 'preventivo_'.$idpreventivo.'.pdf';
