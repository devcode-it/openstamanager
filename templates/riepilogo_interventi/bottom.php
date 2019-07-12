<?php

include_once __DIR__.'/../../core.php';

$somma_imponibile = sum($somma_imponibile);
$somma_sconto = sum($somma_sconto);
$somma_totale_imponibile = sum($somma_totale_imponibile);

echo '
        <tr>
            <th width="5%" style="border-right: 0"></th>
            <th class="text-right" style="border-left: 0;">
                <b>'.tr('Totale', [], ['upper' => true]).':</b>
            </th>
            <th class="text-right">'.moneyFormat($somma_imponibile, 2).'</th>
            <th class="text-right">'.moneyFormat($somma_sconto, 2).'</th>
            <th class="text-right">'.moneyFormat($somma_totale_imponibile, 2).'</th>
        </tr>
    </tbody>
</table>';
