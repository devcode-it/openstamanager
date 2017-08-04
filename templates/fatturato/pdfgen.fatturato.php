<?php

include_once __DIR__.'/../../core.php';

// carica report html
$report = file_get_contents($docroot.'/templates/fatturato/fatturato.html');
$body = file_get_contents($docroot.'/templates/fatturato/fatturato_body.html');

$mesi = ['', 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];

$dir = save($_GET['dir']);

include_once $docroot.'/templates/pdfgen_variables.php';

$totale_imponibile = 0;
$totale_iva = 0;
$totale = 0;

if ($dir == 'entrata') {
    $addwhere = $additional_where['Fatture di vendita'];
} else {
    $addwhere = $additional_where['Fatture di acquisto'];
}

// Ciclo tra le fatture selezionate
$query = "SELECT DATE_FORMAT( data, '%m-%Y' ) AS periodo, data FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id GROUP BY periodo, dir HAVING (data BETWEEN '".$_SESSION['period_start']."' AND '".$_SESSION['period_end']."') AND dir='".$dir."' ".$add_where.' ORDER BY data ASC';

$rs = $dbo->fetchArray($query);
$totrows = sizeof($rs);

if ($dir == 'entrata') {
    $body .= '<h3>FATTURATO MENSILE DAL '.Translator::dateToLocale($_SESSION['period_start']).' AL '.Translator::dateToLocale($_SESSION['period_end'])."</h3>\n";
} else {
    $body .= '<h3>ACQUISTI MENSILI DAL '.Translator::dateToLocale($_SESSION['period_start']).' AL '.Translator::dateToLocale($_SESSION['period_end'])."</h3>\n";
}

$body .= "<table cellspacing='0' style='table-layout:fixed;'>\n";
$body .= "<col width='320'><col width='100'><col width='100'><col width='100'>\n";

$body .= "<tr><th bgcolor='#dddddd' class='full_cell1 cell-padded' width='320'>Mese</th>\n";
$body .= "<th bgcolor='#dddddd' class='full_cell cell-padded' width='100'>Imponibile</th>\n";
$body .= "<th bgcolor='#dddddd' class='full_cell cell-padded' width='100'>Iva</th>\n";
$body .= "<th bgcolor='#dddddd' class='full_cell cell-padded' width='100'>Totale</th></tr>\n";

for ($r = 0; $r < sizeof($rs); ++$r) {
    // Lettura totali
    $rs2 = $dbo->fetchArray("SELECT SUM(subtotale-co_righe_documenti.sconto) AS imponibile, SUM(iva) AS iva, (SELECT SUM(bollo) FROM co_documenti WHERE DATE_FORMAT(data,'%m-%Y') = \"".$rs[$r]['periodo'].'" AND idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="'.$dir."\")) AS bollo, SUM(co_righe_documenti.rivalsainps) AS rivalsainps, SUM(co_righe_documenti.ritenutaacconto) AS ritenutaacconto FROM co_righe_documenti INNER JOIN co_documenti ON co_righe_documenti.iddocumento=co_documenti.id WHERE DATE_FORMAT(data,'%m-%Y') = \"".$rs[$r]['periodo'].'" AND idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="'.$dir.'")');

    $body .= "<tr><td class='first_cell cell-padded'>".$mesi[intval(date('m', strtotime($rs[$r]['data'])))].' '.date('Y', strtotime($rs[$r]['data']))."</td>\n";
    $body .= "<td class='table_cell cell-padded text-right'>".Translator::numberToLocale($rs2[0]['imponibile'], 2)." &euro;</td>\n";
    $body .= "<td class='table_cell cell-padded text-right'>".Translator::numberToLocale($rs2[0]['iva'], 2)." &euro;</td>\n";
    $body .= "<td class='table_cell cell-padded text-right'>".Translator::numberToLocale($rs2[0]['imponibile'] + $rs2[0]['iva'] + $rs2[0]['rivalsainps'] + $rs2[0]['bollo'] + $rs2[0]['ritenutaacconto'], 2)." &euro;</td></tr>\n";

    $totale_imponibile += $rs2[0]['imponibile'];
    $totale_iva += $rs2[0]['iva'];
    $totale += $rs2[0]['imponibile'] + $rs2[0]['iva'] + $rs2[0]['rivalsainps'] + $rs2[0]['bollo'] + $rs2[0]['ritenutaacconto'];
}

// Totali
$body .= "<tr>\n";
$body .= "	<td class='first_cell cell-padded text-right'><b>TOTALE:</b></td>\n";
$body .= "	<td class='table_cell text-right cell-padded'><b>".Translator::numberToLocale($totale_imponibile, 2)." &euro;</b></td>\n";
$body .= "	<td class='table_cell text-right cell-padded'><b>".Translator::numberToLocale($totale_iva, 2)." &euro;</b></td>\n";
$body .= "	<td class='table_cell text-right cell-padded'><b>".Translator::numberToLocale($totale, 2)." &euro;</b></td>\n";
$body .= "</tr>\n";

$body .= "</table>\n";

$report_name = 'inventario.pdf';
