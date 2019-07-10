<?php

include_once __DIR__.'/../../core.php';

use Plugins\StatisticheArticoli\Stats;

$calendar = filter('calendar');
$direzione = filter('dir');
$start = filter('start');
$end = filter('end');

$prezzi = Stats::prezzi($id_record, $start, $end, $direzione);
$prezzo_min = $prezzi['min'];
$prezzo_max = $prezzi['max'];
$prezzo_medio = $prezzi['media'];

$oscillazione = $prezzo_max['prezzo'] - $prezzo_min['prezzo'];
$oscillazione_percentuale = $oscillazione * 100 / $prezzo_medio ?: 0;

$data_min = strtotime($prezzo_min['data']);
$data_max = strtotime($prezzo_max['data']);
if ($data_min == $data_max) {
    $andamento = tr('N.D.');
} elseif ($data_min < $data_max) {
    $andamento = tr('In aumento');
} else {
    $andamento = tr('In diminuzione');
}

echo '
<tr id="row-'.$calendar.'">
    <td>'.dateFormat($start).' - '.dateFormat($end).'</td>
    <td>'.moneyFormat($prezzo_min['prezzo']).'</td>
    <td>'.moneyFormat($prezzo_medio).'</td>
    <td>'.moneyFormat($prezzo_max['prezzo']).'</td>
    <td>'.moneyFormat($oscillazione).'</td>
    <td>'.Translator::numberToLocale($oscillazione_percentuale, '2').' %</td>
    <td>'.$andamento.'</td>
</tr>';
