<?php

include_once __DIR__.'/../../core.php';

use Modules\Interventi\Intervento;

print_r($record['id']);
$intervento = Intervento::find($record['id']);

$imponibile = $intervento->imponibile;
$sconto = $intervento->sconto;
$totale_imponibile = $intervento->totale_imponibile;

$somma_imponibile[] = $imponibile;
$somma_sconto[] = $sconto;
$somma_totale_imponibile[] = $totale_imponibile;

$pricing = isset($pricing) ? $pricing : true;

// Informazioni intervento
echo '
<tr>
    <td colspan="2">
        <p>'.tr('Intervento _NUM_ del _DATE_', [
            '_NUM_' => $intervento->codice,
            '_DATE_' => dateFormat($intervento->inizio),
        ]).'</p>
        <p><small><b>'.tr('Cliente').':</b> '.$intervento->anagrafica->ragione_sociale.'</small></p>
        <p><small><b>'.tr('Stato').':</b> '.$intervento->stato->descrizione.'</small></p>
    </td>
    <td class="text-center">'.($pricing ? moneyFormat($imponibile, 2) : '-').'</td>
    <td class="text-center">'.($pricing ? moneyFormat($sconto, 2) : '-').'</td>
    <td class="text-center">'.($pricing ? moneyFormat($totale_imponibile, 2) : '-').'</td>
</tr>';

// Sessioni
$sessioni = $intervento->sessioni;
if (!empty($sessioni)) {
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
        echo '
<tr>
    <td style="border-top: 0; border-bottom: 0;"></td>
    <td><small>'.$riga->descrizione.'</small></td>
    <td class="text-center"><small>'.$riga->qta.' '.$riga->um.'</small></td>
    <td class="text-center"><small>'.($pricing ? moneyFormat($riga->prezzo_unitario_vendita) : '-').'</small></td>
    <td class="text-center"><small>'.($pricing ? moneyFormat($riga->totale_imponibile) : '-').'</small></td>
</tr>';
    }
}
