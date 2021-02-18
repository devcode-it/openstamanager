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

if (get('lev') == '2' || get('lev') == '3') {
    $dare = 0;
    $avere = 0;
    for ($i = 0; $i < sizeof($records); ++$i) {
        if ($records[$i]['totale'] >= 0) {
            $dare += $records[$i]['totale'];
        } else {
            $avere += $records[$i]['totale'];
        }
    }
    echo '
    <tr>
        <th></th>
        <th>SALDO FINALE</th>
        <th class="text-right">'.moneyFormat(abs($dare)).'</th>
        <th class="text-right">'.moneyFormat(abs($avere)).'</th>
    </tr>';
} elseif (get('lev') == '1') {
    $totale_attivo = 0;
    $totale_passivo = 0;
    for ($i = 0; $i < sizeof($patrimoniale); ++$i) {
        if ($patrimoniale[$i]['totale'] >= 0) {
            $totale_attivo += $patrimoniale[$i]['totale'];
        } else {
            $totale_passivo += $patrimoniale[$i]['totale'];
        }
    }
    echo '</table>
    <table class="table table-striped table-bordered">
    <tr>
        <th width="25%">TOTALE ATTIVITÀ</th>
        <th width="25%" class="text-right">'.moneyFormat(abs($totale_attivo)).'</th>
        <th width="25%">PASSIVITÀ</th>
        <th width="25%" class="text-right">'.moneyFormat(abs($totale_passivo)).'</th>
    </tr>
    <tr>';

    if ($utile_perdita['totale'] <= 0) {
        echo '  
            <th></th>
            <th></th>
            <th>UTILE</th>
            <th class="text-right">'.moneyFormat(abs($utile_perdita['totale'])).'</th>
        </tr>';
        $totale_passivo = abs($totale_passivo + $utile_perdita['totale']);
    } else {
        echo '  
            <th>PERDITA</th>
            <th class="text-right">'.moneyFormat(abs($utile_perdita['totale'])).'</th>
            <th></th>
            <th></th>
        </tr>';
        $totale_attivo = abs($totale_attivo + $utile_perdita['totale']);
    }

    echo '
    <tr>
        <th>TOTALE A PAREGGIO</th>
        <th class="text-right">'.moneyFormat(abs($totale_attivo)).'</th>
        <th>TOTALE A PAREGGIO</th>
        <th class="text-right">'.moneyFormat(abs($totale_passivo)).'</th>
    </tr>
    </table>';
}

echo '</tbody></table>';
