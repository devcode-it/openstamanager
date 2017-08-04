<?php

include_once __DIR__.'/../../core.php';

$iddocumento = save($_GET['iddocumento']);

// Lettura tipo documento
$q = 'SELECT (SELECT dir FROM co_tipidocumento WHERE id=idtipodocumento) AS dir FROM co_documenti WHERE id="'.$iddocumento.'"';
$rs = $dbo->fetchArray($q);

if ($rs[0]['dir'] == 'entrata') {
    $module_name = 'Fatture di vendita';
} else {
    $module_name = 'Fatture di acquisto';
}

$additional_where[$module_name] = str_replace('|idanagrafica|', "'".$user_idanagrafica."'", $additional_where[$module_name]);

// Lettura info fattura
$q = 'SELECT *, (SELECT descrizione FROM co_tipidocumento WHERE id=idtipodocumento) AS tipo_doc, (SELECT descrizione FROM co_pagamenti WHERE id=idpagamento) AS tipo_pagamento, (SELECT dir FROM co_tipidocumento WHERE id=idtipodocumento) AS dir FROM co_documenti WHERE id="'.$iddocumento.'" '.$additional_where[$module_name];
$rs = $dbo->fetchArray($q);
$numero_doc = $rs[0]['numero'];
$idcliente = $rs[0]['idanagrafica'];
(!empty($rs[0]['numero_esterno'])) ? $numero = $rs[0]['numero_esterno'] : $numero = $rs[0]['numero'];

// carica report html
$report = file_get_contents($docroot.'/templates/fatture_accompagnatorie/fattura.html');
$body = file_get_contents($docroot.'/templates/fatture_accompagnatorie/fattura_body.html');

if (!($idcliente == $user_idanagrafica || Auth::isAdmin())) {
    die('Non hai i permessi per questa stampa!');
}

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
    if ($rsd[0]['piva'] == '') {
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

// Dati generici fattura
if ($rs[0]['buono_ordine'] != '') {
    $width = '165';
} else {
    $width = '228';
}

$body .= "<table class='table_values' border='0' cellspacing='1'>\n";
$body .= "<tr>\n";
$body .= "<td width='".$width."' class='center'><b>".$rs[0]['tipo_doc']."</b><br/>n<sup>o</sup> $numero</td>\n";
$body .= "<td width='".$width."' class='center'><b>Data:</b><br/>".Translator::dateToLocale($rs[0]['data'])."</td>\n";
$body .= "<td width='".$width."' class='center'><b>Pagamento:</b><br/>".$rs[0]['tipo_pagamento']."</td>\n";

if ($rs[0]['buono_ordine']) {
    $body .= "<td width='".$width."' class='center'><b>Buono d'ordine:</b><br/>".$rs[0]['buono_ordine']."</td>\n";
}

$body .= "</tr>\n";
$body .= "</table><br/><br/>\n";

// Intestazione tabella per righe
$body .= "<table class='table_values' border='0' cellspacing='1' style='table-layout:fixed;'>\n";
$body .= "<col width='280'><col width='50'><col width='40'><col width='90'><col width='60'><col width='98'>\n";
$body .= "<thead>\n";
$body .= "<tr><th width='280'>Descrizione</th>\n";
$body .= "<th width='50' align='center'>Q.tà</th>\n";
$body .= "<th width='40' align='center'>u.m.</th>\n";
$body .= "<th width='90' align='center'>Costo&nbsp;unitario</th>\n";
$body .= "<th width='60' align='center'>Iva</th>\n";
$body .= "<th width='98' align='center'>Imponibile</th></tr>\n";
$body .= "</thead>\n";

$body .= "<tbody>\n";

// Mostro le righe del documento
$totale_documento = 0.00;
$totale_imponibile = 0.00;
$totale_iva = 0.00;
$sconto = 0.00;

/*
    Righe fattura
*/
$qr = "SELECT * FROM `co_righe_documenti` WHERE iddocumento='$iddocumento'";
$rsr = $dbo->fetchArray($qr);
$tot = sizeof($rsr);
$imponibile_int = 0.00;
$iva_int = 0.00;

if ($tot > 0) {
    for ($i = 0; $i < $tot; ++$i) {
        // Intervento
        if (!empty($rsr[$i]['idintervento']) && empty($rsr[$i]['idarticolo'])) {
            $body .= "<tr><td class='first_cell'>\n";
            $body .= nl2br($rsr[$i]['descrizione'])."\n";
            $body .= "</td>\n";

            $qta = $rsr[$i]['qta'];
            ($qta == 0) ? $qta = '-' : $qta = Translator::numberToLocale($qta, 2);
            $body .= "<td class='table_cell center'>\n";
            $body .= $qta;
            $body .= "</td>\n";

            ($qta == 0) ? $um = '-' : $um = $rsr[$i]['um'];
            $body .= "<td class='table_cell center'>\n";
            $body .= $um;
            $body .= "</td>\n";

            // costo unitario
            $subtotale = $rsr[$i]['subtotale'] / $rsr[$i]['qta'];
            ($subtotale == 0) ? $subtotale = '-' : $subtotale = Translator::numberToLocale($subtotale, 2).' &euro;';
            $body .= "<td class='table_cell center'>\n";
            $body .= $subtotale."\n";
            $body .= "</td>\n";

            $body .= "<td class='table_cell center'>\n";
            $iva = $rsr[$i]['iva'];
            $body .= '<br/>'.Translator::numberToLocale($iva, 2)." &euro;<br/><small style='color:#777;'>".$rsr[$i]['desc_iva']."</small>\n";
            $body .= "</td>\n";

            $body .= "<td class='table_cell' align='right'>\n";
            $subtot = $rsr[$i]['subtotale'];
            $body .= Translator::numberToLocale($subtot, 2)." &euro;\n";
            if ($rsr[$i]['sconto'] > 0) {
                $body .= "<br/>\n<small style='color:#555;'>- sconto ".Translator::numberToLocale($rsr[$i]['sconto'], 2)." &euro;</small>\n";
            }
            $body .= "</td></tr>\n";

            $imponibile_int += $rsr[$i]['subtotale'];
            $iva_int += $iva;
            $sconto += $rsr[$i]['sconto'];
        }

        // Preventivi
        elseif ($rsr[$i]['idpreventivo'] != 0) {
            $body .= "<tr><td class='first_cell'>\n";
            $body .= nl2br($rsr[$i]['descrizione'])."\n";
            $body .= "</td>\n";

            $body .= "<td class='table_cell center'>\n";
            $body .= "1\n";
            $body .= "</td>\n";

            $body .= "<td class='table_cell center'>\n";
            $body .= '-';
            $body .= "</td>\n";

            $body .= "<td class='table_cell center'>\n";
            $body .= "-\n";
            $body .= "</td>\n";

            // Iva
            $body .= "<td class='table_cell center'>\n";
            $iva = $rsr[$i]['iva'];
            $body .= '<br/>'.Translator::numberToLocale($iva, 2)." &euro;<br/><small style='color:#777;'>".$rsr[$i]['desc_iva']."</small>\n";
            $body .= "</td>\n";

            // Imponibile
            $body .= "<td class='table_cell' align='right'>\n";
            $subtot = $rsr[$i]['subtotale'];
            $body .= Translator::numberToLocale($subtot, 2)." &euro;\n";
            if ($rsr[$i]['sconto'] > 0) {
                $body .= "<br/>\n<small style='color:#555;'>- sconto ".Translator::numberToLocale($rsr[$i]['sconto'], 2)." &euro;</small>\n";
            }
            $body .= "</td></tr>\n";

            $imponibile_pre += $rsr[$i]['subtotale'];
            $iva_pre += $iva;
            $sconto += $rsr[$i]['sconto'];
        }

        // Contratti
        elseif ($rsr[$i]['idcontratto'] != 0) {
            $body .= "<tr><td class='first_cell'>\n";
            $body .= nl2br($rsr[$i]['descrizione'])."\n";
            $body .= "</td>\n";

            $body .= "<td class='table_cell center'>\n";
            $body .= "1\n";
            $body .= "</td>\n";

            $body .= "<td class='table_cell center'>\n";
            $body .= '-';
            $body .= "</td>\n";

            $body .= "<td class='table_cell center'>\n";
            $body .= "-\n";
            $body .= "</td>\n";

            // Iva
            $body .= "<td class='table_cell center'>\n";
            $iva = $rsr[$i]['iva'];
            $body .= '<br/>'.Translator::numberToLocale($iva, 2)." &euro;<br/><small style='color:#777;'>".$rsr[$i]['desc_iva']."</small>\n";
            $body .= "</td>\n";

            // Imponibile
            $body .= "<td class='table_cell' align='right'>\n";
            $subtot = $rsr[$i]['subtotale'];
            $body .= Translator::numberToLocale($subtot, 2)." &euro;\n";
            if ($rsr[$i]['sconto'] > 0) {
                $body .= "<br/>\n<small style='color:#555;'>- sconto ".Translator::numberToLocale($rsr[$i]['sconto'], 2)." &euro;</small>\n";
            }
            $body .= "</td></tr>\n";

            $imponibile_con += $rsr[$i]['subtotale'];
            $iva_con += $iva;
            $sconto += $rsr[$i]['sconto'];
        }

        // Articoli
        elseif ($rsr[$i]['idarticolo'] != 0) {
            $body .= "<tr><td class='first_cell'>\n";

            // Immagine articolo
            $f = pathinfo($rsr[$i]['immagine01']);
            $img = $docroot.'/modules/magazzino/articoli/images/'.$f['filename'].'_thumb100.'.$f['extension'];
            if (file_exists($img)) {
                $body .= '<img src="'.$img."\" alt=\"\" border=\"0\" align=\"left\" style=\"margin:0px 4px 4px 0px; border:1px solid #ccc;\" />\n";
            }

            $body .= nl2br($rsr[$i]['descrizione']);

            // Aggiunta riferimento a ordine
            if (!empty($rsr[$i]['idordine'])) {
                $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM or_ordini WHERE id="'.$rsr[$i]['idordine'].'"');
                ($rso[0]['numero_esterno'] != '') ? $numero = $rso[0]['numero_esterno'] : $numero = $rso[0]['numero'];
                $body .= '<br/><small>Rif. ordine '.$numero.' del '.Translator::dateToLocale($rso[0]['data']).'</small>';
            }

            // Aggiunta riferimento a ddt
            elseif (!empty($rsr[$i]['idddt'])) {
                $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM dt_ddt WHERE id="'.$rsr[$i]['idddt'].'"');
                ($rso[0]['numero_esterno'] != '') ? $numero = $rso[0]['numero_esterno'] : $numero = $rso[0]['numero'];
                $body .= '<br/><small>Rif. ddt '.$numero.' del '.Translator::dateToLocale($rso[0]['data']).'</small>';
            }
            $body .= "</td>\n";

            $body .= "<td class='table_cell center'>\n";
            $body .= Translator::numberToLocale($rsr[$i]['qta'], 2);
            $body .= "</td>\n";

            $body .= "<td class='table_cell center'>\n";
            $body .= $rsr[$i]['um'];
            $body .= "</td>\n";

            // costo unitario
            $body .= "<td class='table_cell center'>\n";
            $body .= Translator::numberToLocale($rsr[$i]['subtotale'] / $rsr[$i]['sumqta'], 2)." &euro;\n";
            $body .= "</td>\n";

            $body .= "<td class='table_cell center'>\n";
            $iva = $rsr[$i]['iva'];
            $body .= '<br/>'.Translator::numberToLocale($iva, 2)." &euro;<br/><small style='color:#777;'>".$rsr[$i]['desc_iva']."</small>\n";
            $body .= "</td>\n";

            $body .= "<td class='table_cell' align='right'>\n";
            $subtot = $rsr[$i]['subtotale'];
            $body .= Translator::numberToLocale($subtot, 2)." &euro;\n";
            if ($rsr[$i]['sconto'] > 0) {
                $body .= "<br/>\n<small style='color:#555;'>- sconto ".Translator::numberToLocale($rsr[$i]['sconto'], 2)." &euro;</small>\n";
            }
            $body .= "</td></tr>\n";

            $imponibile_art += $rsr[$i]['subtotale'];
            $iva_art += $iva;
            $sconto += $rsr[$i]['sconto'];
        }

        // Righe generiche
        else {
            $body .= "<tr><td class='first_cell'>\n";
            $body .= nl2br($rsr[$i]['descrizione']);

            // Aggiunta riferimento a ordine
            if (!empty($rsr[$i]['idordine'])) {
                $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM or_ordini WHERE id="'.$rsr[$i]['idordine'].'"');
                ($rso[0]['numero_esterno'] != '') ? $numero = $rso[0]['numero_esterno'] : $numero = $rso[0]['numero'];
                $body .= '<br/><small>Rif. ordine n<sup>o</sup>'.$numero.' del '.Translator::dateToLocale($rso[0]['data']).'</small>';
            }

            // Aggiunta riferimento a ddt
            elseif (!empty($rsr[$i]['idddt'])) {
                $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM dt_ddt WHERE id="'.$rsr[$i]['idddt'].'"');
                ($rso[0]['numero_esterno'] != '') ? $numero = $rso[0]['numero_esterno'] : $numero = $rso[0]['numero'];
                $body .= '<br/><small>Rif. ddt n<sup>o</sup>'.$numero.' del '.Translator::dateToLocale($rso[0]['data']).'</small>';
            }
            $body .= "</td>\n";

            $body .= "<td class='table_cell center'>\n";
            $body .= Translator::numberToLocale($rsr[$i]['qta'], 2)."\n";
            $body .= "</td>\n";

            $body .= "<td class='table_cell center'>\n";
            $body .= $rsr[$i]['um']."\n";
            $body .= "</td>\n";

            $body .= "<td class='table_cell center'>\n";
            $body .= Translator::numberToLocale($rsr[$i]['subtotale'] / $rsr[$i]['qta'], 2)." &euro;\n";
            $body .= "</td>\n";

            // Iva
            $body .= "<td class='table_cell center'>\n";
            $iva = $rsr[$i]['iva'];
            $body .= '<br/>'.Translator::numberToLocale($iva, 2)." &euro;<br/><small style='color:#777;'>".$rsr[$i]['desc_iva']."</small>\n";
            $body .= "</td>\n";

            // Imponibile
            $body .= "<td class='table_cell' align='right'>\n";
            $subtot = $rsr[$i]['subtotale'];
            $body .= Translator::numberToLocale($subtot, 2)." &euro;\n";
            if ($rsr[$i]['sconto'] > 0) {
                $body .= "<br/>\n<small style='color:#555;'>- sconto ".Translator::numberToLocale($rsr[$i]['sconto'], 2)." &euro;</small>\n";
            }
            $body .= "</td></tr>\n";

            $imponibile_gen += $rsr[$i]['subtotale'];
            $iva_gen += $iva;
            $sconto += $rsr[$i]['sconto'];
        }
    }

    $imponibile_documento += $imponibile_int;
    $totale_iva += $iva_int;
    $totale_documento += $imponibile_int;

    $imponibile_documento += $imponibile_pre;
    $totale_iva += $iva_pre;
    $totale_documento += $imponibile_pre;

    $imponibile_documento += $imponibile_con;
    $totale_iva += $iva_con;
    $totale_documento += $imponibile_con;

    $imponibile_documento += $imponibile_art;
    $totale_iva += $iva_art;
    $totale_documento += $imponibile_art;

    $imponibile_documento += $imponibile_gen;
    $totale_iva += $iva_gen;
    $totale_documento += $imponibile_gen;
}

// Totale documento
$body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
$body .= '<b>Subtot.:</b>';
$body .= "</td>\n";

// Imponibile
$body .= "<td class='table_cell' align='right'>\n";
$totale_documento = $imponibile_documento;
$body .= Translator::numberToLocale($totale_documento, 2)." &euro;\n";
$body .= "</td></tr>\n";

// Mostra sconto se c'è
if (abs($sconto) > 0) {
    $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
    $body .= '<b>Sconto:</b>';
    $body .= "</td>\n";

    // Sconto
    $body .= "<td class='table_cell' align='right'>\n";
    $body .= Translator::numberToLocale($sconto, 2)." &euro;\n";
    $body .= '</td></tr>';

    // Totale scontato
    $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
    $body .= '<b>Totale scontato:</b>';
    $body .= "</td>\n";

    // Sconto
    $body .= "<td class='table_cell' align='right'>\n";
    $totale_documento -= $sconto;
    $body .= Translator::numberToLocale($totale_documento, 2)." &euro;\n";
    $body .= "</td></tr>\n";
}

// Mostra INPS se c'è
if (abs($rs[0]['rivalsainps']) > 0) {
    $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
    $body .= '<b>Rivalsa INPS:</b>';
    $body .= "</td>\n";

    // Rivalsa INPS
    $body .= "<td class='table_cell' align='right'>\n";
    $body .= Translator::numberToLocale($rs[0]['rivalsainps'], 2)." &euro;\n";
    $body .= "</td></tr>\n";
    $totale_documento += $rs[0]['rivalsainps'];
}

// Mostra iva se c'è
$totale_iva += $rs[0]['iva_rivalsainps'];
if (abs($totale_iva) > 0) {
    $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
    $body .= '<b>Iva:</b>';
    $body .= "</td>\n";

    // Iva
    $body .= "<td class='table_cell' align='right'>\n";
    $body .= Translator::numberToLocale($totale_iva, 2)." &euro;\n";
    $body .= "</td></tr>\n";
    $totale_documento += $totale_iva;
}

/*
    Totale documento
*/
$body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
$body .= '<b>Totale documento:</b>';
$body .= "</td>\n";

$body .= "<td class='table_cell_h' align='right'>\n";
$body .= '<b>'.Translator::numberToLocale($totale_documento, 2)." &euro;</b>\n";
$body .= "</td></tr>\n";
$netto_a_pagare = $totale_documento;

// Mostra marca da bollo se c'è
if (abs($rs[0]['bollo']) > 0) {
    $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
    $body .= '<b>Marca da bollo:</b>';
    $body .= "</td>\n";

    // Marca da bollo
    $body .= "<td class='table_cell' align='right'>\n";
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
    $body .= "<td class='table_cell' align='right'>\n";
    $body .= Translator::numberToLocale($rs[0]['ritenutaacconto'], 2).' &euro;';
    $body .= "</td></tr>\n";
    $netto_a_pagare -= $rs[0]['ritenutaacconto'];
}

/*
    Netto a pagare (se diverso dal totale)
*/
if ($totale_documento != $netto_a_pagare) {
    $body .= "<tr><td class='first_cell' colspan='5' align='right'>\n";
    $body .= '<b>Netto a pagare:</b>';
    $body .= "</td>\n";

    $body .= "<td class='table_cell_h' align='right'>\n";
    $body .= '<b>'.Translator::numberToLocale($netto_a_pagare, 2)." &euro;</b>\n";
    $body .= "</td></tr>\n";
}
$body .= "</tbody>\n";
$body .= "</table>\n";

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

$report_name = 'fattura_'.$numero_doc.'.pdf';
