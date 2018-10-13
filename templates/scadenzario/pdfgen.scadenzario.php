<?php

include_once __DIR__.'/../../core.php';

$module_name = 'Scadenzario';

$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

// carica report html
$report = file_get_contents($docroot.'/templates/scadenzario/scadenzario.html');
$body = file_get_contents($docroot.'/templates/scadenzario/scadenzario_body.html');

include_once $docroot.'/templates/pdfgen_variables.php';

/*
    Dati scadenzario
*/
if ($_GET['type'] == 'clienti') {
    $titolo = 'Scadenzario clienti';
    $add_where = "AND co_tipidocumento.dir='entrata'";
} elseif ($_GET['type'] == 'fornitori') {
    $titolo = 'Scadenzario fornitori';
    $add_where = "AND co_tipidocumento.dir='uscita'";
} else {
    $titolo = 'Scadenzario';
    $add_where = '';
}

$body .= '<h3>'.$titolo.' dal '.Translator::dateToLocale($date_start).' al '.Translator::dateToLocale($date_end)."</h3>\n";
$body .= "<table class=\"table_values\" cellspacing=\"0\" border=\"0\" cellpadding=\"0\" style=\"table-layout:fixed; border-color:#aaa;\">\n";
$body .= "<col width=\"300\"><col width=\"200\"><col width=\"150\"><col width=\"50\"><col width=\"70\"><col width=\"70\">\n";

$body .= "<thead>\n";
$body .= "	<tr>\n";
$body .= "		<th style='padding:2mm; background:#eee;'>Documento</th>\n";
$body .= "		<th style='padding:2mm; background:#eee;'>Anagrafica</th>\n";
$body .= "		<th style='padding:2mm; background:#eee;'>Tipo di pagamento</th>\n";
$body .= "		<th style='padding:2mm; background:#eee;'>Data scadenza</th>\n";
$body .= "		<th style='padding:2mm; background:#eee;'>Importo</th>\n";
$body .= "		<th style='padding:2mm; background:#eee;'>Già pagato</th>\n";
$body .= "	</tr>\n";
$body .= "</thead>\n";

$body .= "<tbody>\n";

$rs = $dbo->fetchArray("SELECT co_scadenziario.id AS id, ragione_sociale AS `Anagrafica`, co_pagamenti.descrizione AS `Tipo di pagamento`, CONCAT( co_tipidocumento.descrizione, CONCAT( ' numero ', IF(numero_esterno<>'', numero_esterno, numero) ) ) AS `Documento`, DATE_FORMAT(data_emissione, '%d/%m/%Y') AS `Data emissione`, DATE_FORMAT(scadenza, '%d/%m/%Y') AS `Data scadenza`, da_pagare AS `Importo`, pagato AS `Pagato`, IF(scadenza<NOW(), '#ff7777', '') AS _bg_ FROM co_scadenziario
    INNER JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id
    INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica
    INNER JOIN co_pagamenti ON co_documenti.idpagamento=co_pagamenti.id
    INNER JOIN co_tipidocumento ON co_documenti.id_tipo_documento=co_tipidocumento.id
WHERE ABS(pagato) < ABS(da_pagare) ".$add_where." AND scadenza >= '".$date_start."' AND scadenza <= '".$date_end."' ORDER BY scadenza ASC");

for ($i = 0; $i < sizeof($rs); ++$i) {
    $body .= '	<tr>';
    $body .= '		<td>'.$rs[$i]['Documento'].'<br><small>'.$rs[$i]['Data emissione']."</small></td>\n";
    $body .= '		<td>'.$rs[$i]['Anagrafica']."</td>\n";
    $body .= '		<td>'.$rs[$i]['Tipo di pagamento']."</td>\n";
    $body .= "		<td align='center'>".$rs[$i]['Data scadenza']."</td>\n";
    $body .= "		<td align='right'>".Translator::numberToLocale($rs[$i]['Importo'])."</td>\n";
    $body .= "		<td align='right'>".Translator::numberToLocale($rs[$i]['Pagato'])."</td>\n";
    $body .= "	</tr>\n";

    $totale_da_pagare += $rs[$i]['Importo'];
    $totale_pagato += $rs[$i]['Pagato'];
}

$body .= "	<tr>\n";
$body .= "		<td colspan='4' align='right'><b>TOTALE:</b></td><td align='right'>".Translator::numberToLocale($totale_da_pagare)."</td><td align='right'>".Translator::numberToLocale($totale_pagato)."</td>\n";
$body .= "	</tr>\n";

$body .= "</tbody>\n";
$body .= "</table>\n";

$orientation = 'L';
$report_name = 'Scadenzario_Totale.pdf';
