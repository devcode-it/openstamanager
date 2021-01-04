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


if ($record['data']!=$last_data && !empty($last_data)) {
    echo '
    <tr>
        <td colspan="4" class="text-right"><b>TOTALE GIORNO</b></td>
        <td class="text-right"><b>'.moneyFormat(abs($dare_giorno), 2).'</b></td>
        <td class="text-right"><b>'.moneyFormat(abs($avere_giorno), 2).'</b></td>
    </tr>';
    $totale_dare += $dare_giorno;
    $totale_avere += $avere_giorno;
    $dare_giorno = 0;
    $avere_giorno = 0;
}

echo '
<tr>
    <td class="text-center">'.dateFormat($record['data']).'</td>
    <td class="text-center">'.$record['numero2'].'.'.$record['numero'].'</td>
    <td>'.$record['conto'].'</td>
    <td>'.$record['descrizione'].'</td>';

    if ($record['totale'] >= 0) {
        echo '<td class="text-right">'.moneyFormat(abs($record['totale']), 2).'</td>
        <td></td>';
        $dare_giorno += $record['totale'];
    } else {
        echo ' <td></td>
        <td class="text-right">'.moneyFormat(abs($record['totale']), 2).'</td>';
        $avere_giorno += $record['totale'];
    }


echo '
</tr>';

if (end($records)==$record) {
    echo '
    <tr>
        <td colspan="4" class="text-right"><b>TOTALE GIORNO</b></td>
        <td class="text-right"><b>'.moneyFormat(abs($dare_giorno), 2).'</b></td>
        <td class="text-right"><b>'.moneyFormat(abs($avere_giorno), 2).'</b></td>
    </tr>';
    $totale_dare += $dare_giorno;
    $totale_avere += $avere_giorno;
    $dare_giorno = 0;
    $avere_giorno = 0;
}

$last_data = $record['data'];

    