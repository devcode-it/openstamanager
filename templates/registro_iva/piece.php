<?php

include_once __DIR__.'/../../core.php';

$previous_number = $previous_number ?: null;

if ($record['numero'] != $previous_number) {
    if ($color == '#dddddd') {
        $color = '#ffffff';
    } elseif ($color == '#ffffff') {
        $color = '#dddddd';
    }
}

echo '
        <tr bgcolor="'.$color.'" >';

if ($record['numero'] == $previous_number) {
    echo '
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>';
} else {
    echo '
            <td>'.(($dir == 'uscita') ? $record['numero'] : '-').'</td>
            <td>'.$record['numero_esterno'].'</td>
            <td>'.Translator::datetoLocale($record['data']).'</td>
            <td>'.$record['codice_tipo_documento_fe'].'</td>
            <td>'.$record['codice_anagrafica'].' / '.safe_truncate(mb_strtoupper(html_entity_decode($record['ragione_sociale']), 'UTF-8'), 50).'</td>
            <td>'.moneyFormat($record['totale']).'</td>';
}

echo '
            <td class="text-right">'.moneyFormat($record['subtotale']).'</td>
            <td class="text-center">'.Translator::numberToLocale($record['percentuale'], 0).'</td>
            <td class="text-center">'.$record['desc_iva'].'</td>
            <td class="text-right">'.moneyFormat($record['iva']).'</td>
        </tr>';

$previous_number = $record['numero'];

$iva[$record['desc_iva']][] = $record['iva'];
$totale[$record['desc_iva']][] = $record['subtotale'];
