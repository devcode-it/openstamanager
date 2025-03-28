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

include_once __DIR__.'/../riepilogo_interventi/bottom.php';

$budget = get_imponibile_preventivo($id_record);
$somma_totale_imponibile = get_totale_interventi_preventivo($id_record);
$rapporto = floatval($budget) - floatval($somma_totale_imponibile) - $documento->provvigione;

$d_qta = (int) setting('Cifre decimali per quantità in stampa');
$d_totali = (int) setting('Cifre decimali per importi in stampa');
$d_totali = (int) setting('Cifre decimali per totali in stampa');

if ($pricing && empty($options['dir'])) {
    // Totale imponibile
    echo '
<table class="table table-bordered">';

    // TOTALE
    echo '
    <tr>
    	<td colspan="3" class="text-right border-top">
            <b>'.tr('Totale consuntivo (no iva)', [], ['upper' => true]).':</b>
    	</td>
    	<th colspan="2" class="text-center">
    		<b>'.moneyFormat($somma_totale_imponibile, $d_totali).'</b>
    	</th>
    </tr>';

    // BUDGET
    echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Budget (no IVA)', [], ['upper' => true]).':</b>
        </td>
        <th colspan="2" class="text-center">
            <b>'.moneyFormat($budget, $d_totali).'</b>
        </th>
    </tr>';

    // RAPPORTO
    echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Rapporto budget/spesa (no IVA)', [], ['upper' => true]).':</b>
        </td>
        <th colspan="2" class="text-center">
            <b>'.moneyFormat($rapporto, $d_totali).'</b>
        </th>
    </tr>';

    echo '
</table>';
}
