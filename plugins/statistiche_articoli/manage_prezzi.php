<?php

include_once __DIR__.'/../../core.php';

use Plugins\StatisticheArticoli\Stats;

$calendar_id = filter('calendar_id');
$direzione = filter('direzione');
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
<tr id="row-'.$calendar_id.'">
    <td class="text-center">'.$calendar_id.'</td>
    <td>'.dateFormat($start).' - '.dateFormat($end).'</td>
    <td class="text-right">'.moneyFormat($prezzo_min['prezzo']).'</td>
    <td class="text-right">'.moneyFormat($prezzo_medio).'</td>
    <td class="text-right">'.moneyFormat($prezzo_max['prezzo']).'</td>
    <td class="text-right">'.moneyFormat($oscillazione).'</td>
    <td class="text-right">'.Translator::numberToLocale($oscillazione_percentuale, '2').' %</td>
    <td>'.$andamento.'</td>
</tr>';
