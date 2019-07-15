<?php

include_once __DIR__.'/../riepilogo_interventi/bottom.php';

$budget = get_imponibile_preventivo($id_record);

$rapporto = floatval($budget) - floatval($somma_totale_imponibile);

if ($pricing) {
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

    echo '
</table>';
}
