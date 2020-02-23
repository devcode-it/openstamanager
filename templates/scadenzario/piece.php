<?php

include_once __DIR__.'/../../core.php';

echo '
    <tr>
        <td>
            '.$record['Rif. Fattura'].'<br>
            <small>'.Translator::dateToLocale($record['Data emissione']).'</small>
        </td>
        <td>'.$record['Anagrafica'].'</td>
        <td>'.$record['Tipo di pagamento'].'</td>
        <td class="text-center">'.Translator::dateToLocale($record['Data scadenza']).'</td>
        <td class="text-right">'.moneyFormat($record['Importo'], 2).'</td>
        <td class="text-right">'.moneyFormat($record['Pagato'], 2).'</td>
    </tr>';
