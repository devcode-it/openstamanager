<?php

include_once __DIR__.'/../../core.php';

// carica intervento
$idpreventivo = save($_GET['idpreventivo']);

// Lettura dati preventivo e interventi
$q = 'SELECT *, (SELECT orario_inizio FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento = in_interventi.id) AS data, (SELECT SUM(km) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento = in_interventi.id) AS km, co_preventivi.descrizione AS `cdescrizione`, (SELECT SUM(subtotale) as totale_budget FROM `co_righe_preventivi` WHERE idpreventivo = '.$idpreventivo." )  AS `budget` , co_preventivi.idanagrafica AS `idanagrafica`, (SELECT costo_orario FROM in_tipiintervento WHERE idtipointervento=in_interventi.idtipointervento) AS src_costo_orario, (SELECT costo_km FROM in_tipiintervento WHERE idtipointervento=in_interventi.idtipointervento) AS src_costo_km, (SELECT costo_diritto_chiamata FROM in_tipiintervento WHERE idtipointervento=in_interventi.idtipointervento) AS src_diritto_chiamata, (SELECT SUM(prezzo_ore_consuntivo) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_ore_consuntivo`, (SELECT SUM(prezzo_km_consuntivo) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_km_consuntivo`,  (SELECT SUM(sconto) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_sconto_ore`, (SELECT SUM(scontokm) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_sconto_km` FROM co_preventivi LEFT OUTER JOIN (co_preventivi_interventi LEFT OUTER JOIN in_interventi ON co_preventivi_interventi.idintervento=in_interventi.id) ON co_preventivi.id=co_preventivi_interventi.idpreventivo WHERE co_preventivi.id='".$idpreventivo."' ORDER BY data DESC";
$rspreventivii = $dbo->fetchArray($q);
$idcliente = $rspreventivii[0]['idanagrafica'];
$budget = Translator::numberToLocale($rspreventivii[0]['budget'], 2);

// carica report html
$report = file_get_contents($docroot.'/templates/preventivi_cons/preventivo.html');
$body = file_get_contents($docroot.'/templates/preventivi_cons/preventivo_body.html');

include_once $docroot.'/templates/pdfgen_variables.php';

$totrows = sizeof($rspreventivii);
$totale_km = 0;
$totale_ore = 0;
$totale = 0;
$preventivi = [];
$ore = [];
$km = [];
$dc = [];
$ntecnici = [];
$tecnici = [];
$costi_orari = [];
$costi_km = [];
$idinterventi = [];

if ($totrows > 0) {
    for ($i = 0; $i < $totrows; ++$i) {
        if (!empty($rspreventivii[$i]['codice'])) {
            // Lettura numero tecnici collegati all'intervento
            $query = 'SELECT an_anagrafiche.idanagrafica, ragione_sociale, in_interventi_tecnici.ore, in_interventi_tecnici.km, prezzo_dirittochiamata FROM in_interventi_tecnici LEFT OUTER JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico=an_anagrafiche.idanagrafica WHERE idintervento="'.$rspreventivii[$i]['id'].'"';
            $rst = $dbo->fetchArray($query);
            $n_tecnici = sizeof($rst);
            $tecnici_full = [];
            $dc_tecnici = 0;
            $t = 0;

            for ($j = 0; $j < $n_tecnici; ++$j) {
                $t1 = $rst[$j]['ore'];

                array_push($tecnici_full, $rst[$j]['ragione_sociale']);

                // Conteggio ore totali
                $t += $rst[$j]['ore'];

                $dc_tecnici += $rst[$j]['prezzo_dirittochiamata'];
            }

            $desc = str_replace("\n", '<br/>&nbsp;&nbsp;', '<small>'.$rspreventivii[$i]['descrizione'].'</small>');
            $line = 'Attività <b>'.$rspreventivii[$i]['codice'].'</b> del <b>'.Translator::dateToLocale($rspreventivii[$i]['data'])."</b><br/>\n&nbsp;&nbsp;".$desc;

            array_push($preventivi, $line);
            array_push($km, $rspreventivii[$i]['km']);
            array_push($ore, $t);
            array_push($dc, $dc_tecnici);
            array_push($ntecnici, $n_tecnici);
            array_push($tecnici, implode(', ', array_unique($tecnici_full)));
            $totale_ore += $t;
            $totale_km += floatval($rspreventivii[$i]['km']);
            $totale_diritto_chiamata += $dc_tecnici;
        }
    }
}

$body .= '<big><b>PREVENTIVO: '.$rspreventivii[0]['nome']."</b></big><br/><br/>\n";
$body .= '<span>'.str_replace("\n", '<br/>', $rspreventivii[0]['cdescrizione'])."</span><br/>\n";

// Sostituisco i valori tra | | con il valore del campo del db
$body .= preg_replace('/|(.+?)|/', $rspreventivii[0]['${1}'], $body);

if (sizeof($preventivi) > 0) {
    // Tabella con riepilogo interventi, km e ore
    $body .= "<table class=\"table_values\" border=\"0\">\n";
    $body .= "<col width='237'><col width='60'><col width='60'><col width='60'><col width='60'><col width='60'><col width='60'>\n";
    $body .= "<tr><th align=\"left\"><small>Attività</small></th>\n";
    $body .= "<th align=\"center\"><small>Km</small></th>\n";
    $body .= "<th align=\"center\"><small>Costo&nbsp;al&nbsp;km</small></th>\n";
    $body .= "<th align=\"center\"><small>Ore</small></th>\n";
    $body .= "<th align=\"center\"><small>Costo&nbsp;orario</small></th>\n";
    $body .= "<th align=\"center\"><small>Diritto ch.</small></th>\n";
    $body .= "<th align=\"center\"><small>Subtotale</small></th></tr>\n";

    $body .= "<tbody>\n";

    // Tabella con i dati
    for ($j = 0; $j < sizeof($preventivi); ++$j) {
        $body .= "<tr><td align=\"left\" valign=\"top\">\n";
        $body .= $preventivi[$j]."<br/>\n";
        $body .= '<span style="font-size:10px; color:#777;"><b>Tecnici:</b><br/>'.$tecnici[$j]."</span>\n";
        $body .= "</td>\n";

        // Km
        $body .= "<td align=\"right\" valign=\"top\">\n";
        $body .= Translator::numberToLocale($km[$j], 2);
        $body .= "</td>\n";

        // Costo unitario km
        $body .= "<td align=\"right\" valign=\"top\">\n";
        $body .= Translator::numberToLocale($rspreventivii[$j]['src_costo_km'], 2);

        $body .= "</td>\n";

        // Ore
        $body .= "<td align=\"right\" valign=\"top\">\n";
        $body .= Translator::numberToLocale($ore[$j], 2);
        $body .= "</td>\n";

        // Costo unitario ore
        $body .= "<td align=\"right\" valign=\"top\">\n";
        $body .= Translator::numberToLocale($rspreventivii[$j]['src_costo_orario'], 2);
        $body .= "</td>\n";

        // Diritto chiamata
        $body .= "<td align=\"right\" valign=\"top\">\n";
        $body .= Translator::numberToLocale($dc[$j], 2);
        $body .= "</td>\n";

        // Subtotale
        $subtotale = $rspreventivii[$j]['src_costo_km'] * $km[$j] + $rspreventivii[$j]['src_costo_orario'] * $ore[$j] + $rspreventivii[$j]['src_diritto_chiamata'];
        $body .= "<td align=\"right\" valign=\"top\">\n";
        $body .= Translator::numberToLocale($subtotale, 2);

        // Sconto ore + km
        if ($rspreventivii[$j]['tot_sconto_ore'] + $rspreventivii[$j]['tot_sconto_km'] > 0) {
            $body .= "<br><small style='color:#aaa;'>".Translator::numberToLocale(-($rspreventivii[$j]['tot_sconto_ore'] + $rspreventivii[$j]['tot_sconto_km']), 2).'</small>';
        }

        $body .= "</td></tr>\n";

        $totale += $subtotale;
        $totale_consuntivo += $rspreventivii[$j]['tot_ore_consuntivo'] + $rspreventivii[$j]['tot_km_consuntivo'];

        array_push($idinterventi, "'".$rspreventivii[$j]['id']."'");
    }

    $body .= "<tr><td style='border:0px;' align=\"right\">\n";
    $body .= "<span><b>Totale:</b></span>\n";
    $body .= "</td>\n";

    $body .= "<td align=\"right\">\n";
    $body .= '<span><b>'.Translator::numberToLocale($totale_km, 2)."</b></span>\n";
    $body .= "</td>\n";
    $body .= "<td></td>\n";

    $body .= "<td align=\"right\">\n";
    $body .= '<span><b>'.Translator::numberToLocale($totale_ore, 2)."</b></span>\n";
    $body .= "</td>\n";
    $body .= "<td></td>\n";

    $body .= "<td align=\"right\">\n";
    $body .= '<span><b>'.Translator::numberToLocale($totale_diritto_chiamata, 2)."</b></span>\n";
    $body .= "</td>\n";

    $body .= "<td align=\"right\" bgcolor=\"#dddddd\">\n";
    $body .= '<span><b>'.Translator::numberToLocale($totale, 2)." &euro;</b></span>\n";
    $body .= "</td></tr>\n";

    // Riga dello sconto
    // $sconto = $totale_consuntivo - $totale;
    // $sconto = 0;
    if ($sconto != 0) {
        /*
        $body .= "<tr><td style=\"border:0px;\" align=\"right\" colspan=\"6\">\n";
        $body .= "<span><b>Arrotondamenti:</b></span>\n";

        $body .= "</td><td align=\"center\">\n";
        $body .= "<span><b>".Translator::numberToLocale( $sconto, 2)." &euro;</b></span>\n";
        $body .= "</td></tr>\n\n";
        */

        $body .= "<tr><td style=\"border:0px;\" align=\"right\" colspan=\"6\">\n";
        $body .= "<span><b>Totale:</b></span>\n";

        $body .= "</td><td align=\"center\"  bgcolor=\"#dddddd\">\n";
        $body .= '<span><b>'.Translator::numberToLocale($totale_consuntivo, 2)." &euro;</b></span>\n";
        $body .= "</td></tr>\n";
    }
    // Fine riga dello sconto

    $totale_intervento_consuntivo += $totale_consuntivo;
    $totale_intervento_consuntivo = $totale;
    $body .= "</tbody>\n";
    $body .= "</table>\n";
}
$body .= "<br/>\n";

// Conteggio articoli utilizzati
$query = "SELECT *, (SELECT orario_inizio FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento = mg_articoli_interventi.idintervento) AS data_intervento, (SELECT percentuale FROM co_iva WHERE id=mg_articoli_interventi.idiva_vendita) AS prciva_vendita, (SELECT codice FROM mg_articoli WHERE id=idarticolo) AS codice_art, GROUP_CONCAT( CONCAT_WS(lotto, 'Lotto: ', ', '), CONCAT_WS(serial, 'SN: ', ', '), CONCAT_WS(altro, 'Altro: ', '') SEPARATOR '<br/>') AS codice, SUM(qta) AS sumqta FROM `mg_articoli_interventi` GROUP BY idarticolo, idintervento, lotto HAVING ".(!empty($idinterventi) ? 'idintervento IN('.implode(',', $idinterventi).') AND ' : '')." NOT idarticolo='0' ORDER BY idarticolo ASC";
$rs2 = $dbo->fetchArray($query);

if (sizeof($rs2) > 0) {
    $body .= "<table style=\"width:100%;\" class=\"table_values\" cellspacing=\"2\" cellpadding=\"5\" style=\"border-color:#aaa;\">\n";
    $body .= "<tr><th align='center' colspan='4'><b>Materiale utilizzato per gli interventi</b></th></tr>\n";

    $body .= "<tr><th style=\"width:130mm;\">\n";
    $body .= "<b>Articolo</b>\n";
    $body .= "</th>\n";

    $body .= "<th style=\"width:10mm;\" align=\"center\">\n";
    $body .= "<b>Q.tà</b>\n";
    $body .= "</th>\n";

    $body .= "<th style=\"width:20mm;\" align=\"center\">\n";
    $body .= "<b>Prezzo unitario</b>\n";
    $body .= "</th>\n";

    $body .= "<th style=\"width:20mm;\" align=\"center\">\n";
    $body .= "<b>Subtot</b>\n";
    $body .= "</th></tr>\n";

    $totale_articoli = 0.00;

    for ($i = 0; $i < sizeof($rs2); ++$i) {
        // Articolo
        $body .= "<tr><td class='first_cell'>\n";
        $body .= '<span>'.nl2br($rs2[$i]['descrizione'])."</span>\n";
        if ($rs2[$i]['codice'] != '' && $rs2[$i]['codice'] != 'Lotto: , SN: , Altro: ') {
            $body .= '<br/><small>'.$rs2[$i]['codice']."</small>\n";
        }

        $body .= '<br/><span><small style="color:#777;">Intervento del '.Translator::dateToLocale($rs2[$i]['data_intervento'])."</small></span>\n";
        $body .= "</td>\n";

        // Quantità
        $qta = $rs2[$i]['sumqta'];
        $body .= "<td class='table_cell' align='center'>\n";
        $body .= '<span>'.$rs2[$i]['sumqta']."</span>\n";
        $body .= "</td>\n";

        // Prezzo unitario
        $body .= "<td class='table_cell' align='center'>\n";
        $netto = $rs2[$i]['prezzo_vendita'];
        $netto = $netto + $netto / 100 * $rs2[$i]['prc_guadagno'];
        $iva = $netto / 100 * $rs2[$i]['prciva_vendita'];
        $body .= '<span>'.Translator::numberToLocale($netto, 2)." &euro;</span>\n";
        $body .= "</td>\n";

        // Prezzo di vendita
        $body .= "<td class='table_cell' align='center'>\n";
        $body .= "<span><span class='prezzo_articolo'>".Translator::numberToLocale($netto * $qta, 2)."</span> &euro;</span>\n";
        $body .= "</td></tr>\n";
        $totale_articoli += $netto * $qta;
    }

    // Totale spesa articoli
    $body .= "<tr><td colspan=\"3\" align=\"right\">\n";
    $body .= "<b>TOTALE MATERIALE UTILIZZATO:</b>\n";
    $body .= "</td>\n";

    $body .= "<td align=\"center\" bgcolor=\"#dddddd\">\n";
    $body .= '<b>'.Translator::numberToLocale($totale_articoli, 2)." &euro;</b>\n";
    $body .= "</td></tr>\n";
    $body .= "</table><br/>\n";
}

// Conteggio spese aggiuntive
$query = 'SELECT *, (SELECT orario_inizio FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento = in_righe_interventi.idintervento) AS data_intervento FROM in_righe_interventi '.(!empty($idinterventi) ? 'WHERE idintervento IN('.implode(',', $idinterventi).')' : '').' ORDER BY id ASC';
$rs2 = $dbo->fetchArray($query);

if (sizeof($rs2) > 0) {
    $body .= "<table style=\"width:100%;\" class=\"table_values\" cellspacing=\"2\" cellpadding=\"5\" style=\"border-color:#aaa;\">\n";
    $body .= "<tr><th align='center' colspan='4'><b>Spese aggiuntive</b></th></tr>\n";

    $body .= "<tr><th style=\"width:130mm;\">\n";
    $body .= "<b>Descrizione</b>\n";
    $body .= "</th>\n";

    $body .= "<th style=\"width:10mm;\" align=\"center\">\n";
    $body .= "<b>Q.tà</b>\n";
    $body .= "</th>\n";

    $body .= "<th style=\"width:20mm;\" align=\"center\">\n";
    $body .= "<b>Prezzo unitario</b>\n";
    $body .= "</th>\n";

    $body .= "<th style=\"width:20mm;\" align=\"center\">\n";
    $body .= "<b>Subtot</b>\n";
    $body .= "</th></tr>\n";

    $totale_spese = 0.00;

    for ($i = 0; $i < sizeof($rs2); ++$i) {
        // Articolo
        $body .= "<tr><td class='first_cell'>\n";
        $body .= '<span>'.$rs2[$i]['descrizione']."</span><br/>\n";
        $body .= '<span><small style="color:#777;">Intervento del '.Translator::dateToLocale($rs2[$i]['data_intervento'])."</small></span>\n";
        $body .= "</td>\n";

        // Quantità
        $qta = $rs2[$i]['qta'];
        $body .= "<td class='table_cell' align='center'>\n";
        $body .= '<span>'.Translator::numberToLocale($rs2[$i]['qta'], 2)."</span>\n";
        $body .= "</td>\n";

        // Prezzo unitario
        $body .= "<td class='table_cell' align='center'>\n";
        $netto = $rs2[$i]['prezzo'];
        $body .= '<span>'.Translator::numberToLocale($netto, 2)." &euro;</span>\n";
        $body .= "</td>\n";

        // Prezzo di vendita
        $body .= "<td class='table_cell' align='center'>\n";
        $body .= '<span>'.Translator::numberToLocale($netto * $qta, 2)." &euro;</span>\n";
        $body .= "</td></tr>\n";
        $totale_spese += $netto * $qta;
    }
    // Totale spese aggiuntive
    $body .= "<tr><td colspan=\"3\" align=\"right\">\n";
    $body .= "<b>ALTRE SPESE:</b>\n";
    $body .= "</td>\n";

    $body .= "<td align=\"center\" bgcolor=\"#dddddd\">\n";
    $body .= '<b>'.Translator::numberToLocale($totale_spese, 2)." &euro;</b>\n";
    $body .= "</td></tr>\n";
    $body .= "</table><br/>\n";
}

// Totale complessivo intervento
$body .= "<table class=\"table_values\" cellspacing=\"0\" border=\"0\" cellpadding=\"0\" style=\"table-layout:fixed; border-color:#aaa;\">\n";

$body .= "<tr><td align=\"center\" colspan=\"2\" valign=\"middle\" style=\"width:194.5mm;font-size:11pt;border:0px;\"><b></b></td></tr>\n";

// IMPONIBILE
$body .= "<tr><td align=\"right\"  >\n";
$body .= "<b>IMPONIBILE:</b>\n";
$body .= "</td>\n";

$body .= "<td align=\"center\" bgcolor=\"#cccccc\" style=\"width:85mm\ >\n";
$totale = Translator::numberToLocale($totale_intervento_consuntivo + $totale_articoli + $totale_spese, 2);
$body .= '<b>'.Translator::numberToLocale($totale_intervento_consuntivo + $totale_articoli + $totale_spese, 2)." &euro;</b>\n";
$body .= "</td></tr>\n";

// IVA
$q = "SELECT * FROM co_iva INNER JOIN zz_settings WHERE co_iva.id = zz_settings.valore AND zz_settings.nome = 'Iva predefinita' ";
$rs = $dbo->fetchArray($q);
$percentuale_iva = $rs[0]['percentuale'];

$body .= "<tr><td align=\"right\" >\n";
$body .= '<b>IVA ('.number_format($percentuale_iva, 0)."%):</b>\n";
$body .= "</td>\n";

$body .= "<td align=\"center\" bgcolor=\"#cccccc\" >\n";
$body .= '<b>'.Translator::numberToLocale(($totale_intervento_consuntivo + $totale_articoli + $totale_spese) / 100 * $percentuale_iva, 2)." &euro;</b>\n";
$body .= "</td></tr>\n";

// TOTALE (IMPONIBILE + IVA)
$body .= "<tr><td align=\"right\" >\n";
$body .= "<b>TOTALE CONSUNTIVO:</b>\n";
$body .= "</td>\n";

$body .= "<td align=\"center\" bgcolor=\"#cccccc\" >\n";
$totale_ivato = Translator::numberToLocale(($totale_intervento_consuntivo + $totale_articoli + $totale_spese) + ($totale_intervento_consuntivo + $totale_articoli + $totale_spese) / 100 * $percentuale_iva, 2);
$body .= '<b>'.$totale_ivato." &euro;</b>\n";
$body .= "</td></tr>\n";

// BUDGET
$body .= "<tr><td align=\"right\" >\n";
$body .= "<b>BUDGET (NO IVA):</b>\n";
$body .= "</td>\n";

$body .= "<td align=\"center\" bgcolor=\"#cccccc\" >\n";
$body .= '<b>'.$budget." &euro;</b>\n";
$body .= "</td></tr>\n";

$body .= "<tr><td align=\"right\" width=\"131mm\">\n";
$body .= "<b>RAPPORTO BUDGET/SPESA (NO IVA):</b>\n";
$body .= "</td>\n";

$diff = Translator::numberToLocale($budget - $totale, 2);
$body .= "<td align=\"center\" bgcolor=\"#cccccc\" width=\"24mm\">\n";
$body .= '<b>'.$diff." &euro;</b>\n";
$body .= "</td></tr>\n";

$body .= "</table>\n";

$report_name = 'preventivo_'.$idpreventivo.'_cons.pdf';
