<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

include_once __DIR__.'/../riepilogo_interventi/bottom.php';

$budget = get_imponibile_preventivo($id_record);

$rapporto = floatval($budget) - floatval($somma_totale_imponibile);

$rs = $dbo->fetchArray("SELECT SUM(qta) AS totale_ore FROM `co_righe_contratti` WHERE um='ore' AND idcontratto = ".prepare($id_record));
$totale_ore = $rs[0]['totale_ore'];
$totale_ore_impiegate = $records->sum('ore_totali');

if ($pricing || !empty($totale_ore)) {
    // Totale imponibile
    echo '
<table class="table table-bordered">';
    if ($pricing && empty($options['dir'])) {
        // TOTALE
        echo '
    <tr>
    	<td colspan="3" class="text-right border-top">
            <b>'.tr('Totale consuntivo (no iva)', [], ['upper' => true]).':</b>
    	</td>
    	<th colspan="2" class="text-center">
    		<b>'.moneyFormat($somma_totale_imponibile).'</b>
    	</th>
    </tr>';

        // BUDGET
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Budget (no IVA)', [], ['upper' => true]).':</b>
        </td>
        <th colspan="2" class="text-center">
            <b>'.moneyFormat($budget).'</b>
        </th>
    </tr>';

        // RAPPORTO
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Rapporto budget/spesa (no IVA)', [], ['upper' => true]).':</b>
        </td>
        <th colspan="2" class="text-center">
            <b>'.moneyFormat($rapporto).'</b>
        </th>
    </tr>';
    }

    // ORE RESIDUE
    if (!empty($totale_ore)) {
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Ore residue', [], ['upper' => true]).':</b>
        </td>
        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($totale_ore - $totale_ore_impiegate).'</b><br>
            <p>'.tr('Ore erogate').': '.Translator::numberToLocale($totale_ore_impiegate).'</p>
            <p>'.tr('Ore a contratto').': '.Translator::numberToLocale($totale_ore).'</p>
        </th>
    </tr>';
    }

    echo '
</table>';
}
