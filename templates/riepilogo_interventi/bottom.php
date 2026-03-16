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

$d_totali = (int) setting('Cifre decimali per totali in stampa');

$somma_ore = sum($somma_ore);
$somma_imponibile = sum($somma_imponibile);
$somma_sconto = sum($somma_sconto);
$somma_totale_imponibile = sum($somma_totale_imponibile);
$somma_iva = sum($somma_iva);
$somma_totale_ivato = sum($somma_totale_ivato);
$somma_km = sum($somma_km);

if (setting('Formato ore in stampa') == 'Sessantesimi') {
    $somma_ore = Translator::numberToHours($somma_ore);
} else {
    $somma_ore = Translator::numberToLocale($somma_ore, $d_qta);
}

echo '
        <tr style="background-color: #eee;">
            <th width="5%" class="border-end-0"></th>
            <th class="text-right text-muted border-start-0">
                <b>'.tr('Totale', [], ['upper' => true]).':</b>
            </th>
            <th class="text-center">'.$somma_km.'</td>
            <th class="text-center">'.$somma_ore.'</th>
            <th class="text-center">'.($pricing ? moneyFormat($somma_imponibile, $d_totali) : '-').'</th>
            <th class="text-center">'.($pricing ? moneyFormat($somma_sconto, $d_totali) : '-').'</th>
            <th class="text-center">'.($pricing ? moneyFormat($somma_totale_imponibile, $d_totali) : '-').'</th>
        </tr>

        <tr style="background-color: #eee;">
            <th width="5%" class="border-end-0"></th>
            <th class="text-right text-muted border-start-0">
                <b>'.tr('Iva', [], ['upper' => true]).':</b>
            </th>
            <th colspan="4"></th>
            <th class="text-center">'.($pricing ? moneyFormat($somma_iva, $d_totali) : '-').'</th>
        </tr>

        <tr style="background-color: #eee;">
            <th width="5%" class="border-end-0"></th>
            <th class="text-right text-muted border-start-0">
                <b>'.tr('Totale Ivato', [], ['upper' => true]).':</b>
            </th>
            <th colspan="4"></th>
            <th class="text-center">'.($pricing ? moneyFormat($somma_totale_ivato, $d_totali) : '-').'</th>
        </tr>
    </tbody>
</table>';

// Sezione riepilogativa materiali e sessioni
if (!empty($riepilogo_materiali) || !empty($riepilogo_sessioni)) {
    echo '
<div class="mt-5">
    <div class="row">';

    // Riepilogo materiali
    if (!empty($riepilogo_materiali)) {
        echo '
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="text-center"><b>'.tr('Riepilogo materiale utilizzato', [], ['upper' => true]).'</b></h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th class="small-bold">'.tr('Descrizione').'</th>
                                <th class="text-center small-bold">'.tr('Qta').'</th>
                                <th class="text-center small-bold">'.tr('Prezzo unitario').'</th>
                                <th class="text-center small-bold">'.tr('Totale').'</th>
                            </tr>
                        </thead>
                        <tbody>';

        foreach ($riepilogo_materiali as $descrizione => $dati) {
            echo '
                            <tr>
                                <td>'.$descrizione.'</td>
                                <td class="text-center">'.Translator::numberToLocale($dati['qta'], $d_qta).' '.$dati['um'].'</td>
                                <td class="text-center">'.($pricing ? moneyFormat($dati['prezzo'], $d_importi) : '-').'</td>
                                <td class="text-center fw-bold">'.($pricing ? moneyFormat($dati['totale'], $d_importi) : '-').'</td>
                            </tr>';
        }

        echo '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>';
    }

    // Riepilogo sessioni per tipo di attività
    if (!empty($riepilogo_sessioni)) {
        echo '
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h6 class="text-center"><b>'.tr('Riepilogo ore per tipo di attività', [], ['upper' => true]).'</b></h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th class="small-bold">'.tr('Tipo attività').'</th>
                                <th class="text-center small-bold">'.tr('Ore totali').'</th>
                                <th class="text-center small-bold">'.tr('Prezzo totale').'</th>
                            </tr>
                        </thead>
                        <tbody>';

        foreach ($riepilogo_sessioni as $tipo => $dati) {
            if (setting('Formato ore in stampa') == 'Sessantesimi') {
                $ore_formatted = Translator::numberToHours($dati['ore']);
            } else {
                $ore_formatted = Translator::numberToLocale($dati['ore'], $d_qta);
            }
            echo '
                            <tr>
                                <td>'.$tipo.'</td>
                                <td class="text-center">'.$ore_formatted.'</td>
                                <td class="text-center fw-bold">'.($pricing ? moneyFormat($dati['prezzo_totale'], $d_importi) : '-').'</td>
                            </tr>';
        }

        echo '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>';
    }

    echo '
    </div>
</div>';
}
