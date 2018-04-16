<?php

//carica report html
$report = file_get_contents($docroot.'/templates/registro_iva/registroiva.html');
$body = file_get_contents($docroot.'/templates/registro_iva/registroiva_body.html');

include_once __DIR__.'/../pdfgen_variables.php';

$dir = $_GET['dir'];
$periodo = $_GET['periodo'];

$v_iva = [];
$v_totale = [];

$totale_iva = 0;
$totale_subtotale = 0;

$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

/*
$periodo = explode('-', $periodo);

switch ($periodo[0]) {
    //anno
    case 'Y':
        $date_start = $periodo[2].'-01-01';
        $date_end = $periodo[2].'-12-31';
        break;
    //mese
    case 'M':
        $date_start = $periodo[2].'-'.$periodo[1].'-01';
        $date_end = date('Y-m-t', strtotime($date_start));
        break;
    //trimestre
    case 'T':
        $date_start = $periodo[2].'-'.$periodo[1].'-01';
        $date_end = date('Y-m-t', strtotime('+2 months', strtotime($date_start)));
        break;
    //semestre
    case 'S':
        $date_start = $periodo[2].'-'.$periodo[1].'-01';
        $date_end = date('Y-m-t', strtotime('+5 months', strtotime($date_start)));
        break;
}*/

$query = 'SELECT *, SUM(subtotale-co_righe_documenti.sconto) AS subtotale, SUM(iva) AS iva, (SELECT ragione_sociale FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=co_documenti.idanagrafica) AS ragione_sociale FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE dir = '.prepare($dir).' AND is_descrizione = 0 AND co_documenti.data >= '.prepare($date_start).' AND co_documenti.data <= '.prepare($date_end).' GROUP BY co_documenti.id, co_righe_documenti.idiva ORDER BY co_documenti.data';
$rs = $dbo->fetchArray($query);

if ('entrata' == $dir) {
    $body .= "<span style='font-size:15pt; margin-left:6px;'><b>".tr('Registro iva vendita dal _START_ al _END_', [
        '_START_' => Translator::dateToLocale($_SESSION['period_start']),
        '_END_' => Translator::dateToLocale($_SESSION['period_end']),
    ], ['upper' => true]).'</b></span><br><br>';
} elseif ('uscita' == $dir) {
    $body .= "<span style='font-size:15pt; margin-left:6px;'><b>".tr('Registro iva acquisto dal _START_ al _END_', [
        '_START_' => Translator::dateToLocale($_SESSION['period_start']),
        '_END_' => Translator::dateToLocale($_SESSION['period_end']),
    ], ['upper' => true]).'</b></span><br><br>';
}

$body .= "
        <table cellspacing='0' style='table-layout:fixed;'>
            <col width='90'><col width='90'><col width='450'><col width='120'><col width='120'><col width='90'><col width='90'>
            <thead>
            <tr>
                <th bgcolor='#dddddd' class='full_cell1 cell-padded'>N° doc.</th>
                <th bgcolor='#dddddd' class='full_cell cell-padded'>Data</th>
                <th bgcolor='#dddddd' class='full_cell cell-padded'>Causale<br>Ragione sociale</th>
                <th bgcolor='#dddddd' class='full_cell cell-padded'>Aliquota</th>
                <th bgcolor='#dddddd' class='full_cell cell-padded'>Imponibile</th>
                <th bgcolor='#dddddd' class='full_cell cell-padded'>Imposta</th>
            </tr>
            </thead>
        ";

for ($i = 0; $i < sizeof($rs); ++$i) {
    $body .= '<tr>';
    if ($rs[$i]['numero'] == $rs[$i - 1]['numero']) {
        $body .= "	<td class='first_cell cell-padded text-center'></td>";
        $body .= "	<td class='table_cell cell-padded text-center'></td>";
    } else {
        $body .= "	<td class='first_cell cell-padded text-center'>".(!empty($rs[$i]['numero_esterno']) ? $rs[$i]['numero_esterno'] : $rs[$i]['numero']).'</td>';
        $body .= "	<td class='table_cell cell-padded text-center'>".date('d/m/Y', strtotime($rs[$i]['data'])).'</td>';
    }

    if ('entrata' == $dir) {
        $body .= "<td class='table_cell cell-padded'>
                    Fattura di vendita<br>
                    ".$rs[$i]['ragione_sociale'].'
                    </td>';
    } elseif ('uscita' == $dir) {
        $body .= "<td class='table_cell cell-padded'>
                    Fattura di acquisto<br>
                    ".$rs[$i]['ragione_sociale'].'
                    </td>';
    }
    $body .= "	<td class='table_cell cell-padded'>".$rs[$i]['desc_iva'].'</td>';
    $body .= "	<td class='table_cell cell-padded text-right'>".Translator::numberToLocale($rs[$i]['subtotale']).' &euro;</td>';
    $body .= "	<td class='table_cell cell-padded text-right'>".Translator::numberToLocale($rs[$i]['iva']).' &euro;</td>';
    $body .= '</tr>';

    $v_iva[$rs[$i]['desc_iva']] += $rs[$i]['iva'];
    $v_totale[$rs[$i]['desc_iva']] += $rs[$i]['subtotale'];

    $totale_iva += $rs[$i]['iva'];
    $totale_subtotale += $rs[$i]['subtotale'];
}

$body .= '
        </table>
            ';

$body .= "<br><br><span style='font-size:12pt; margin-left:6px;'><b>RIEPILOGO IVA</b></span><br><br>";

$body .= "
        <table cellspacing='0' style='table-layout:fixed;'>
            <col width='140'><col width='90'><col width='90'>
            <tr>
                <th bgcolor='#dddddd' class='full_cell1 cell-padded'>Cod. IVA</th>
                <th bgcolor='#dddddd' class='full_cell1 cell-padded'>Imponibile</th>
                <th bgcolor='#dddddd' class='full_cell1 cell-padded'>Imposta</th>

            </tr>
        ";

foreach ($v_iva as $desc_iva => $tot_iva) {
    if ('' != $desc_iva) {
        $body .= "<tr><td valign='top' class='first_cell cell-padded'>\n";
        $body .= $desc_iva."\n";
        $body .= "</td>\n";

        $body .= "<td valign='top' align='right' class='table_cell cell-padded'>\n";
        $body .= Translator::numberToLocale($v_totale[$desc_iva])." &euro;\n";
        $body .= "</td>\n";

        $body .= "<td valign='top' align='right' class='table_cell cell-padded'>\n";
        $body .= Translator::numberToLocale($v_iva[$desc_iva])." &euro;\n";
        $body .= "</td></tr>\n";
    }
}

$body .= "	<tr bgcolor='#dddddd'>
                <td class='full_cell1 cell-padded text-right'><b>TOTALE</b></td>
                <td class='full_cell1 cell-padded text-right'>".Translator::numberToLocale($totale_subtotale)." &euro;</td>
                <td class='full_cell1 cell-padded text-right'>".Translator::numberToLocale($totale_iva).' &euro;</td>
            </tr>';

$body .= '
        </table>
            ';

$orientation = 'L';
$report_name = 'registro_iva.pdf';
