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

$totale_iva = sum(array_column($records, 'iva'));
$totale_subtotale = sum(array_column($records, 'subtotale'));

echo '
    </tbody>
</table>

<br><br>
<h4><b>'.tr('Riepilogo IVA', [], ['upper' => true]).'</b></h4>

<table class="table" style="width:50%">
    <thead>
        <tr bgcolor="#dddddd">
            <th>'.tr('Iva').'</th>
            <th class="text-center">'.tr('Imponibile').'</th>
            <th class="text-center">'.tr('Imposta').'</th>
        </tr>
    </thead>

    <tbody>';

foreach ($iva as $descrizione => $tot_iva) {
    if (!empty($descrizione)) {
        $somma_iva = sum($iva[$descrizione]);
        $somma_totale = sum($totale[$descrizione]);

        echo '
        <tr>
            <td>
                '.$descrizione.'
            </td>

            <td class="text-right">
                '.moneyFormat($somma_totale).'
            </td>

            <td class="text-right">
                '.moneyFormat($somma_iva).'
            </td>
        </tr>';
    }
}

echo '

        <tr bgcolor="#dddddd">
            <td class="text-right">
                <b>'.tr('Totale', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">'.moneyFormat($totale_subtotale).'</td>
            <td class="text-right">'.moneyFormat($totale_iva).'</td>
        </tr>
    </tbody>
</table>';
