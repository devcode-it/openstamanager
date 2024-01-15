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

$d_totali = (int) setting('Cifre decimali per totali in stampa');

$somma_ore = sum($somma_ore);
$somma_imponibile = sum($somma_imponibile);
$somma_sconto = sum($somma_sconto);
$somma_totale_imponibile = sum($somma_totale_imponibile);
$somma_iva = sum($somma_iva);
$somma_totale_ivato = sum($somma_totale_ivato);
$somma_km = sum($somma_km);

if (setting('Formato ore in stampa') == 'Sessantesimi') {
    $somma_ore = Translator::numberToHours($somma_ore);
} else {
    $somma_ore = Translator::numberToLocale($somma_ore, $d_qta);
}

echo '
        <tr>
            <th width="5%" style="border-right: 0"></th>
            <th class="text-right" style="border-left: 0;">
                <b>'.tr('Totale', [], ['upper' => true]).':</b>
            </th>
            <th class="text-center">'.$somma_km.'</td>
            <th class="text-center">'.($pricing ? $somma_ore : '-').'</th>
            <th class="text-center">'.($pricing ? moneyFormat($somma_imponibile, $d_totali) : '-').'</th>
            <th class="text-center">'.($pricing ? moneyFormat($somma_sconto, $d_totali) : '-').'</th>
            <th class="text-center">'.($pricing ? moneyFormat($somma_totale_imponibile, $d_totali) : '-').'</th>
        </tr>

        <tr>
            <th width="5%" style="border-right: 0"></th>
            <th class="text-right" style="border-left: 0;">
                <b>'.tr('Iva', [], ['upper' => true]).':</b>
            </th>
            <th colspan="4"></th>
            <th class="text-center">'.($pricing ? moneyFormat($somma_iva, $d_totali) : '-').'</th>
        </tr>

        <tr>
            <th width="5%" style="border-right: 0"></th>
            <th class="text-right" style="border-left: 0;">
                <b>'.tr('Totale Ivato', [], ['upper' => true]).':</b>
            </th>
            <th colspan="4"></th>
            <th class="text-center">'.($pricing ? moneyFormat($somma_totale_ivato, $d_totali) : '-').'</th>
        </tr>
    </tbody>
</table>';
