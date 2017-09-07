<?php

include_once __DIR__.'/../../core.php';

$idddt = save($_GET['idddt']);

$show_costi = get_var('Stampa i prezzi sui ddt');

// Lettura info ddt
$q = "SELECT *, (SELECT descrizione FROM dt_tipiddt WHERE dt_tipiddt.id=idtipoddt) AS tipo_doc, (SELECT descrizione FROM co_pagamenti WHERE id=idpagamento) AS tipo_pagamento, (SELECT descrizione FROM dt_causalet WHERE id=idcausalet) AS causalet, (SELECT descrizione FROM dt_porto WHERE id=idporto) AS porto, (SELECT descrizione FROM dt_aspettobeni WHERE id=idaspettobeni) AS aspettobeni, (SELECT descrizione FROM dt_spedizione WHERE id=idspedizione) AS spedizione, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idvettore) AS vettore FROM dt_ddt WHERE id='$idddt'";
$rs = $dbo->fetchArray($q);
$numero_ddt = $rs[0]['numero'];
$idcliente = $rs[0]['idanagrafica'];
(!empty($rs[0]['numero_esterno'])) ? $numero = $rs[0]['numero_esterno'] : $numero = $rs[0]['numero'];

// Lettura righe ddt
$q2 = "SELECT * FROM dt_righe_ddt WHERE idddt='$idddt'";
$righe = $dbo->fetchArray($q2);

// carica report html
$report = file_get_contents(__DIR__.'/ddt.html');
$body = file_get_contents(__DIR__.'/ddt_body.html');

include_once $docroot.'/templates/pdfgen_variables.php';

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
$destinazione = '';
if ($rs[0]['idsede'] == 0) {
    $queryd = "SELECT ragione_sociale, indirizzo, indirizzo2, cap, citta, provincia, piva, codice_fiscale FROM an_anagrafiche WHERE idanagrafica='".$idcliente."'";
    $rsd = $dbo->fetchArray($queryd);

    if ($rsd[0]['ragione_sociale'] != '') {
        $destinazione .= $rsd[0]['ragione_sociale']."<br/>\n";
    }
    if ($rsd[0]['indirizzo'] != '') {
        $destinazione .= $rsd[0]['indirizzo']."<br/>\n";
    }
    if ($rsd[0]['indirizzo2'] != '') {
        $destinazione .= $rsd[0]['indirizzo2']."<br/>\n";
    }
    if ($rsd[0]['cap'] != '') {
        $destinazione .= $rsd[0]['cap'].' ';
    }
    if ($rsd[0]['citta'] != '') {
        $destinazione .= $rsd[0]['citta'];
    }
    if ($rsd[0]['provincia'] != '') {
        $destinazione .= ' ('.$rsd[0]['provincia'].")<br/>\n";
    }
    if ($rsd[0]['piva'] != '') {
        $destinazione .= 'P.IVA: '.$rsd[0]['piva']."<br/>\n";
    }
    if ($rsd[0]['codice_fiscale'] != '') {
        $destinazione .= 'C.F.: '.$rsd[0]['codice_fiscale']."<br/>\n";
    }
} else {
    $queryd = "SELECT (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS ragione_sociale, indirizzo, indirizzo2, cap, citta, provincia, piva, codice_fiscale FROM an_sedi WHERE idanagrafica='".$idcliente."' AND id='".$rs[0]['idsede']."'";
    $rsd = $dbo->fetchArray($queryd);

    if ($rsd[0]['ragione_sociale'] != '') {
        $destinazione .= $rsd[0]['ragione_sociale']."<br/>\n";
    }
    if ($rsd[0]['indirizzo'] != '') {
        $destinazione .= $rsd[0]['indirizzo']."<br/>\n";
    }
    if ($rsd[0]['indirizzo2'] != '') {
        $destinazione .= $rsd[0]['indirizzo2']."<br/>\n";
    }
    if ($rsd[0]['cap'] != '') {
        $destinazione .= $rsd[0]['cap'].' ';
    }
    if ($rsd[0]['citta'] != '') {
        $destinazione .= $rsd[0]['citta'];
    }
    if ($rsd[0]['provincia'] != '') {
        $destinazione .= ' ('.$rsd[0]['provincia'].")<br/>\n";
    }
    if ($rsd[0]['piva'] != '') {
        $destinazione .= 'P.IVA: '.$rsd[0]['piva']."<br/>\n";
    }
    if ($rsd[0]['codice_fiscale'] != '') {
        $destinazione .= 'C.F.: '.$rsd[0]['codice_fiscale']."<br/>\n";
    }
}
$body = str_replace('$c_destinazione$', $destinazione, $body);

// Dati generici ddt
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

// Mostro le righe del ddt
$totale_ddt = 0.00;
$totale_imponibile = 0.00;
$totale_iva = 0.00;
$sconto = 0.00;

/*
    Articoli
*/
$q_art = "SELECT *, CONCAT_WS(serial, 'SN: ', ', ') AS codice, SUM(qta) AS sumqta FROM `dt_righe_ddt` JOIN mg_prodotti ON dt_righe_ddt.idarticolo = mg_prodotti.id_articolo GROUP BY idarticolo, idddt, lotto HAVING idddt='$idddt' AND NOT idarticolo='0' ORDER BY idarticolo ASC";
$rs_art = $dbo->fetchArray($q_art);
$tot_art = sizeof($rs_art);
$imponibile_art = 0.0;
$iva_art = 0.0;

if ($tot_art > 0) {
    for ($i = 0; $i < $tot_art; ++$i) {
        $body .= "<tr><td class='first_cell'>\n";
        $body .= nl2br($rs_art[$i]['descrizione']);
        if ($rs_art[$i]['codice'] != '') {
            $body .= '<br/><small>'.$rs_art[$i]['codice']."</small>\n";
        }

        // Aggiunta riferimento a ordine
        if (!empty($rs_art[$i]['idordine'])) {
            $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM or_ordini WHERE id="'.$rs_art[$i]['idordine'].'"');
            ($rso[0]['numero_esterno'] != '') ? $numero = $rso[0]['numero_esterno'] : $numero = $rso[0]['numero'];
            $body .= '<br/><small>Rif. ordine n<sup>o</sup>'.$numero.' del '.Translator::dateToLocale($rso[0]['data']).'</small>';
        }

        $body .= "</td>\n";

        $body .= "<td class='table_cell center' valign='top'>\n";
        $body .= Translator::numberToLocale($rs_art[$i]['sumqta'], 2)."\n";
        $body .= "</td>\n";

        $body .= "<td class='table_cell center' valign='top'>\n";
        $body .= $rs_art[$i]['um']."\n";
        $body .= "</td>\n";

        $body .= "<td class='table_cell center' valign='top'>\n";
        if ($show_costi) {
            $body .= Translator::numberToLocale($rs_art[$i]['subtotale'], 2)." &euro;\n";
        } else {
            $body .= '-';
        }
        $body .= "</td>\n";

        // Iva
        $body .= "<td class='table_cell center' valign='top'>\n";
        $iva = $rs_art[$i]['iva'];
        if ($show_costi) {
            $body .= Translator::numberToLocale($iva, 2)." &euro;\n";
        } else {
            $body .= '-';
        }
        $body .= "</td>\n";

        // Imponibile
        $body .= "<td class='table_cell center' valign='top'>\n";
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
    $imponibile_ddt += $imponibile_art;
    $totale_iva += $iva_art;
    $totale_ddt += $imponibile_art;
}

/*
    Righe generiche
*/
$q_gen = "SELECT * FROM `dt_righe_ddt` WHERE idddt='$idddt' AND idarticolo=0";
$rs_gen = $dbo->fetchArray($q_gen);
$tot_gen = sizeof($rs_gen);
$imponibile_gen = 0.0;
$iva_gen = 0.0;

if ($tot_gen > 0) {
    for ($i = 0; $i < $tot_gen; ++$i) {
        $body .= "<tr><td class='first_cell'>\n";
        $body .= nl2br($rs_gen[$i]['descrizione']);

        // Aggiunta riferimento a ordine
        if (!empty($rs_gen[$i]['idordine'])) {
            $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM or_ordini WHERE id="'.$rs_gen[$i]['idordine'].'"');
            ($rso[0]['numero_esterno'] != '') ? $numero = $rso[0]['numero_esterno'] : $numero = $rso[0]['numero'];
            $body .= '<br/><small>Rif. ordine n<sup>o</sup>'.$numero.' del '.Translator::dateToLocale($rso[0]['data']).'</small>';
        }
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
    $imponibile_ddt += $imponibile_gen;
    $totale_iva += $iva_gen;
    $totale_ddt += $imponibile_gen;
}

// Totale imponibile
if ($show_costi) {
    $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
    $body .= '<b>Totale imponibile:</b>';
    $body .= "</td>\n";

    $body .= "<td class='table_cell_h center'>\n";
    $body .= '<b>'.Translator::numberToLocale($imponibile_ddt, 2)." &euro;</b>\n";
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
        $totale_ddt -= $sconto;
        $body .= Translator::numberToLocale($totale_ddt, 2).' &euro;';
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
        $totale_ddt += $rs[0]['rivalsainps'];
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
        $totale_ddt += $totale_iva;
    }

    /*
        Totale ddt
    */
    $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
    $body .= '<b>Totale ddt:</b>';
    $body .= "</td>\n";

    $body .= "<td class='table_cell_h center'>\n";
    $body .= '<b>'.Translator::numberToLocale($totale_ddt, 2)." &euro;</b>\n";
    $body .= "</td></tr>\n";
    $netto_a_pagare = $totale_ddt;

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
    if ($totale_ddt != $netto_a_pagare) {
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

// Note
$body .= '<p>'.nl2br($rs[0]['note'])."</p>\n";

if ($rs[0]['vettore'] != '') {
    $vettore = ' ('.$rs[0]['vettore'].')';
} else {
    $vettore = '';
}

// Dati footer ddt
$footer = "<br/><br/><table style='width:200mm;' class='table_values' style='table-layout:fixed;' border='0' cellspacing='1'>\n";
$footer .= "<col width='169'><col width='169'><col width='169'><col width='169'>\n";
$footer .= "<tr><td width='169' height='25' class='center'><b>Colli:</b><br/>".$rs[0]['n_colli']."&nbsp;</td>\n";
$footer .= "<td width='169' class='center'><b>Aspetto beni:</b><br/>".$rs[0]['aspettobeni']."&nbsp;</td>\n";
$footer .= "<td width='169' class='center'><b>Causale trasporto:</b><br/>".$rs[0]['causalet']."&nbsp;</td>\n";
$footer .= "<td width='169' class='center'><b>Porto:</b><br/>".$rs[0]['porto']."&nbsp;</td></tr>\n";
$footer .= "</table>\n";

$footer .= "<table style='width:200mm;' class='table_values' style='table-layout:fixed;' border='0' cellspacing='1'>\n";
$footer .= "<col width='232'><col width='232'><col width='232'>\n";
$footer .= "<tr><td width='232' height='25' class='center'><b>Tipo di spedizione:</b><br/>".$rs[0]['spedizione'].$vettore."&nbsp;</td>\n";
$footer .= "<td width='232' class='center'><b>Conducente:</b><br/>______________________</td>\n";
$footer .= "<td width='233' class='center'><b>Destinatario:</b><br/>______________________</td></tr>\n";
$footer .= "</table>\n";

$body = str_replace('|footer|', $footer, $body);

$report_name = 'ddt_'.$numero_ddt.'.pdf';
