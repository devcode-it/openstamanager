<?php

include_once __DIR__.'/../../core.php';

// carica report html
$report = file_get_contents($docroot.'/templates/fatturato/fatturato.html');
$body = file_get_contents($docroot.'/templates/fatturato/fatturato_body.html');

$mesi = months();

$dir = get('dir');

$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

include_once $docroot.'/templates/pdfgen_variables.php';

$totale_imponibile = 0;
$totale_iva = 0;
$totale = 0;

if ($dir == 'entrata') {
    $addwhere = Modules::getAdditionalsQuery('Fatture di vendita');
} else {
    $addwhere = Modules::getAdditionalsQuery('Fatture di acquisto');
}

// Ciclo tra le fatture selezionate
$query = "SELECT DATE_FORMAT( data, '%m-%Y' ) AS periodo, data FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id GROUP BY periodo, dir HAVING (data BETWEEN '".$date_start."' AND '".$date_end."') AND dir='".$dir."' ".$add_where.' ORDER BY data ASC';

$rs = $dbo->fetchArray($query);
$totrows = sizeof($rs);

if ($dir == 'entrata') {
    $body .= '<h3>FATTURATO MENSILE DAL '.Translator::dateToLocale($date_start).' AL '.Translator::dateToLocale($date_end)."</h3>\n";
} else {
    $body .= '<h3>ACQUISTI MENSILI DAL '.Translator::dateToLocale($date_start).' AL '.Translator::dateToLocale($date_end)."</h3>\n";
}

$body .= "<table cellspacing='0' style='table-layout:fixed;'>\n";
$body .= "<col width='320'><col width='100'><col width='100'><col width='100'>\n";

$body .= "<tr><th bgcolor='#dddddd' class='full_cell1 cell-padded' width='320'>Mese</th>\n";
$body .= "<th bgcolor='#dddddd' class='full_cell cell-padded' width='100'>Imponibile</th>\n";
$body .= "<th bgcolor='#dddddd' class='full_cell cell-padded' width='100'>Iva</th>\n";
$body .= "<th bgcolor='#dddddd' class='full_cell cell-padded' width='100'>Totale</th></tr>\n";

for ($r = 0; $r < sizeof($rs); ++$r) {
    // Lettura totali
    $rs2 = $dbo->fetchArray("SELECT SUM(subtotale-co_righe_documenti.sconto) AS imponibile, SUM(iva) AS iva FROM co_righe_documenti INNER JOIN co_documenti ON co_righe_documenti.iddocumento=co_documenti.id WHERE DATE_FORMAT(data,'%m-%Y') = \"".$rs[$r]['periodo'].'" AND idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="'.$dir.'")');

    $body .= "<tr><td class='first_cell cell-padded'>".$mesi[intval(date('m', strtotime($rs[$r]['data'])))].' '.date('Y', strtotime($rs[$r]['data']))."</td>\n";
    $body .= "<td class='table_cell cell-padded text-right'>".moneyFormat($rs2[0]['imponibile'])."</td>\n";
    $body .= "<td class='table_cell cell-padded text-right'>".moneyFormat($rs2[0]['iva'])."</td>\n";
    $body .= "<td class='table_cell cell-padded text-right'>".moneyFormat($rs2[0]['imponibile'] + $rs2[0]['iva'])."</td></tr>\n";

    $totale_imponibile += $rs2[0]['imponibile'];
    $totale_iva += $rs2[0]['iva'];
    //Nel fatturato totale è corretto NON tenere in considerazione eventuali rivalse, ritenute acconto o contributi.
    $totale += $rs2[0]['imponibile'] + $rs2[0]['iva'];
}

// Totali
$body .= "<tr>\n";
$body .= "	<td class='first_cell cell-padded text-right'><b>TOTALE:</b></td>\n";
$body .= "	<td class='table_cell text-right cell-padded'><b>".moneyFormat($totale_imponibile)."</b></td>\n";
$body .= "	<td class='table_cell text-right cell-padded'><b>".moneyFormat($totale_iva)."</b></td>\n";
$body .= "	<td class='table_cell text-right cell-padded'><b>".moneyFormat($totale)."</b></td>\n";
$body .= "</tr>\n";

$body .= "</table>\n";
