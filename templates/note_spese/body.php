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

$stato = [
    'bozza' => tr('Bozza'),
    'confermata' => tr('Confermata'),
    'rimborsata' => tr('Rimborsata'),
][$record['stato']] ?? $record['stato'];

echo '
<h3 class="text-center">'.tr('Nota spese _NUM_', [
    '_NUM_' => $record['numero'],
]).'</h3>

<table class="table table-bordered">
    <tr>
        <td style="width: 50%;"><strong>'.tr('Data').':</strong> '.dateFormat($record['data']).'</td>
        <td><strong>'.tr('Stato').':</strong> '.$stato.'</td>
    </tr>
    <tr>
        <td colspan="2"><strong>'.tr('Persona').':</strong> '.$record['ragione_sociale'].'</td>
    </tr>
    <tr>
        <td colspan="2"><strong>'.tr('Oggetto').':</strong> '.$record['oggetto'].'</td>
    </tr>
</table>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th style="width: 15%;">'.tr('Data').'</th>
            <th style="width: 20%;">'.tr('Categoria').'</th>
            <th>'.tr('Descrizione').'</th>
            <th class="text-right" style="width: 18%;">'.tr('Importo').'</th>
        </tr>
    </thead>
    <tbody>';

if (empty($righe)) {
    echo '
        <tr>
            <td colspan="4" class="text-center text-muted">'.tr('Nessuna riga inserita.').'</td>
        </tr>';
}

foreach ($righe as $riga) {
    echo '
        <tr>
            <td>'.dateFormat($riga['data']).'</td>
            <td>'.ucfirst($riga['categoria']).'</td>
            <td>'.$riga['descrizione'].'</td>
            <td class="text-right">'.moneyFormat($riga['importo']).'</td>
        </tr>';
}

echo '
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3" class="text-right">'.tr('Totale').'</th>
            <th class="text-right">'.moneyFormat($record['totale']).'</th>
        </tr>
    </tfoot>
</table>';

if (!empty($record['note'])) {
    echo '
<h4>'.tr('Note').'</h4>
<div>'.$record['note'].'</div>';
}
