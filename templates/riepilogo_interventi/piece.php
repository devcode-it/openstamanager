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

use Modules\Interventi\Intervento;

$intervento = Intervento::find($record['id']);

$imponibile = empty($options['dir']) ? $intervento->imponibile : $intervento->spesa;
$sconto = empty($options['dir']) ? $intervento->sconto : 0;
$totale_imponibile = empty($options['dir']) ? $intervento->totale_imponibile : $intervento->spesa;

$somma_imponibile[] = $imponibile;
$somma_sconto[] = $sconto;
$somma_totale_imponibile[] = $totale_imponibile;

$pricing = isset($pricing) ? $pricing : true;

// Informazioni intervento
echo '
<tr>
    <td colspan="2">';

if (dateFormat($intervento->inizio)) {
    echo '
        <p>'.tr('Intervento _NUM_ del _DATE_', [
            '_NUM_' => $intervento->codice,
            '_DATE_' => dateFormat($intervento->inizio),
        ]).'</p>';
} else {
    echo '
        <p>'.tr('Promemoria _NUM_', [
            '_NUM_' => $intervento->codice,
        ]).'</p>';
}
echo '
        <p><small><b>'.tr('Cliente').':</b> '.$intervento->anagrafica->ragione_sociale.'</small></p>
        <p><small><b>'.tr('Stato').':</b> '.$intervento->stato->descrizione.'</small></p>
        <p><small><b>'.tr('Data richiesta').':</b> '.dateFormat($intervento->data_richiesta).'</small></p>
        <p><small><b>'.tr('Richiesta').':</b> '.$intervento->richiesta.'</small></p>
    </td>
    <td class="text-center">'.($pricing ? moneyFormat($imponibile, 2) : '-').'</td>
    <td class="text-center">'.($pricing && empty($options['dir']) ? moneyFormat($sconto, 2) : '-').'</td>
    <td class="text-center">'.($pricing ? moneyFormat($totale_imponibile, 2) : '-').'</td>
</tr>';

// Sessioni
$sessioni = $intervento->sessioni;
if (count($sessioni) > 0) {
    echo '
<tr>
    <td style="border-top: 0; border-bottom: 0;"></td>
    <th style="background-color: #eee"><small>'.tr('Sessioni').'</small></th>
    <th class="text-center" style="background-color: #eee"><small>'.tr('Data').'</small></th>
    <th class="text-center" style="background-color: #eee"><small>'.tr('Inizio').'</small></th>
    <th class="text-center" style="background-color: #eee"><small>'.tr('Fine').'</small></th>
</tr>';

    foreach ($sessioni as $sessione) {
        echo '
<tr>
    <td style="border-top: 0; border-bottom: 0;"></td>
    <td><small>'.$sessione->anagrafica->ragione_sociale.' <small>('.$sessione->tipo->descrizione.')</small></td>
    <td class="text-center"><small>'.dateFormat($sessione->orario_inizio).'</small></td>
    <td class="text-center"><small>'.timeFormat($sessione->orario_inizio).'</small></td>
    <td class="text-center"><small>'.timeFormat($sessione->orario_fine).'</small></td>
</tr>';
    }
}

// Righe
$righe = $intervento->getRighe();
if (!$righe->isEmpty()) {
    echo '
<tr>
    <td style="border-top: 0; border-bottom: 0;"></td>
    <th style="background-color: #eee"><small>'.tr('Materiale utilizzato e spese aggiuntive').'</small></th>
    <th class="text-center" style="background-color: #eee"><small>'.tr('Qta').'</small></th>
    <th class="text-center" style="background-color: #eee"><small>'.tr('Prezzo unitario').'</small></th>
    <th class="text-center" style="background-color: #eee"><small>'.tr('Imponibile').'</small></th>
</tr>';

    foreach ($righe as $riga) {
        $prezzo = empty($options['dir']) ? $riga->prezzo_unitario : $riga->costo_unitario;
        $totale = empty($options['dir']) ? $riga->totale_imponibile : $riga->spesa;

        echo '
<tr>
    <td style="border-top: 0; border-bottom: 0;"></td>
    <td><small>'.$riga->descrizione.'</small></td>
    <td class="text-center"><small>'.$riga->qta.' '.$riga->um.'</small></td>
    <td class="text-center"><small>'.($pricing ? moneyFormat($prezzo) : '-').'</small></td>
    <td class="text-center"><small>'.($pricing ? moneyFormat($totale) : '-').'</small></td>
</tr>';
    }
}
