<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';

$totale_iva_vendite = sum(array_column($iva_vendite, 'iva'), null, 2);
$totale_subtotale_vendite = sum(array_column($iva_vendite, 'subtotale'), null, 2);
$totale_iva_acquisti = sum(array_column($iva_acquisti, 'iva'), null, 2);
$totale_subtotale_acquisti = sum(array_column($iva_acquisti, 'subtotale'), null, 2);

$totale_iva_esigibile = sum(array_column($iva_vendite_esigibile, 'iva'), null, 2);
$totale_iva_nonesigibile = sum(array_column($iva_vendite_nonesigibile, 'iva'), null, 2);
$subtotale_iva_esigibile = sum(array_column($iva_vendite_esigibile, 'subtotale'), null, 2);
$subtotale_iva_nonesigibile = sum(array_column($iva_vendite_nonesigibile, 'subtotale'), null, 2);

$totale_iva_detraibile = sum(array_column($iva_acquisti_detraibile, 'iva'), null, 2);
$totale_iva_nondetraibile = sum(array_column($iva_acquisti_nondetraibile, 'iva'), null, 2);
$subtotale_iva_detraibile = sum(array_column($iva_acquisti_detraibile, 'subtotale'), null, 2);
$subtotale_iva_nondetraibile = sum(array_column($iva_acquisti_nondetraibile, 'subtotale'), null, 2);

$totale_iva_vendite_anno_precedente = sum(array_column($iva_vendite_anno_precedente, 'iva'), null, 2);
$totale_iva_acquisti_anno_precedente = sum(array_column($iva_acquisti_anno_precedente, 'iva'), null, 2);
$totale_iva_anno_precedente = $totale_iva_vendite_anno_precedente - $totale_iva_acquisti_anno_precedente;

$totale_iva_vendite_periodo_precedente = sum(array_column($iva_vendite_periodo_precedente, 'iva'), null, 2);
$totale_iva_acquisti_periodo_precedente = sum(array_column($iva_acquisti_periodo_precedente, 'iva'), null, 2);
$totale_iva_periodo_precedente = $totale_iva_vendite_periodo_precedente - $totale_iva_acquisti_periodo_precedente;

$totale_iva = $totale_iva_esigibile - $totale_iva_detraibile;

if ($periodo == 'Trimestrale') {
    if ($totale_iva_periodo_precedente > 0) {
        $totale_iva += $totale_iva_periodo_precedente;
    }
    $maggiorazione = $totale_iva * 0.01;
    $totale_iva_maggiorata = $totale_iva + $maggiorazione;
}

echo '
<h5 class="text-center">VENDITE</h5>
<table class="table table-condensed table-striped table-bordered">
<thead>
    <tr>
        <th width="15%">Aliquota</th>
        <th width="15%">Natura IVA</th>
        <th width="30%">Descrizione</th>
        <th class="text-right" width="20%">Imponibile</th>
        <th class="text-right" width="20%">Imposta</th>
    </tr>
</thead> 
<tbody>
    <tr>
        <th class="text-center" colspan="5">IVA ESIGIBILE DEL PERIODO</th>
    </tr>';

// Somma importi arrotondati per fattura
$aliquote = [];

foreach ($iva_vendite_esigibile as $record) {
    $aliquote[$record['descrizione']]['aliquota'] = $record['aliquota'];
    $aliquote[$record['descrizione']]['cod_iva'] = $record['cod_iva'];
    $aliquote[$record['descrizione']]['descrizione'] = $record['descrizione'];
    $aliquote[$record['descrizione']]['subtotale'] += sum($record['subtotale'], null, 2);
    $aliquote[$record['descrizione']]['iva'] += sum($record['iva'], null, 2);
}

foreach ($aliquote as $aliquota => $record) {
    echo '
    <tr>
        <td>'.round($record['aliquota']).'%</td>
        <td>'.$record['cod_iva'].'</td>
        <td>'.$record['descrizione'].'</td>
        <td class=text-right>'.moneyFormat($record['subtotale'], 2).'</td>
        <td class=text-right>'.moneyFormat($record['iva'], 2).'</td>
    </tr>';
}
echo '
<tr>
        <td colspan="2"></td>
        <td>TOTALI</td>
        <td class=text-right>'.moneyFormat($subtotale_iva_esigibile, 2).'</td>
        <td class=text-right>'.moneyFormat($totale_iva_esigibile, 2).'</td>
    </tr>
    
<tr>
    <th class="text-center" colspan="5">IVA NON ESIGIBILE DEL PERIODO</th>
</tr>';

// Somma importi arrotondati per fattura
$aliquote = [];

foreach ($iva_vendite_nonesigibile as $record) {
    $aliquote[$record['descrizione']]['aliquota'] = $record['aliquota'];
    $aliquote[$record['descrizione']]['cod_iva'] = $record['cod_iva'];
    $aliquote[$record['descrizione']]['descrizione'] = $record['descrizione'];
    $aliquote[$record['descrizione']]['subtotale'] += sum($record['subtotale'], null, 2);
    $aliquote[$record['descrizione']]['iva'] += sum($record['iva'], null, 2);
}

foreach ($aliquote as $aliquota => $record) {
    echo '
    <tr>
        <td>'.round($record['aliquota']).'%</td>
        <td>'.$record['cod_iva'].'</td>
        <td>'.$record['descrizione'].'</td>
        <td class=text-right>'.moneyFormat($record['subtotale'], 2).'</td>
        <td class=text-right>'.moneyFormat($record['iva'], 2).'</td>
    </tr>';
}
echo '
<tr>
    <td colspan="2"></td>
    <td>TOTALI</td>
    <td class=text-right>'.moneyFormat($subtotale_iva_nonesigibile, 2).'</td>
    <td class=text-right>'.moneyFormat($totale_iva_nonesigibile, 2).'</td>
</tr>

<tr>
    <th class="text-center" colspan="5">RIEPILOGO GENERALE IVA VENDITE</th>
</tr>';

// Somma importi arrotondati per fattura
$aliquote = [];

foreach ($iva_vendite as $record) {
    $aliquote[$record['descrizione']]['aliquota'] = $record['aliquota'];
    $aliquote[$record['descrizione']]['cod_iva'] = $record['cod_iva'];
    $aliquote[$record['descrizione']]['descrizione'] = $record['descrizione'];
    $aliquote[$record['descrizione']]['subtotale'] += sum($record['subtotale'], null, 2);
    $aliquote[$record['descrizione']]['iva'] += sum($record['iva'], null, 2);
}

foreach ($aliquote as $aliquota => $record) {
    echo '
    <tr>
        <td>'.round($record['aliquota']).'%</td>
        <td>'.$record['cod_iva'].'</td>
        <td>'.$record['descrizione'].'</td>
        <td class=text-right>'.moneyFormat($record['subtotale'], 2).'</td>
        <td class=text-right>'.moneyFormat($record['iva'], 2).'</td>
    </tr>';
}
echo '
<tr>
    <th colspan="2"></th>
    <th>TOTALE</th>
    <th class=text-right>'.moneyFormat($totale_subtotale_vendite, 2).'</th>
    <th class=text-right>'.moneyFormat($totale_iva_vendite, 2).'</th>
</tr>
</tbody>
</table>


<h5 class="text-center">ACQUISTI</h5>
<table class="table table-condensed table-striped table-bordered">
<thead>
    <tr>
        <th width="15%">Aliquota</th>
        <th width="15%">Natura IVA</th>
        <th width="30%">Descrizione</th>
        <th class="text-right" width="20%">Imponibile</th>
        <th class="text-right" width="20%">Imposta</th>
    </tr>
</thead> 
<tbody>
    <tr>
        <th class="text-center" colspan="5">IVA DETRAIBILE DEL PERIODO</th>
    </tr>';

// Somma importi arrotondati per fattura
$aliquote = [];

foreach ($iva_acquisti_detraibile as $record) {
    $aliquote[$record['descrizione']]['aliquota'] = $record['aliquota'];
    $aliquote[$record['descrizione']]['cod_iva'] = $record['cod_iva'];
    $aliquote[$record['descrizione']]['descrizione'] = $record['descrizione'];
    $aliquote[$record['descrizione']]['subtotale'] += sum($record['subtotale'], null, 2);
    $aliquote[$record['descrizione']]['iva'] += sum($record['iva'], null, 2);
}

foreach ($aliquote as $aliquota => $record) {
    echo '
    <tr>
        <td>'.round($record['aliquota']).'%</td>
        <td>'.$record['cod_iva'].'</td>
        <td>'.$record['descrizione'].'</td>
        <td class=text-right>'.moneyFormat($record['subtotale'], 2).'</td>
        <td class=text-right>'.moneyFormat($record['iva'], 2).'</td>
    </tr>';
}
echo '
<tr>
    <td colspan="2"></td>
    <td>TOTALI</td>
    <td class=text-right>'.moneyFormat($subtotale_iva_detraibile, 2).'</td>
    <td class=text-right>'.moneyFormat($totale_iva_detraibile, 2).'</td>
</tr>


<tr>
    <th class="text-center" colspan="5">IVA NON DETRAIBILE DEL PERIODO</th>
</tr>';

// Somma importi arrotondati per fattura
$aliquote = [];

foreach ($iva_acquisti_nondetraibile as $record) {
    $aliquote[$record['descrizione']]['aliquota'] = $record['aliquota'];
    $aliquote[$record['descrizione']]['cod_iva'] = $record['cod_iva'];
    $aliquote[$record['descrizione']]['descrizione'] = $record['descrizione'];
    $aliquote[$record['descrizione']]['subtotale'] += sum($record['subtotale'], null, 2);
    $aliquote[$record['descrizione']]['iva'] += sum($record['iva'], null, 2);
}

foreach ($aliquote as $aliquota => $record) {
    echo '
    <tr>
        <td>'.round($record['aliquota']).'%</td>
        <td>'.$record['cod_iva'].'</td>
        <td>'.$record['descrizione'].'</td>
        <td class=text-right>'.moneyFormat($record['subtotale'], 2).'</td>
        <td class=text-right>'.moneyFormat($record['iva'], 2).'</td>
    </tr>';
}
echo '
<tr>
    <td colspan="2"></td>
    <td>TOTALI</td>
    <td class=text-right>'.moneyFormat($subtotale_iva_nondetraibile, 2).'</td>
    <td class=text-right>'.moneyFormat($totale_iva_nondetraibile, 2).'</td>
</tr>


<tr>
    <th class="text-center" colspan="5">RIEPILOGO GENERALE IVA ACQUISTI</th>
</tr>';

// Somma importi arrotondati per fattura
$aliquote = [];

foreach ($iva_acquisti as $record) {
    $aliquote[$record['descrizione']]['aliquota'] = $record['aliquota'];
    $aliquote[$record['descrizione']]['cod_iva'] = $record['cod_iva'];
    $aliquote[$record['descrizione']]['descrizione'] = $record['descrizione'];
    $aliquote[$record['descrizione']]['subtotale'] += sum($record['subtotale'], null, 2);
    $aliquote[$record['descrizione']]['iva'] += sum($record['iva'], null, 2);
}

foreach ($aliquote as $aliquota => $record) {
    echo '
    <tr>
        <td>'.round($record['aliquota']).'%</td>
        <td>'.$record['cod_iva'].'</td>
        <td>'.$record['descrizione'].'</td>
        <td class=text-right>'.moneyFormat($record['subtotale'], 2).'</td>
        <td class=text-right>'.moneyFormat($record['iva'], 2).'</td>
    </tr>';
}

echo '
<tr>
    <th colspan="2"></th>
    <th>TOTALE</th>
    <th class=text-right>'.moneyFormat($totale_subtotale_acquisti, 2).'</th>
    <th class=text-right>'.moneyFormat($totale_iva_acquisti, 2).'</th>
</tr>
</tbody>
</table>

<br>
<br>
<table class="table table-condensed table-striped table-bordered">
<thead>
    <tr>
        <th class="text-center" colspan="2">PROSPETTO RIEPILOGATIVO DI LIQUIDAZIONE IVA</th>
    <tr>
        <th width="70%">DESCRIZIONE</th>
        <th class="text-right" width="30%">IMPORTO</th>
    </tr>
</thead>
<tbody>
    <tr>';
if ($totale_iva_anno_precedente >= 0) {
    echo ' <td>DEBITO ANNO PRECEDENTE</td>';
} else {
    echo ' <td>CREDITO ANNO PRECEDENTE</td>';
}
echo '<td class=text-right>'.moneyFormat(abs($totale_iva_anno_precedente), 2).'</td>
    </tr>
    <tr>';
if ($totale_iva_periodo_precedente >= 0) {
    echo ' <td>DEBITO PERIODO PRECEDENTE</td>';
} else {
    echo ' <td>CREDITO PERIODO PRECEDENTE</td>';
}
echo ' <td class=text-right>'.moneyFormat(abs($totale_iva_periodo_precedente), 2).'</td>
    </tr>    
    <tr>
        <td>TOTALE IVA SU VENDITE ESIGIBILE</td>
        <td class=text-right>'.moneyFormat($totale_iva_esigibile, 2).'</td>
    </tr>    
    <tr>
        <td>TOTALE IVA OGGETTIVAMENTE NON A DEBITO SU VENDITE</td>
        <td class=text-right>'.moneyFormat($totale_iva_nonesigibile, 2).'</td>
    </tr>
    <tr>
        <td>TOTALE IVA SU ACQUISTI DETRAIBILI</td>
        <td class=text-right>'.moneyFormat($totale_iva_detraibile, 2).'</td>
    </tr>
    <tr>
        <td>TOTALE IVA OGGETTIVAMENTE INDETRAIBILI SU ACQUISTI</td>
        <td class=text-right>'.moneyFormat($totale_iva_nondetraibile, 2).'</td>
    </tr>
    <tr>
        <td>VARIAZIONE DI IMPOSTA RELATIVE A PERIODI PRECEDENTI</td>
            <td class=text-right>'.($totale_iva_periodo_precedente > 0 ? moneyFormat(abs($totale_iva_periodo_precedente), 2):'').'</td>


    </tr>
    <tr>
        <td>DI CUI INTERESSI PER RAVVEDIMENTO</td>
        <td class=text-right></td>
    </tr>
    <tr>
        <td>DI CUI INTERESSI PER MAGGIORAZIONE TRIMESTRALI</td>
        <td class=text-right></td>
    </tr>
    <tr>
        <td>CREDITO IVA COMPENSABILE</td>
        <td class=text-right></td>
    </tr>
    <tr>
        <td>'.($totale_iva >= 0 ? 'IVA A DEBITO' : 'IVA A CREDITO') .'</td>
        <td class=text-right>'.moneyFormat(abs($totale_iva), 2).'</td>
    </tr>
    <tr>
        <td>CREDITO SPECIALE DI IMPOSTA</td>
        <td class=text-right></td>
    </tr>
    <tr>
        <td>MAGGIORAZIONE 1,00%</td>
        <td class=text-right>'.($periodo == 'Trimestrale' ? moneyFormat($maggiorazione, 2) : '').'</td>
    </tr>
    <tr>
        <td>IVA A DEBITO CON MAGGIORAZIONE</td>
            <td class=text-right>'.($periodo == 'Trimestrale' ?moneyFormat($totale_iva_maggiorata, 2): '').'</td>


    </tr>
    <tr>
        <td>IMPORTO DA VERSARE</td>
            <td class=text-right>'.($periodo == 'Mensile' ?moneyFormat($totale_iva, 2) : moneyFormat($totale_iva_maggiorata, 2)).'</td>



    </tr>
    <tr>
        <td>CREDITO INFRANNUALE DI IMPOSTA CHIESTO A RIMBORSO</td>
        <td class=text-right></td>
    </tr>
    <tr>
        <td>CREDITO INFRANNUALE DA UTILIZZARE IN COMPENSAZIONE</td>
        <td class=text-right></td>
    </tr>
</tbody>
</table>';
