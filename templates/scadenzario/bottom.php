<?php

include_once __DIR__.'/../../core.php';

$totale_da_pagare = sum(array_column($records, 'Importo'));
$totale_pagato = sum(array_column($records, 'Pagato'));

echo '
        <tr>
            <td colspan="4" class="text-right">
                <b>'.tr('Totale', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">'.moneyFormat($totale_da_pagare, 2).'</td>
            <td class="text-right">'.moneyFormat($totale_pagato, 2).'</td>
        </tr>
    </tbody>
</table>';
