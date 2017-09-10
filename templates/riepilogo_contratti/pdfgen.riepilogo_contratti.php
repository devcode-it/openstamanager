<?php

include_once __DIR__.'/../../core.php';

$module_name = 'Contratti';

$additional_where['Contratti'] = str_replace('|idtecnico|', "'".$user['idanagrafica']."'", $additional_where['Contratti']);

// carica parametri di ricerca
$search_numero = save($_GET['search_numerocontratto']);
($search_numero != '') ? $search_numerocontratto = ' AND numero="'.$search_numero.'"' : $search_numerocontratto = '';
$search_nome = save($_GET['search_nome']);
$search_ragione_sociale = save($_GET['search_ragione_sociale']);
$search_idstato = save($_GET['search_idstato']);
isset($_GET['search_datastart']) ? $search_datastart = save($_GET['search_datastart']) : $search_datastart = '01/'.date('m/Y', strtotime('-6 year'));
isset($_GET['search_dataend']) ? $search_dataend = save($_GET['search_dataend']) : $search_dataend = date('t/m/Y', strtotime('+2 year'));

if ($search_idstato != '') {
    $WHERE = " AND idstato='$search_idstato'";
} else {
    $WHERE = '';
}

// Lettura contratti che soddisfano la ricerca
$query = 'SELECT *, (SELECT SUM(subtotale) FROM co_righe2_contratti WHERE idcontratto=co_contratti.id) AS budget_totale, co_staticontratti.descrizione AS stato FROM co_staticontratti INNER JOIN (co_contratti INNER JOIN an_anagrafiche ON co_contratti.idanagrafica=an_anagrafiche.idanagrafica) ON co_contratti.idstato=co_staticontratti.id WHERE nome LIKE "%'.$search_nome."%\" AND ( replace(ragione_sociale,'.','') LIKE \"%$search_ragione_sociale%\" OR ragione_sociale LIKE \"%$search_ragione_sociale%\" ) ".$search_numerocontratto." AND ((data_bozza BETWEEN '".Translator::dateToEnglish($search_datastart)."' AND '".Translator::dateToEnglish($search_dataend)."') OR (data_accettazione BETWEEN '".Translator::dateToEnglish($search_datastart)."' AND '".Translator::dateToEnglish($search_dataend)."') OR (data_rifiuto BETWEEN '".Translator::dateToEnglish($search_datastart)."' AND '".Translator::dateToEnglish($search_dataend)."') OR (data_conclusione BETWEEN '".Translator::dateToEnglish($search_datastart)."' AND '".Translator::dateToEnglish($search_dataend)."')) $WHERE ".$additional_where['Contratti'].' ORDER BY data_bozza ASC, co_contratti.id DESC';
$rs = $dbo->fetchArray($query);

// Se il cliente è uno solo carico la sua intestazione, altrimenti la lascio in bianco
$idcliente = $rsi[0]['idanagrafica'];
$singolo_cliente = true;
for ($i = 0; $i < sizeof($rs); ++$i) {
    if ($rs[$i]['idanagrafica'] != $idcliente) {
        $singolo_cliente = false;
    }
}

if (!$singolo_cliente) {
    $idcliente = '';
}

// carica report html
$report = file_get_contents($docroot.'/templates/riepilogo_contratti/contratto.html');
$body = file_get_contents($docroot.'/templates/riepilogo_contratti/contratto_body.html');

if (!$singolo_cliente) {
    $body = str_replace('Spett.le', '', $body);
}

include_once $docroot.'/templates/pdfgen_variables.php';

$body .= '<big><big><b>RIEPILOGO CONTRATTI DAL '.$search_datastart.' al '.$search_dataend."</b></big></big><br/><br/>\n";

// Sostituisco i valori tra | | con il valore del campo del db
$body .= preg_replace('/|(.+?)|/', $rs[0]['${1}'], $body);

// Tabella con riepilogo contratti
$body .= "<table class=\"table_values\" style=\"table-layout:fixed;\" border=\"0\">\n";
$body .= "<col width='14'><col width='60'><col width='60'><col width='35'><col width='30'><col width='30'><col width='20'>\n";
$body .= "<thead>\n";
$body .= "<tr><th align=\"left\" style=\"width:14mm;\">\n";
$body .= "	Numero\n";
$body .= "</th>\n";

$body .= "<th align=\"center\" style=\"width:60mm;\">\n";
$body .= "	Ragione&nbsp;sociale\n";
$body .= "</th>\n";

$body .= "<th align=\"center\" style=\"width:60mm;\">\n";
$body .= "	Nome\n";
$body .= "</th>\n";

$body .= "<th align=\"center\" style=\"width:35mm;\">\n";
$body .= "	Stato\n";
$body .= "</th>\n";

$body .= "<th align=\"center\" style=\"width:30mm;\">\n";
$body .= "	Data&nbsp;inizio\n";
$body .= "</th>\n";

$body .= "<th align=\"center\" style=\"width:30mm;\">\n";
$body .= "	Data&nbsp;conclusione\n";
$body .= "</th>\n";

$body .= "<th align=\"center\" style=\"width:20mm;\">\n";
$body .= "	Budget\n";
$body .= "</th></tr>\n";
$body .= "</thead>\n";

$body .= "<tbody>\n";

// Tabella con i dati
for ($i = 0; $i < sizeof($rs); ++$i) {
    $data_accettazione = Translator::dateToLocale($rs[$i]['data_accettazione']);
    $data_conclusione = Translator::dateToLocale($rs[$i]['data_conclusione']);

    if ($data_accettazione == '01/01/1970') {
        $data_accettazione = '';
    }

    if ($data_conclusione == '01/01/1970') {
        $data_conclusione = '';
    }

    $body .= "<tr><td align=\"center\" style=\"width:14mm;\">\n";
    $body .= '	'.$rs[$i]['numero']."\n";
    $body .= "</td>\n";

    $body .= "<td align=\"left\" style=\"width:60mm;\">\n";
    $body .= '	'.str_replace(' ', ' ', $rs[$i]['ragione_sociale'])."\n";
    $body .= "</td>\n";

    $body .= "<td align=\"left\" style=\"width:60mm;\">\n";
    $body .= '	'.str_replace(' ', ' ', $rs[$i]['nome'])."\n";
    $body .= "</td>\n";

    $body .= "<td align=\"center\" style=\"width:35mm;\">\n";
    $body .= '	'.str_replace(' ', ' ', $rs[$i]['stato'])."\n";
    $body .= "</td>\n";

    $body .= "<td align=\"center\" style=\"width:30mm;\">\n";
    $body .= '	'.$data_accettazione."\n";
    $body .= "</td>\n";

    $body .= "<td align=\"center\" style=\"width:30mm;\">\n";
    $body .= '	'.$data_conclusione."\n";
    $body .= "</td>\n";

    $body .= "<td align=\"right\" style=\"width:20mm;\">\n";
    $body .= '	'.Translator::numberToLocale($rs[$i]['budget_totale'], 2).' &euro;\n';
    $body .= "</td></tr>\n";

    $totale += $rs[$i]['budget_totale'];
}

// Totale
$body .= "<tr><td colspan=\"6\" align=\"right\">\n";
$body .= "	<b>TOTALE:</b>\n";
$body .= "</td>\n";

$body .= "<td align=\"right\">\n";
$body .= '	<b>'.Translator::numberToLocale($totale, 2)." &euro;</b>\n";
$body .= "</td></tr>\n";

$body .= "</tbody>\n";
$body .= "</table>\n";

$orientation = 'L';
$report_name = 'Riepilogo_contratti.pdf';
