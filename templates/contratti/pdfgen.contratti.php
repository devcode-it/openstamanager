<?php

include_once __DIR__.'/../../core.php';

// carica intervento
$idcontratto = save($_GET['idcontratto']);

$show_costi = get_var('Stampa i prezzi sui contratti');

// Lettura dati contratto e interventi
$q = "SELECT *, (SELECT orario_inizio FROM in_interventi_tecnici WHERE idintervento=in_interventi.id LIMIT 0,1) AS data, co_contratti.descrizione AS `cdescrizione`, co_contratti.idanagrafica AS `idanagrafica`, co_contratti.costo_orario AS costo_orario , co_contratti.costo_km AS costo_km FROM co_contratti LEFT OUTER JOIN (co_righe_contratti LEFT OUTER JOIN in_interventi ON co_righe_contratti.idintervento=in_interventi.id) ON co_contratti.id=co_righe_contratti.idcontratto WHERE co_contratti.id='".$idcontratto."' ORDER BY data DESC";
$rscontrattii = $dbo->fetchArray($q);
$idcliente = $rscontrattii[0]['idanagrafica'];

// carica report html
$report = file_get_contents($docroot.'/templates/contratti/contratto.html');
$body = file_get_contents($docroot.'/templates/contratti/contratto_body.html');

include_once $docroot.'/templates/pdfgen_variables.php';

$totrows = sizeof($rscontrattii);
$totale_km = 0;
$totale_ore = 0;
$totale = 0;
$contratti = [];
$ore = [];
$km = [];
$ntecnici = [];
$tecnici = [];
$costi_orari = [];
$costi_km = [];
$idinterventi = ['-1'];

// Sostituisco i valori tra | | con il valore del campo del db
$body .= preg_replace('/|(.+?)|/', $rscontrattii[0]['${1}'], $body);

// Lettura nome referenti collegati all'anagrafica
$query = 'SELECT * FROM an_referenti WHERE id = "'.$rscontrattii[0]['idreferente'].'"';
$rs = $dbo->fetchArray($query);
$nome_referente = $rs[0]['nome'];

// Tabella intestazione
$body .= "<table  class='table_values' border='0' cellpadding='0'>\n";

$body .= "<tr><td align=\"left\" width='356' style='border:0px;'>\n";
$body .= " \n";
$body .= "</td>\n";
$body .= "<td align=\"left\" width='356' >\n";

if ($c_cap != '') {
    $c_cap = $c_cap.' ';
}

if ($c_provincia != '') {
    $c_provincia = ' ('.$c_provincia.')';
}

$body .= '<big style="line-height:5mm" >Spettabile<br/><b>'.$c_ragionesociale.'<br>'.$c_indirizzo.'<br>'.$c_cap.$c_citta.$c_provincia.'<br> P.Iva: '.$c_piva."</b></big>\n";
$body .= "</td>\n";
$body .= "</tr>\n";

$body .= "<tr><td align=\"left\" colspan=\"2\" style='border:0px;'>\n";
$body .= '<big><b>'.$f_citta.', '.Translator::dateToLocale($rscontrattii[0]['data_bozza'])."</b></big>\n";
$body .= "</td>\n";
$body .= "</tr>\n";

$body .= "<tr><td align=\"left\" colspan=\"2\" style='border:0px;'>\n";
$body .= '<big><b>OGGETTO: CONTRATTO N<sup>o</sup> '.$rscontrattii[0]['numero'].' DEL '.Translator::dateToLocale($rscontrattii[0]['data_bozza'])."</b></big>\n";
$body .= "</td>\n";
$body .= "</tr>\n";

if ($nome_referente != '') {
    $body .= "<tr><td align=\"left\" colspan=\"2\" >\n";
    $body .= '<b><u>C.A.</u></b> '.$nome_referente."\n";
    $body .= "</td>\n";
    $body .= "</tr>\n";
}

$body .= "</table>\n";

/*
    TABELLA COSTI
*/
$body .= "<table  class='table_values' border='0' cellpadding='0'>\n";
$body .= "<col width='390'><col width='50'><col width='40'><col width='90'><col width='75'><col width='60'><col width='78'>\n";
$body .= "<thead>\n";
$body .= "<tr><th width='390'>Descrizione</th>\n";
$body .= "<th width='50' align='center'>Q.tà</th>\n";
$body .= "<th width='40' align='center'>u.m.</th>\n";
$body .= "<th width='90' align='center'>Costo&nbsp;unitario</th>\n";

$body .= "<th width='75' align='center'>Imponibile</th></tr>\n";
$body .= "</thead>\n";

$body .= "<tbody>\n";

$rs = $dbo->fetchArray('SELECT * FROM co_righe2_contratti WHERE idcontratto="'.$idcontratto.'"');
$totale = 0;

for ($i = 0; $i < sizeof($rs); ++$i) {
    // Descrizione
    $body .= "<tr><td valign='top'>".nl2br($rs[$i]['descrizione'])."</td>\n";

    // Q.tà
    $body .= "<td align='center' valign='top'>".Translator::numberToLocale($rs[$i]['qta'], 2)."</td>\n";

    // um
    $body .= "<td align='center' valign='top'>".$rs[$i]['um']."</td>\n";

    // Costo unitario
    $body .= "<td align='center' valign='top'>".Translator::numberToLocale($rs[$i]['subtotale'] / $rs[$i]['qta'], 2)."  &euro;</td>\n";

    // Subtotale
    $body .= "<td align='center' valign='top'>".Translator::numberToLocale($rs[$i]['subtotale'], 2)."  &euro;</td>\n";
    $body .= "</tr>\n";

    $totale += $rs[$i]['subtotale'];
}

// Totale complessivo intervento
$body .= "<tr><td align=\"right\" colspan=\"4\">\n";
$body .= "<big><b>QUOTAZIONE TOTALE:</b></big>\n";
$body .= "</td>\n";

$body .= "<td align=\"right\" bgcolor=\"#cccccc\">\n";
$body .= '<big><b>'.Translator::numberToLocale($totale, 2)." &euro;</b></big>\n";
$body .= "</td></tr>\n";
$body .= "</tbody>\n";
$body .= "</table><br/><br/>\n";

// CONDIZIONI GENERALI DI FORNITURA

// Lettura pagamenti
if ($rscontrattii[0]['idpagamento'] != '') {
    $query = 'SELECT * FROM co_pagamenti WHERE id = '.$rscontrattii[0]['idpagamento'];
    $rs = $dbo->fetchArray($query);
    $pagamento = $rs[0]['descrizione'];
} else {
    $pagamento = 'N.D.';
}

$rscontrattii[0]['idpagamento'];

$body .= "<table border=\"0\" cellpadding='0' cellspacing='10'>\n";
$body .= "<col width='200'><col width='510'>\n";
$body .= "<tr><td align=\"center\" valign=\"middle\" style=\"height:5mm;font-size:14pt;\" bgcolor=\"#dddddd\" colspan=\"2\">\n";
$body .= "<span><b>CONDIZIONI GENERALI DI FORNITURA</b></span><br/>\n";
$body .= "</td></tr>\n";

// PAGAMENTI
$body .= "<tr><td>\n";
$body .= "<big><b><u>PAGAMENTI:</u></b></big>\n";
$body .= "</td>\n";

$body .= "<td>\n";
$body .= '<span><b>'.$pagamento."</b></span>\n";
$body .= "</td></tr>\n";

// VALIDITA' OFFERTA
$body .= "<tr><td>\n";
$body .= "<big><b><u>VALIDIT&Agrave; OFFERTA:</u></b></big>\n";
$body .= "</td>\n";

$body .= "<td>\n";
$body .= '<span><b>'.$rscontrattii[0]['validita']." giorni</b></span>\n";
$body .= "</td></tr>\n";

// VALIDITA' CONTRATTO
$body .= "<tr><td>\n";
$body .= "<big><b><u>VALIDIT&Agrave; CONTRATTO:</u></b></big>\n";
$body .= "</td>\n";

$body .= "<td>\n";
$body .= '<span><b>dal '.Translator::dateToLocale($rscontrattii[0]['data_accettazione']).' al '.Translator::dateToLocale($rscontrattii[0]['data_conclusione'])."</b></span>\n";
$body .= "</td></tr>\n";

// ESCLUSIONI
$body .= "<tr><td>\n";
$body .= "<big><b><u>ESCLUSIONI:</u></b></big>\n";
$body .= "</td>\n";

$body .= "<td>\n";
$body .= '<span><b>'.$rscontrattii[0]['esclusioni']."</b></span>\n";
$body .= "</td></tr>\n";

$body .= "</table>\n";
$body .= "<br/><br/>\n";

$body .= "<span><b>Il tutto S.E. & O.</b></span><br/><br/>\n";
$body .= "<span>In attesa di un Vostro Cortese riscontro scritto, l&rsquo;occasione mi &egrave; gradita per porgere Cordiali Saluti.</span>\n";

$report_name = 'contratto_'.$idcontratto.'.pdf';
