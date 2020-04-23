<?php

include_once __DIR__.'/../../core.php';

if ($dir == 'entrata') {
    $title = tr('Fatturato mensile dal _START_ al _END_', [
        '_START_' => Translator::dateToLocale($date_start),
        '_END_' => Translator::dateToLocale($date_end),
    ], ['upper' => true]);
} else {
    $title = tr('Acquisti mensili dal _START_ al _END_', [
        '_START_' => Translator::dateToLocale($date_start),
        '_END_' => Translator::dateToLocale($date_end),
    ], ['upper' => true]);
}

echo '
<h4><strong>'.$title.'</strong></h4>';

// Intestazione tabella per righe
echo '
<table class="table table-bordered">
    <thead>
        <tr>
            <th>'.tr('Mese').'</th>
            <th class="text-center" style="width: 15%">'.tr('Imponibile').'</th>
            <th class="text-center" style="width: 15%">'.tr('IVA').'</th>
            <th class="text-center" style="width: 15%">'.tr('Totale').'</th>
        </tr>
    </thead>

    <tbody>';

echo '
    </tbody>';

$totale_imponibile = 0;
$totale_iva = 0;
$totale_finale = 0;

// Nel fatturato totale è corretto NON tenere in considerazione eventuali rivalse, ritenute acconto o contributi.
foreach ($raggruppamenti as $raggruppamento) {
    $data = new \Carbon\Carbon($raggruppamento['data']);
    $mese = ucfirst($data->formatLocalized('%B %Y'));

    $imponibile = $raggruppamento['imponibile'];
    $iva = $raggruppamento['iva'];
    $totale = $raggruppamento['totale'];

    echo '
        <tr>
            <td>'.$mese.'</td>
            <td class="text-right">'.moneyFormat($imponibile).'</td>
            <td class="text-right">'.moneyFormat($iva).'</td>
            <td class="text-right">'.moneyFormat($totale).'</td>
        </tr>';

    $totale_imponibile += $imponibile;
    $totale_iva += $iva;
    $totale_finale += $totale;
}

echo '
        <tr>
            <td class="text-right text-bold">'.tr('Totale', [], ['upper' => true]).':</td>
            <td class="text-right text-bold">'.moneyFormat($totale_imponibile).'</td>
            <td class="text-right text-bold">'.moneyFormat($totale_iva).'</td>
            <td class="text-right text-bold">'.moneyFormat($totale_finale).'</td>
        </tr>
    </tbody>
</table>';
