<?php

include_once __DIR__.'/../../core.php';

// carica intervento
$idcontratto = save($_GET['idcontratto']);

// Lettura dati contratto
$q = "SELECT * FROM co_contratti WHERE id='".$idcontratto."'";
$rscontratti = $dbo->fetchArray($q);
$idcliente = $rscontratti[0]['idanagrafica'];

$rs = $dbo->fetchArray("SELECT SUM(qta) AS totale_ore,  SUM(subtotale) as totale_budget FROM `co_righe2_contratti` WHERE um='ore' AND idcontratto=\"".$idcontratto.'"');
$contratto_tot_ore = $rs[0]['totale_ore'];
$contratto_tot_budget = $rs[0]['totale_budget'];

// carica report html
$report = file_get_contents($docroot.'/templates/contratti_cons/contratto.html');
$body = file_get_contents($docroot.'/templates/contratti_cons/contratto_body.html');

include_once $docroot.'/templates/pdfgen_variables.php';

$totale = 0;
$contratti = [];
$ore = [];
$totale_ore_impiegate = 0;

$costo_orario = [];
$costo_km = [];
$diritto_chiamata = [];

$tot_ore_consuntivo = [];
$tot_km_consuntivo = [];
$tot_dirittochiamata = [];

$km = [];
$ntecnici = [];
$tecnici = [];
$costi_orari = [];
$costi_km = [];
$idinterventi = ["''"];

// Ciclo tra le righe degli interventi da programmare
$rs_righe = $dbo->fetchArray('SELECT * FROM co_righe_contratti WHERE idcontratto="'.$idcontratto.'" ORDER BY data_richiesta ASC');
$totrows = sizeof($rs_righe);

for ($r = 0; $r < sizeof($rs_righe); ++$r) {
    if (!empty($rs_righe[$r]['id'])) {
        $totale_ore = 0;
        $totale_km = 0;
        $totale_diritto_chiamata = 0;

        $q = 'SELECT id, codice, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data, (SELECT SUM(km) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS km, (SELECT SUM(prezzo_ore_consuntivo) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_ore_consuntivo`, (SELECT SUM(prezzo_km_consuntivo) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_km_consuntivo` FROM in_interventi WHERE id="'.$rs_righe[$r]['idintervento'].'"';
        $rscontrattii = $dbo->fetchArray($q);

        if (sizeof($rscontrattii) == 1) {
            // Lettura numero tecnici collegati all'intervento
            $query = 'SELECT an_anagrafiche.idanagrafica, prezzo_ore_consuntivo, prezzo_km_consuntivo, prezzo_ore_unitario, prezzo_km_unitario, prezzo_dirittochiamata, ragione_sociale, orario_inizio, orario_fine, in_interventi_tecnici.km FROM in_interventi_tecnici LEFT OUTER JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico=an_anagrafiche.idanagrafica WHERE idintervento="'.$rscontrattii[0]['id'].'"';
            $rst = $dbo->fetchArray($query);
            $n_tecnici = sizeof($rst);
            $tecnici_full = '';

            $t = 0;

            for ($j = 0; $j < $n_tecnici; ++$j) {
                $t1 = datediff('n', $rst[$j]['orario_inizio'], $rst[$j]['orario_fine']);

                $orario = '';
                if (floatval($t1) > 0) {
                    $orario .= Translator::timestampToLocale($rst[$j]['orario_inizio']).' - '.Translator::timestampToLocale($rst[$j]['orario_fine']);
                }

                $tecnici_full .= '<b>'.$rst[$j]['ragione_sociale'].'</b> ('.$orario.')<br/>'.Translator::numberToLocale($t1 / 60, 2).'h x '.Translator::numberToLocale($rst[$j]['prezzo_ore_unitario'], 2).' &euro;/h<br>'.Translator::numberToLocale($rst[$j]['km'], 2).'km x '.Translator::numberToLocale($rst[$j]['prezzo_km_unitario'], 2).' km/h<br>'.Translator::numberToLocale($rst[$j]['prezzo_dirittochiamata'], 2)."&euro; d.c.<br><br>\n";

                // Conteggio ore totali
                $t += $t1 / 60;

                $totale_ore += $rst[$j]['prezzo_ore_consuntivo'];
                $totale_km += $rst[$j]['prezzo_km_consuntivo'];
                $totale_diritto_chiamata += $rst[$j]['prezzo_dirittochiamata'];
            }

            $totale_ore_impiegate += $t;

            $desc = str_replace("\n", '<br>', $rscontratti[$i]['descrizione']);
            $line = 'Intervento <b>'.$rscontrattii[0]['codice'].'</b> del <b>'.Translator::dateToLocale($rscontrattii[0]['data']).'</b><br>'.$desc;

            array_push($contratti, $line);
            array_push($tot_ore_consuntivo, $totale_ore);
            array_push($tot_km_consuntivo, $totale_km);
            array_push($tot_dirittochiamata, $totale_diritto_chiamata);

            array_push($ntecnici, $n_tecnici);
            array_push($tecnici, $tecnici_full);
            array_push($idinterventi, "'".$rscontrattii[0]['codice']."'");
        }
    }

    // Visualizzo i dati degli interventi programmati
    else {
        $line = 'Da programmare entro il '.Translator::dateToLocale($rs_righe[$r]['data_richiesta']);

        array_push($contratti, $line);
        array_push($km, 0);
        array_push($ore, 0);

        array_push($costo_orario, 0);
        array_push($costo_km, 0);
        array_push($diritto_chiamata, 0);

        array_push($tot_ore_consuntivo, 0);
        array_push($tot_km_consuntivo, 0);

        array_push($ntecnici, 0);
        array_push($tecnici, '-');
    }
}

$body .= '<big><b>&gt; CONSUNTIVO CONTRATTO: '.$rscontratti[0]['nome']."</b></big>\n";
$body .= '<span>'.str_replace("\n", '<br/>', $rscontratti[0]['cdescrizione'])."</span><br/>\n";

// Sostituisco i valori tra | | con il valore del campo del db
$body .= preg_replace('/|(.+?)|/', $rscontratti[0]['${1}'], $body);

if (sizeof($contratti) > 0) {
    // Tabella con riepilogo interventi, km e ore
    $body .= "<table class=\"table_values\" width=\"100%\" border=\"0\">\n";
    $body .= "<thead>\n";
    $body .= "<tr><th width='200' align=\"left\">Interventi</th>\n";
    $body .= "<th width='400' align=\"center\">Tecnici</th>\n";
    $body .= "<th width='100' align=\"center\">Subtotale</th></tr>\n";
    $body .= "</thead>\n";

    $body .= "<tbody>\n";

    // Tabella con i dati
    for ($j = 0; $j < sizeof($contratti); ++$j) {
        // Intervento (+ tecnici)
        $body .= "<tr><td valign='top'>\n";
        $body .= '	'.$contratti[$j]."</td><td><span style='color:#555; font-size:11px;'>".$tecnici[$j]."</span>\n";
        $body .= "</td>\n";

        // Subtotale
        $subtotale = $tot_ore_consuntivo[$j] + $km[$j] * $costo_km[$j] + $diritto_chiamata[$j];
        $body .= "<td valign=\"top\" align='right'>\n";
        $body .= '	'.Translator::numberToLocale($subtotale, 2)."\n";
        $body .= "</td></tr>\n";
        $totale += $subtotale;
        $totale_consuntivo += $tot_ore_consuntivo[$j] + $tot_km_consuntivo[$j];
    }

    $body .= "<tr><td colspan='2' align=\"right\">\n";
    $body .= "<span><b>Totale:</b></span>\n";
    $body .= "</td>\n";

    $body .= "<td align=\"right\" bgcolor=\"#dddddd\">\n";
    $body .= '<span><b>'.Translator::numberToLocale($totale, 2)." &euro;</b></span>\n";
    $body .= "</td></tr>\n";
    $body .= "</tbody>\n";
    $body .= "</table>\n";
}
$body .= "<br/>\n";

if (!empty($idinterventi)) {
    // Conteggio articoli utilizzati
    $query = "SELECT *, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=mg_articoli_interventi.idintervento) AS data_intervento, (SELECT percentuale FROM co_iva WHERE id=mg_articoli_interventi.idiva_vendita) AS prciva_vendita, (SELECT codice FROM mg_articoli WHERE id=idarticolo) AS codice_art, (SELECT prc_guadagno FROM mg_listini WHERE id=(SELECT idlistino_vendite FROM an_anagrafiche WHERE idanagrafica=(SELECT idanagrafica FROM in_interventi WHERE id=mg_articoli_interventi.idintervento) ) ) AS prc_guadagno, CONCAT_WS(serial, 'SN: ', ', ') AS codice, SUM(qta) AS sumqta FROM `mg_articoli_interventi` JOIN mg_prodotti ON mg_articoli_interventi.idarticolo = mg_prodotti.id_articolo GROUP BY idarticolo, idintervento, lotto HAVING idintervento IN(".implode(',', $idinterventi).") AND NOT idarticolo='0' ORDER BY idarticolo ASC";
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
}

if (!empty($idinterventi)) {
    // Conteggio spese aggiuntive
    $query = 'SELECT *, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_righe_interventi.idintervento) AS data_intervento FROM in_righe_interventi WHERE idintervento IN('.implode(',', $idinterventi).') ORDER BY id ASC';
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
}

// Totale complessivo intervento
$body .= "<table style=\"width:100%;\" class=\"table_values\" cellspacing=\"2\" cellpadding=\"5\">\n";

$body .= "<tr><td align=\"right\" width=\"131mm\">\n";
$body .= "<b>TOTALE CONSUNTIVO:</b>\n";
$body .= "</td>\n";
$body .= "<td align=\"left\" bgcolor=\"#cccccc\" width=\"24mm\">\n";
$totale_intervento_consuntivo = Translator::numberToLocale($totale + $totale_articoli + $totale_spese, 2);
$body .= '<b>'.$totale_intervento_consuntivo." &euro;</b>\n";
$body .= "</td></tr>\n";

$body .= "<tr><td align=\"right\" width=\"131mm\">\n";
$body .= "<b>BUDGET TOTALE (NO IVA):</b>\n";
$body .= "</td>\n";

$body .= "<td align=\"left\" bgcolor=\"#cccccc\" width=\"24mm\">\n";
$contratto_tot_budget = Translator::numberToLocale($contratto_tot_budget, 2);
$body .= '<b>'.$contratto_tot_budget." &euro;</b>\n";
$body .= "</td></tr>\n";

$body .= "<tr><td align=\"right\" width=\"131mm\">\n";
$body .= "<b>RAPPORTO BUDGET/SPESA (NO IVA):</b>\n";
$body .= "</td>\n";

$diff = Translator::numberToLocale($contratto_tot_budget - $totale_intervento_consuntivo, 2);
$body .= "<td align=\"left\" bgcolor=\"#cccccc\" width=\"24mm\">\n";
$body .= '<b>'.$diff." &euro;</b>\n";
$body .= "</td></tr>\n";

if (!empty($contratto_tot_ore)) {
    $body .= "<tr><td align=\"right\" width=\"131mm\">\n";
    $body .= "<b>ORE RESIDUE:</b>\n";
    $body .= "</td>\n";

    $body .= "<td align=\"center\" bgcolor=\"#cccccc\" width=\"24mm\">\n";
    $diff2 = Translator::numberToLocale($contratto_tot_ore - $totale_ore_impiegate, 2);
    $body .= "<b>$diff2&nbsp;&nbsp;(ore erogate: ".Translator::numberToLocale($totale_ore_impiegate, 2).'&nbsp;-&nbsp;ore in contratto: '.Translator::numberToLocale($contratto_tot_ore, 2).")</b>\n";
    $body .= "</td></tr>\n";
}

$body .= "</table>\n";

$report_name = 'contratto_'.$idcontratto.'_cons.pdf';
