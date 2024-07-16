<?php

echo '
<br><br><span><big><b>CARICO SUGLI AUTOMEZZI IL '.date('d/m/Y', strtotime((string) $dt_carico)).'</b></big></span><br>';

$targa = '';
$totale_qta = 0.000;
$totale_ven = 0.00;
if ($rs) {
    for ($r = 0; $r < sizeof($rs); ++$r) {
        if ($targa != $rs[$r]['targa']) {
            if ($targa != '') {
                echo "
<table cellspacing='0' style='table-layout:fixed;'>
    <col width='35'>
    <col width='275'>
    <col width='50'>
    <col width='70'>
    <col width='45'>
    <col width='65'>
    <col width='65'>
    <tr>
        <td class='first_cell cell-padded'>".'&nbsp;'."</td>
        <td class='table_cell cell-padded'>".'&nbsp;'."</td>
        <td class='table_cell cell-padded'>".'&nbsp;'."</td>
        <td class='table_cell text-right cell-padded'>".number_format($totale_qta, 3, ',', '.')."&nbsp;kg</td>
        <td class='table_cell text-right cell-padded'>".'&nbsp;'."</td>
        <td class='table_cell text-right cell-padded'>".number_format($totale_ven, 2, ',', '.')." &euro;</td>
        <td class='table_cell cell-padded'>".'&nbsp;'.'</td>
    </tr>
</table>';
            }

            echo "
                <br/>
<table cellspacing='0' style='table-layout:fixed;'>
    <col width='150'><col width='250'>
    <tr>
        <th bgcolor='#ffffff' class='full_cell1 cell-padded' width='150'>Targa: ".$rs[$r]['targa']."</th>
        <th bgcolor='#ffffff' class='full_cell cell-padded' width='250'>Automezzo: ".$rs[$r]['nome'].'</th>
    </tr>
</table>';

            echo "
<table class='table table-bordered' cellspacing='0' style='table-layout:fixed;'>
    <col width='35'><col width='275'><col width='50'><col width='70'><col width='45'><col width='65'><col width='65'>
    <tr>
        <th bgcolor='#dddddd' class='full_cell1 cell-padded' width='10%'>Codice</th>
        <th bgcolor='#dddddd' class='full_cell cell-padded' >Descrizione</th>
        <th bgcolor='#dddddd' class='full_cell cell-padded' width='20%'>Sub.Cat.</th>
        <th bgcolor='#dddddd' class='full_cell cell-padded' width='10%'>Quantit&agrave;</th>
        <th bgcolor='#dddddd' class='full_cell cell-padded' width='10%'>P. Ven.</th>
        <th bgcolor='#dddddd' class='full_cell cell-padded' width='10%'>Totale</th>
        <th bgcolor='#dddddd' class='full_cell cell-padded' width='10%'>Utente</th>
    </tr>";
            $targa = $rs[$r]['targa'];
            $totale_qta = 0.000;
            $totale_ven = 0.00;
        }
        echo '
    <tr>';
        $qta = number_format($rs[$r]['qta'], 3, ',', '.').'&nbsp;'.$rs[$r]['um'];

        $prz_vendita = number_format($rs[$r]['prezzo_vendita'], 2);
        $prz_vendita += ($prz_vendita / 100) * $rs[$r]['iva'];
        $totv = number_format($prz_vendita, 2) * $rs[$r]['qta'];

        echo "
        <td class='first_cell cell-padded'>".$rs[$r]['codice']."</td>
        <td class='table_cell cell-padded'>".$rs[$r]['descrizione']."</td>
        <td class='table_cell cell-padded'>".$rs[$r]['subcategoria']."</td>
        <td class='table_cell text-right cell-padded'>".$qta."</td>
        <td class='table_cell text-right cell-padded'>".number_format($prz_vendita, 2, ',', '.')." &euro;</td>
        <td class='table_cell text-right cell-padded'>".number_format($totv, 2, ',', '.')." &euro;</td>
        <td class='table_cell cell-padded'>".ucfirst((string) $rs[$r]['username']).'</td>
    </tr>';

        $totale_ven = $totale_ven + $totv;
        if ($rs[$r]['um'] == 'kg') {
            $totale_qta = $totale_qta + $rs[$r]['qta'];
        }
    }
    echo '
</table>';
} else {
    echo 'Nessun articolo caricato sugli automezzi il '.date('d/m/Y', strtotime((string) $dt_carico)),'.';
}

if ($targa != '') {
    echo "
<table cellspacing='0' style='table-layout:fixed;'>
    <tr>
        <td class='first_cell cell-padded'>".'&nbsp;'."</td>
        <td class='table_cell cell-padded'>".'&nbsp;'."</td>
        <td class='table_cell cell-padded'>".'&nbsp;'."</td>
        <td class='table_cell text-right cell-padded'>".number_format($totale_qta, 3, ',', '.')."&nbsp;kg</td>
        <td class='table_cell text-right cell-padded'>".'&nbsp;'."</td>
        <td class='table_cell text-right cell-padded'>".number_format($totale_ven, 2, ',', '.')." &euro;</td>
        <td class='table_cell cell-padded'>".'&nbsp;'.'</td>
    </tr>
</table>';
}
