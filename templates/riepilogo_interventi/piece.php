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
use Modules\Iva\Aliquota;

$d_qta = (int) setting('Cifre decimali per quantità in stampa');
$d_importi = (int) setting('Cifre decimali per importi in stampa');

$intervento = Intervento::find($record['id']);
$sessioni = $intervento->sessioni;
$iva_predefinita = floatval(Aliquota::find(setting('Iva predefinita'))->percentuale);

$km = $sessioni->sum('km');
$ore = $sessioni->sum('ore');
$imponibile = $tipo == 'interno' ? $intervento->spesa : $intervento->imponibile;
$sconto = $tipo == 'interno' ? 0 : $intervento->sconto;
$totale_imponibile = $tipo == 'interno' ? $intervento->spesa : $intervento->totale_imponibile;
$iva = $tipo == 'interno' ? (($intervento->spesa * $iva_predefinita) / 100) : $intervento->iva;
$totale_ivato = $tipo == 'interno' ? ($intervento->spesa + $iva) : $intervento->totale;

$somma_km[] = $km;
$somma_ore[] = $ore;
$somma_imponibile[] = $imponibile;
$somma_sconto[] = $sconto;
$somma_totale_imponibile[] = $totale_imponibile;
$somma_iva[] = $iva;
$somma_totale_ivato[] = $totale_ivato;

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
        <p><small><b>'.tr('Stato').':</b> '.$intervento->stato->name.'</small></p>
        <p><small><b>'.tr('Data richiesta').':</b> '.dateFormat($intervento->data_richiesta).'</small></p>
        <p><small><b>'.tr('Richiesta').':</b> '.$intervento->richiesta.'</p>';
if ($intervento->descrizione) {
    echo '
        <p><b>'.tr('Descrizione').':</b> '.$intervento->descrizione.'</small></p>';
}

if (setting('Formato ore in stampa') == 'Sessantesimi') {
    $ore = Translator::numberToHours($ore);
} else {
    $ore = Translator::numberToLocale($ore, $d_qta);
}
echo '
    </td>
    <td class="text-center">'.($pricing ? $km : '-').'</td>
    <td class="text-center">'.($pricing ? $ore : '-').'</td>
    <td class="text-center">'.($pricing ? moneyFormat($imponibile, $d_importi) : '-').'</td>
    <td class="text-center">'.($pricing && empty($options['dir']) ? moneyFormat($sconto, $d_importi) : '-').'</td>
    <td class="text-center">'.($pricing ? moneyFormat($totale_imponibile, $d_importi) : '-').'</td>
</tr>';

// Sessioni
if (count($sessioni) > 0) {
    echo '
<tr>
    <td style="border-top: 0; border-bottom: 0;"></td>
    <th style="background-color: #eee" colspan="'.(get('id_print') != 24 ? 3 : 2).'"><small>'.tr('Sessioni').'</small></th>
    <th class="text-center" style="background-color: #eee"><small>'.tr('Data').'</small></th>
    <th class="text-center" style="background-color: #eee"><small>'.tr('Inizio').'</small></th>
    <th class="text-center" style="background-color: #eee"><small>'.tr('Fine').'</small></th>
</tr>';

    foreach ($sessioni as $sessione) {
        echo '
<tr>
    <td style="border-top: 0; border-bottom: 0;"></td>
    <td colspan="'.(get('id_print') != 24 ? 3 : 2).'"><small>'.$sessione->anagrafica->ragione_sociale.' <small>('.$sessione->tipo->name.')</small></td>
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
    <th style="background-color: #eee" colspan="'.(get('id_print') != 24 ? 3 : 2).'"><small>'.tr('Materiale utilizzato e spese aggiuntive').'</small></th>
    <th class="text-center" style="background-color: #eee"><small>'.tr('Qta').'</small></th>
    <th class="text-center" style="background-color: #eee"><small>'.($tipo == 'interno' ? tr('Costo unitario') : tr('Prezzo unitario')).'</small></th>
    <th class="text-center" style="background-color: #eee"><small>'.($tipo == 'interno' ? tr('Costo netto') : tr('Imponibile')).'</small></th>
</tr>';

    foreach ($righe as $riga) {
        $prezzo = $tipo == 'interno' ? $riga->costo_unitario : $riga->prezzo_unitario;
        $totale = $tipo == 'interno' ? $riga->spesa : $riga->totale_imponibile;

        echo '
<tr>
    <td style="border-top: 0; border-bottom: 0;"></td>
    <td colspan="'.(get('id_print') != 24 ? 3 : 2).'"><small>'.$riga->descrizione.'</small></td>
    <td class="text-center"><small>'.$riga->qta.' '.$riga->um.'</small></td>
    <td class="text-center"><small>'.($pricing ? moneyFormat($prezzo, $d_importi) : '-').'</small></td>
    <td class="text-center"><small>'.($pricing ? moneyFormat($totale, $d_importi) : '-').'</small></td>
</tr>';
    }
}
