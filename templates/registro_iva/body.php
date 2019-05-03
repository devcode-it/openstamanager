<?php

include_once __DIR__.'/../../core.php';

$dir = $_GET['dir'];
if ($dir == 'entrata') {
    $tipo = 'vendite';
} else {
    $tipo = 'acquisti';
}

$report_name = 'registro_iva_'.$tipo.'.pdf';

$periodo = $_GET['periodo'];

$v_iva = [];
$v_totale = [];

$totale_iva = 0;
$totale_subtotale = 0;

$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

$query = 'SELECT *, co_documenti.id AS id, IF(numero = "", numero_esterno, numero) AS numero, SUM(subtotale-co_righe_documenti.sconto) AS subtotale, SUM(iva) AS iva, (SELECT ragione_sociale FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=co_documenti.idanagrafica) AS ragione_sociale, (SELECT codice FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=co_documenti.idanagrafica) AS codice_anagrafica FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_iva ON co_righe_documenti.idiva=co_iva.id WHERE dir = '.prepare($dir).' AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND co_documenti.data >= '.prepare($date_start).' AND co_documenti.data <= '.prepare($date_end).' GROUP BY co_documenti.id, co_righe_documenti.idiva ORDER BY co_documenti.id, co_documenti.'.(($dir == 'entrata') ? 'data' : 'numero');
$rs = $dbo->fetchArray($query);

if ('entrata' == $dir) {
    echo "<span style='font-size:12pt;'><b>".tr('Registro iva vendita dal _START_ al _END_ _ESERCIZIO_', [
        '_START_' => Translator::dateToLocale($date_start),
        '_END_' => Translator::dateToLocale($date_end),
        '_ESERCIZIO_' => (date('Y', strtotime($date_start)) == date('Y', strtotime($date_end)) ? '- Esercizio '.date('Y', strtotime($date_end)) : ''),
    ], ['upper' => true]).'</b></span><br><br>';
} elseif ('uscita' == $dir) {
    echo "<span style='font-size:12pt;'><b>".tr('Registro iva acquisto dal _START_ al _END_ _ESERCIZIO_', [
        '_START_' => Translator::dateToLocale($date_start),
        '_END_' => Translator::dateToLocale($date_end),
        '_ESERCIZIO_' => (date('Y', strtotime($date_start)) == date('Y', strtotime($date_end)) ? '- Esercizio '.date('Y', strtotime($date_end)) : ''),
    ], ['upper' => true]).'</b></span><br><br>';
}

echo "
        <table cellspacing='0' style='table-layout:fixed;'>

            <thead>
            <tr>
				<th bgcolor='#dddddd'>N<sup>o</sup> prot.</th>
                <th bgcolor='#dddddd'>N<sup>o</sup> doc.</th>
                <th bgcolor='#dddddd'>Data</th>
                <th bgcolor='#dddddd'>Tipo</th>
                <th bgcolor='#dddddd'>".(($dir == 'entrata') ? 'Cliente' : 'Fornitore')."</th>
                <th bgcolor='#dddddd'>Tot doc.</th>
                <th bgcolor='#dddddd'>Imponibile</th>
                <th bgcolor='#dddddd'>%</th>
                <th bgcolor='#dddddd'>Iva</th>
                <th bgcolor='#dddddd'>Imposta</th>
            </tr>
            </thead>
        ";

for ($i = 0; $i < sizeof($rs); ++$i) {
    echo '  <tr>';

    if ($rs[$i]['numero'] == $rs[$i - 1]['numero']) {
        echo '	<td></td>';
        echo '	<td></td>';
        echo '	<td></td>';
        echo '	<td></td>';
        echo '	<td></td>';
        echo '	<td></td>';
    } else {
        echo '	<td>'.(($dir == 'uscita') ? $rs[$i]['numero'] : '-').'</td>';
        echo '	<td>'.$rs[$i]['numero_esterno'].'</td>';
        echo '	<td>'.date('d/m/Y', strtotime($rs[$i]['data'])).'</td>';
        echo '	<td>'.$rs[$i]['codice_tipo_documento_fe'].'</td>';
        echo '  <td>'.$rs[$i]['codice_anagrafica'].' / '.tr($rs[$i]['ragione_sociale'], [], ['upper' => true]).'</td>';
        echo "	<td class='text-right'>".moneyFormat(get_totale_fattura($rs[$i]['iddocumento'])).'</td>';
    }

    echo "	    <td class='text-right'>".moneyFormat($rs[$i]['subtotale']).'</td>';
    echo "	    <td class='text-center'>".Translator::numberToLocale($rs[$i]['percentuale'], 0).'</td>';
    echo "	    <td class='text-center'>".$rs[$i]['desc_iva'].'</td>';
    echo "	    <td class='text-right'>".moneyFormat($rs[$i]['iva']).'</td>';
    echo '  </tr>';

    $v_iva[$rs[$i]['desc_iva']] += $rs[$i]['iva'];
    $v_totale[$rs[$i]['desc_iva']] += $rs[$i]['subtotale'];

    $totale_iva += $rs[$i]['iva'];
    $totale_subtotale += $rs[$i]['subtotale'];
}

echo '
        </table>';

echo "  <br><br><span style='font-size:12pt;'><b>RIEPILOGO IVA</b></span><br><br>";

echo "
        <table cellspacing='0' style='table-layout:fixed;' style='width:50%'>
            <tr>
                <th bgcolor='#dddddd'>Iva</th>
                <th bgcolor='#dddddd'>Imponibile</th>
                <th bgcolor='#dddddd'>Imposta</th>
            </tr>
        ";

foreach ($v_iva as $desc_iva => $tot_iva) {
    if ('' != $desc_iva) {
        echo "
            <tr>
                <td valign='top'>\n";
        echo        $desc_iva."\n";
        echo "  </td>\n";

        echo "  <td valign='top' align='right'>\n";
        echo        moneyFormat($v_totale[$desc_iva])."\n";
        echo "  </td>\n";

        echo "  <td valign='top' align='right'>\n";
        echo        moneyFormat($v_iva[$desc_iva])."\n";
        echo "  </td>
            </tr>\n";
    }
}

echo "	    <tr bgcolor='#dddddd'>
                <td><b>TOTALE</b></td>
                <td class='text-right'>".moneyFormat($totale_subtotale)."</td>
                <td class='text-right'>".moneyFormat($totale_iva).'</td>
            </tr>';

echo '
        </table>';
