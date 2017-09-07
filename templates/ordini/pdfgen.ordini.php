<?php

include_once __DIR__.'/../../core.php';

$idordine = save($_GET['idordine']);

$show_costi = get_var('Stampa i prezzi sugli ordini');

// Lettura info ordine
$q = "SELECT *, (SELECT descrizione FROM or_tipiordine WHERE or_tipiordine.id=idtipoordine) AS tipo_doc, (SELECT descrizione FROM co_pagamenti WHERE id=idpagamento) AS tipo_pagamento FROM or_ordini WHERE id='".$idordine."'";
$rs = $dbo->fetchArray($q);
$numero_ord = $rs[0]['numero'];
$idcliente = $rs[0]['idanagrafica'];
(!empty($rs[0]['numero_esterno'])) ? $numero = $rs[0]['numero_esterno'] : $numero = $rs[0]['numero'];

// Lettura righe ordine
$q2 = "SELECT * FROM or_righe_ordini WHERE idordine='".$idordine."'";
$righe = $dbo->fetchArray($q2);

// carica report html
$report = file_get_contents($docroot.'/templates/ordini/ordine.html');
$body = file_get_contents($docroot.'/templates/ordini/ordine_body.html');

include_once $docroot.'/templates/pdfgen_variables.php';

// Dati generici fattura
$body .= "<table style='width:200mm;' class='table_values' border='0' cellspacing='1'>\n";
$body .= "<tr><td width='229' class='center'><b>".$rs[0]['tipo_doc']."</b><br/>n<sup>o</sup> $numero</td>\n";
$body .= "<td width='229' class='center'><b>Data:</b><br/>".Translator::dateToLocale($rs[0]['data'])."</td>\n";
$body .= "<td width='229' class='center'><b>Pagamento:</b><br/>".$rs[0]['tipo_pagamento']."</td></tr>\n";
$body .= "</table><br/><br/>\n";

// Intestazione tabella per righe
$body .= "<table class='table_values' border='0' cellspacing='1' style='table-layout:fixed;'>\n";
$body .= "<col width='300'><col width='50'><col width='40'><col width='90'><col width='75'><col width='60'><col width='78'>\n";
$body .= "<thead>\n";
$body .= "<tr><th width='300'>Descrizione</th>\n";
$body .= "<th width='50' align='center'>Q.tà</th>\n";
$body .= "<th width='40' align='center'>u.m.</th>\n";
$body .= "<th width='90' align='center'>Costo&nbsp;unitario</th>\n";
$body .= "<th width='60' align='center'>Iva</th>\n";
$body .= "<th width='75' align='center'>Imponibile</th></tr>\n";
$body .= "</thead>\n";

$body .= "<tbody>\n";

// Mostro le righe del ordine
$totale_ordine = 0.00;
$totale_imponibile = 0.00;
$totale_iva = 0.00;
$sconto = 0.00;

/*
    Articoli
*/
$q_art = "SELECT *, CONCAT_WS(serial, 'SN: ', ', ') AS codice, SUM(qta) AS sumqta FROM `or_righe_ordini` JOIN mg_prodotti ON or_righe_ordini.idarticolo = mg_prodotti.id_articolo GROUP BY idarticolo, idordine, lotto HAVING idordine='$idordine' AND NOT idarticolo='0' ORDER BY idarticolo ASC";
$rs_art = $dbo->fetchArray($q_art);
$tot_art = sizeof($rs_art);
$imponibile_art = 0.0;
$iva_art = 0.0;

if ($tot_art > 0) {
    $prec_art = '';
    $riga_art = '';

    for ($i = 0; $i < $tot_art; ++$i) {
        if ($rs_art[$i]['idarticolo'] != $prec_art) {
            $q_art = 0;
        }
        $body .= "<tr><td class='first_cell'>\n";
        $body .= nl2br($rs_art[$i]['descrizione']);
        if ($rs_art[$i]['codice'] != '') {
            $body .= '<br/><small>'.$rs_art[$i]['codice']."</small>\n";
        }
        $body .= "</td>\n";

        $body .= "<td class='table_cell center'>\n";
        $body .= Translator::numberToLocale($rs_art[$i]['sumqta'], 2)."\n";
        $body .= "</td>\n";

        $body .= "<td class='table_cell center'>\n";
        $body .= $rs_art[$i]['um']."\n";
        $body .= "</td>\n";

        $body .= "<td class='table_cell center'>\n";
        if ($show_costi) {
            $body .= Translator::numberToLocale($rs_art[$i]['subtotale'] / $rs_art[$i]['sumqta'], 2)." &euro;\n";
        } else {
            $body .= '-';
        }
        $body .= "</td>\n";

        // Iva
        $body .= "<td class='table_cell center'>\n";
        $iva = $rs_art[$i]['iva'];
        if ($show_costi) {
            $body .= Translator::numberToLocale($iva, 2)." &euro;\n";
        } else {
            $body .= '-';
        }
        $body .= "</td>\n";

        // Imponibile
        $body .= "<td class='table_cell center'>\n";
        if ($show_costi) {
            $body .= Translator::numberToLocale($rs_art[$i]['subtotale'], 2)." &euro;\n";

            if ($rs_art[$i]['sconto'] > 0) {
                $body .= "<br/>\n<small style='color:#555;'>- sconto ".Translator::numberToLocale($rs_art[$i]['sconto'], 2)." &euro;</small>\n";
            }
        } else {
            $body .= '-';
        }
        $body .= "</td></tr>\n";

        $imponibile_art += $rs_art[$i]['subtotale'];
        $iva_art += $iva;
        $sconto += $rs_art[$i]['sconto'];
    }
    $imponibile_ordine += $imponibile_art;
    $totale_iva += $iva_art;
    $totale_ordine += $imponibile_art;
}

/*
    Righe generiche
*/
$q_gen = "SELECT * FROM `or_righe_ordini` WHERE idordine='$idordine' AND idarticolo=0";
$rs_gen = $dbo->fetchArray($q_gen);
$tot_gen = sizeof($rs_gen);
$imponibile_gen = 0.0;
$iva_gen = 0.0;

if ($tot_gen > 0) {
    for ($i = 0; $i < $tot_gen; ++$i) {
        $body .= "<tr><td class='first_cell'>\n";
        $body .= nl2br($rs_gen[$i]['descrizione']);
        $body .= "</td>\n";

        $body .= "<td class='table_cell center'>\n";
        $body .= Translator::numberToLocale($rs_gen[$i]['qta'], 2)."\n";
        $body .= "</td>\n";

        $body .= "<td class='table_cell center'>\n";
        $body .= $rs_gen[$i]['um']."\n";
        $body .= "</td>\n";

        $body .= "<td class='table_cell center'>\n";
        if ($show_costi) {
            $body .= Translator::numberToLocale($rs_gen[$i]['subtotale'] / $rs_gen[$i]['qta'], 2)." &euro;\n";
        } else {
            $body .= '-';
        }
        $body .= "</td>\n";

        // Iva
        $body .= "<td class='table_cell center'>\n";
        $iva = $rs_gen[$i]['iva'];
        if ($show_costi) {
            $body .= Translator::numberToLocale($iva, 2)." &euro;\n";
        } else {
            $body .= '-';
        }
        $body .= "</td>\n";

        // Imponibile
        $body .= "<td class='table_cell center'>\n";
        if ($show_costi) {
            $body .= Translator::numberToLocale($rs_gen[$i]['subtotale'], 2)." &euro;\n";

            if ($rs_gen[$i]['sconto'] > 0) {
                $body .= "<br/>\n<small style='color:#555;'>- sconto ".Translator::numberToLocale($rs_gen[$i]['sconto'], 2)." &euro;</small>\n";
            }
        } else {
            $body .= '-';
        }
        $body .= "</td></tr>\n";

        $imponibile_gen += $rs_gen[$i]['subtotale'];
        $iva_gen += $iva;
        $sconto += $rs_gen[$i]['sconto'];
    }
    $imponibile_ordine += $imponibile_gen;
    $totale_iva += $iva_gen;
    $totale_ordine += $imponibile_gen;
}

// Totale imponibile
if ($show_costi) {
    $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
    $body .= '<b>Totale imponibile:</b>';
    $body .= "</td>\n";

    $body .= "<td class='table_cell center'>\n";
    $body .= Translator::numberToLocale($imponibile_ordine, 2).' &euro;';
    $body .= "</td></tr>\n";

    // Mostra sconto se c'è
    if (abs($sconto) > 0) {
        $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
        $body .= '<b>Sconto:</b>';
        $body .= "</td>\n";

        // Sconto
        $body .= "<td class='table_cell center'>\n";
        $body .= Translator::numberToLocale($sconto, 2).' &euro;';
        $body .= '</td>';
        $body .= "</tr>\n";

        // Totale scontato
        $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
        $body .= '<b>Totale scontato:</b>';
        $body .= "</td>\n";

        // Sconto
        $body .= "<td class='table_cell center'>\n";
        $totale_ordine = $imponibile_ordine - $sconto;
        $body .= Translator::numberToLocale($totale_ordine, 2).' &euro;';
        $body .= "</td></tr>\n";
    }

    // Mostra INPS se c'è
    if (abs($rs[0]['rivalsainps']) > 0) {
        $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
        $body .= '<b>Rivalsa INPS:</b>';
        $body .= "</td>\n";

        // Rivalsa INPS
        $body .= "<td class='table_cell center'>\n";
        $body .= Translator::numberToLocale($rs[0]['rivalsainps'], 2).' &euro;';
        $body .= "</td></tr>\n";
        $totale_ordine += $rs[0]['rivalsainps'];
    }

    // Mostra iva se c'è
    $totale_iva += $rs[0]['iva_rivalsainps'];
    if (abs($totale_iva) > 0) {
        $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
        $body .= '<b>Iva:</b>';
        $body .= "</td>\n";

        // Iva
        $body .= "<td class='table_cell center'>\n";
        $body .= Translator::numberToLocale($totale_iva, 2)." &euro;\n";
        $body .= "</td></tr>\n";
        $totale_ordine += $totale_iva;
    }

    /*
        Totale ordine
    */
    $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
    $body .= '<b>Totale ordine:</b>';
    $body .= "</td>\n";

    $body .= "<td class='table_cell_h center'>\n";
    $body .= '<b>'.Translator::numberToLocale($totale_ordine, 2)." &euro;</b>\n";
    $body .= "</td></tr>\n";
    $netto_a_pagare = $totale_ordine;

    // Mostra marca da bollo se c'è
    if (abs($rs[0]['bollo']) > 0) {
        $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
        $body .= '<b>Marca da bollo:</b>';
        $body .= "</td>\n";

        // Marca da bollo
        $body .= "<td class='table_cell center'>\n";
        $marca_da_bollo = str_replace(',', '.', $rs[0]['bollo']);
        $body .= Translator::numberToLocale($marca_da_bollo, 2).' &euro;';
        $body .= "</td></tr>\n";
        $netto_a_pagare += $marca_da_bollo;
    }

    // Mostra ritenuta d'acconto se c'è
    if (abs($rs[0]['ritenutaacconto']) > 0) {
        $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
        $body .= "<b>Ritenuta d'acconto:</b>";
        $body .= "</td>\n";

        // Ritenuta d'acconto
        $body .= "<td class='table_cell center'>\n";
        $body .= Translator::numberToLocale($rs[0]['ritenutaacconto'], 2).' &euro;';
        $body .= "</td></tr>\n";
        $netto_a_pagare -= $rs[0]['ritenutaacconto'];
    }

    /*
        Netto a pagare (se diverso dal totale)
    */
    if ($totale_ordine != $netto_a_pagare) {
        $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
        $body .= '<b>Netto a pagare:</b>';
        $body .= "</td>\n";

        $body .= "<td class='table_cell_h center'>\n";
        $body .= '<b>'.Translator::numberToLocale($netto_a_pagare, 2)." &euro;</b>\n";
        $body .= "</td></tr>\n";
    }
}

$body .= "</tbody>\n";
$body .= "</table>\n";

$body .= '<p>'.nl2br($rs[0]['note'])."</p>\n";

$report_name = 'Ordine_'.$numero_ord.'.pdf';
