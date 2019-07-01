<?php

include_once __DIR__.'/../../core.php';

$module_name = 'Scadenzario';
$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

$module = Modules::get('Scadenzario');
$id_module = $module['id'];

$total = Util\Query::readQuery($module);

// Lettura parametri modulo
$module_query = $total['query'];

$search_filters = [];

if (is_array($_SESSION['module_'.$id_module])) {
    foreach ($_SESSION['module_'.$id_module] as $field => $value) {
        if (!empty($value) && starts_with($field, 'search_')) {
            $field_name = str_replace('search_', '', $field);
            $field_name = str_replace('__', ' ', $field_name);
            $field_name = str_replace('-', ' ', $field_name);
            array_push($search_filters, '`'.$field_name.'` LIKE "%'.$value.'%"');
        }
    }
}

if (!empty($search_filters)) {
    $module_query = str_replace('2=2', '2=2 AND ('.implode(' AND ', $search_filters).') ', $module_query);
}

// Filtri derivanti dai permessi (eventuali)
$module_query = Modules::replaceAdditionals($id_module, $module_query);

$scadenze = $dbo->fetchArray($module_query);

// carica report html
$report = file_get_contents($docroot.'/templates/scadenzario/scadenzario.html');
$body = file_get_contents($docroot.'/templates/scadenzario/scadenzario_body.html');

include_once $docroot.'/templates/pdfgen_variables.php';

//Filtro in base al segmento
$id_segment = $_SESSION['module_'.$id_module]['id_segment'];
$rs_segment = $dbo->fetchArray('SELECT * FROM zz_segments WHERE id='.prepare($id_segment));

$add_where = 'AND '.$rs_segment[0]['clause'];

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

/*$rs = $dbo->fetchArray("SELECT co_scadenziario.id AS id, ragione_sociale AS `Anagrafica`, co_pagamenti.descrizione AS `Tipo di pagamento`, CONCAT( co_tipidocumento.descrizione, CONCAT( ' numero ', IF(numero_esterno<>'', numero_esterno, numero) ) ) AS `Documento`, DATE_FORMAT(data_emissione, '%d/%m/%Y') AS `Data emissione`, DATE_FORMAT(scadenza, '%d/%m/%Y') AS `Data scadenza`, da_pagare AS `Importo`, pagato AS `Pagato`, IF(scadenza<NOW(), '#ff7777', '') AS _bg_ FROM co_scadenziario
    INNER JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id
    INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica
    INNER JOIN co_pagamenti ON co_documenti.idpagamento=co_pagamenti.id
    INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id
WHERE ABS(pagato) < ABS(da_pagare) ".$add_where." AND scadenza >= '".$date_start."' AND scadenza <= '".$date_end."' ORDER BY scadenza ASC");*/

for ($i = 0; $i < sizeof($scadenze); ++$i) {
    $body .= '	<tr>';
    $body .= '		<td>'.$scadenze[$i]['Rif. Fattura'].'<br><small>'.Translator::dateToLocale($scadenze[$i]['Data emissione'])."</small></td>\n";
    $body .= '		<td>'.$scadenze[$i]['Anagrafica']."</td>\n";
    $body .= '		<td>'.$scadenze[$i]['Tipo di pagamento']."</td>\n";
    $body .= "		<td align='center'>".Translator::dateToLocale($scadenze[$i]['Data scadenza'])."</td>\n";
    $body .= "		<td align='right'>".moneyFormat($scadenze[$i]['Importo'], 2)."</td>\n";
    $body .= "		<td align='right'>".moneyFormat($scadenze[$i]['Pagato'], 2)."</td>\n";
    $body .= "	</tr>\n";

    $totale_da_pagare += $scadenze[$i]['Importo'];
    $totale_pagato += $scadenze[$i]['Pagato'];
}

$body .= "	<tr>\n";
$body .= "		<td colspan='4' align='right'><b>TOTALE:</b></td><td align='right'>".moneyFormat($totale_da_pagare, 2)."</td><td align='right'>".moneyFormat($totale_pagato, 2)."</td>\n";
$body .= "	</tr>\n";

$body .= "</tbody>\n";
$body .= "</table>\n";

$orientation = 'L';
$report_name = 'Scadenzario_Totale.pdf';
