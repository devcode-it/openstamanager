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
$oscillazione_percentuale = $prezzo_medio ? $oscillazione * 100 / $prezzo_medio : 0;

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
