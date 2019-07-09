<?php

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
                '.moneyFormat($somma_totale).'
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

