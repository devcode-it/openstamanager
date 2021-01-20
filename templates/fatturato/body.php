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
