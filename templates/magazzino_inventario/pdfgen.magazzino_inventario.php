<?php

include_once __DIR__.'/../../core.php';

// carica report html
$report = file_get_contents(__DIR__.'/magazzino_inventario.html');
$body = file_get_contents(__DIR__.'/magazzino_inventario_body.html');

$search_codice = $_GET['search_codice'];
$search_descrizione = $_GET['search_descrizione'];

if ($_GET['search_subcategoria'] == 'undefined') {
    $_GET['search_subcategoria'] = '';
}

if (!empty($_GET['search_categoria']) or !empty($_GET['search_subcategoria'])) {
    $search_categoria = $_GET['search_categoria'].' '.$_GET['search_subcategoria'];
}

$search_tipo = $_GET['search_tipo'];

if ($search_tipo == '') {
    $search_tipo = 'solo prodotti attivi';
}

if ($search_tipo == 'solo prodotti attivi') {
    $add_where = ' AND attivo=1';
} elseif ($search_tipo == 'solo prodotti non attivi') {
    $add_where = ' AND attivo=0';
} else {
    $add_where = '';
}

if ($search_codice != '') {
    $add_where .= " AND ( replace(codice,'.','') LIKE \"%$search_codice%\" OR codice LIKE \"%$search_codice%\" )";
}

if ($search_descrizione != '') {
    $add_where .= "  AND replace(descrizione,'.','') LIKE \"%$search_descrizione%\"";
}

$add_having = '';
if (!empty($search_categoria)) {
    $add_having .= "  AND CONCAT_WS( ' ', categoria, subcategoria ) LIKE '%".$search_categoria."%' ";
}

include_once $docroot.'/templates/pdfgen_variables.php';

// Ciclo tra gli articoli selezionati
// LEFT OUTER JOIN mg_unitamisura ON mg_unitamisura.id=mg_articoli.idum
// mg_unitamisura.valore AS um
// LEFT OUTER JOIN mg_categorie ON (mg_categorie.id=mg_articoli.id_categoria AND mg_categorie.parent = 0) OR (mg_categorie.id=mg_articoli.id_sottocategoria AND  mg_categorie.parent = 1)
$period_end = $_SESSION['period_end'];

$query = 'SELECT *, mg_articoli.id AS id_articolo, (SELECT nome FROM mg_categorie WHERE  mg_categorie.parent = 0 AND mg_categorie.id = mg_articoli.id_categoria) AS categoria, (SELECT nome FROM mg_categorie WHERE  mg_categorie.parent = 1 AND mg_categorie.id = mg_articoli.id_sottocategoria) AS subcategoria, (SELECT SUM(qta) FROM mg_movimenti WHERE mg_movimenti.idarticolo=mg_articoli.id AND (mg_movimenti.idintervento IS NULL OR mg_movimenti.idautomezzo = 0) AND data <= '.prepare($period_end).' ) AS qta FROM mg_articoli WHERE 1=1 '.$add_where.' HAVING  2=2 AND qta > 0 '.$add_having.' ORDER BY codice ASC';
$rs = $dbo->fetchArray($query);
$totrows = sizeof($rs);

$body .= '<h3>INVENTARIO AL '.Translator::dateToLocale($period_end)."</h3>\n";

$body .= "<table cellspacing='0' style='table-layout:fixed;'>\n";
$body .= "<col width='100'><col width='230'><col width='70'><col width='70'><col width='70'><col width='90'>\n";

$body .= "<tr>\n";
$body .= "<th bgcolor='#dddddd' class='full_cell1 cell-padded'>Codice</th>\n";
$body .= "<th bgcolor='#dddddd' class='full_cell cell-padded'>Descrizione</th>\n";
$body .= "<th bgcolor='#dddddd' class='full_cell cell-padded'>Prezzo di vendita</th>\n";
$body .= "<th bgcolor='#dddddd' class='full_cell cell-padded'>Q.tà</th>\n";
$body .= "<th bgcolor='#dddddd' class='full_cell cell-padded'>Prezzo di acquisto</th>\n";
$body .= "<th bgcolor='#dddddd' class='full_cell cell-padded'>Valore totale</th>\n";
$body .= "</tr>\n";

for ($r = 0; $r < sizeof($rs); ++$r) {
    $body .= "<tr>\n";
    $body .= "	<td class='first_cell cell-padded'>".$rs[$r]['codice']."</td>\n";
    $body .= "	<td class='table_cell cell-padded'>".$rs[$r]['descrizione']."</td>\n";
    $body .= "	<td class='table_cell text-right cell-padded'>".Translator::numberToLocale($rs[$r]['prezzo_vendita']).' '.currency()."</td>\n";
    $body .= "	<td class='table_cell text-right cell-padded'>".$rs[$r]['um'].' '.Translator::numberToLocale($rs[$r]['qta'])."</td>\n";
    $body .= "	<td class='table_cell text-right cell-padded'>".Translator::numberToLocale($rs[$r]['prezzo_acquisto']).' '.currency()."</td>\n";
    $body .= "	<td class='table_cell text-right cell-padded'>".Translator::numberToLocale(($rs[$r]['prezzo_acquisto'] * $rs[$r]['qta'])).' '.currency()."</td>\n";
    $body .= "</tr>\n";

    $totale_qta += $rs[$r]['qta'];
    $totale_acquisto += ($rs[$r]['prezzo_acquisto'] * $rs[$r]['qta']);
}

// Totali
$body .= "<tr>\n";
$body .= "<td colspan='2' bgcolor='#dddddd' class='first_cell text-right cell-padded'><b>TOTALE:</b></td>\n";
$body .= "<td bgcolor='#dddddd' class='first_cell text-right cell-padded'></td>\n";
$body .= "<td bgcolor='#dddddd' class='table_cell text-right cell-padded'><b>".Translator::numberToLocale($totale_qta)."</b></td>\n";
$body .= "<td bgcolor='#dddddd' class='first_cell text-right cell-padded'></td>\n";
$body .= "<td bgcolor='#dddddd' class='table_cell text-right cell-padded'><b>".Translator::numberToLocale($totale_acquisto).' '.currency()."</b></td>\n";
$body .= "</tr>\n";
$body .= "</table>\n";

$report_name = 'inventario.pdf';
