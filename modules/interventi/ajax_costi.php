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

$intervento = Intervento::find($id_record);
$sessioni = $intervento->sessioni;
$righe = $intervento->getRighe();

$show_prezzi = Auth::user()['gruppo'] != 'Tecnici' || (Auth::user()['gruppo'] == 'Tecnici' && setting('Mostra i prezzi al tecnico'));

if ($show_prezzi) {
    $rss = $dbo->fetchArray('SELECT in_statiintervento.is_completato AS flag_completato FROM in_statiintervento INNER JOIN in_interventi ON in_statiintervento.idstatointervento=in_interventi.idstatointervento WHERE in_interventi.id='.prepare($id_record));

    if ($rss[0]['flag_completato']) {
        $readonly = 'readonly';
    } else {
        $readonly = '';
    }

    echo '
<!-- Riepilogo dei costi -->
<table class="table table condensed table-striped table-hover table-bordered">
    <thead>
        <tr>
            <th width="40%"></th>
            <th width="20%" class="text-center">'.tr('Costo', [], ['upper' => true]).' <span class="tip" title="'.tr('Costo interno').'"><i class="fa fa-question-circle-o"></i></span></th>
            <th width="20%" class="text-center">'.tr('Addebito', [], ['upper' => true]).' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
            <th width="20%" class="text-center">'.tr('Tot. Scontato', [], ['upper' => true]).' <span class="tip" title="'.tr('Addebito scontato al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <th>'.tr('Totale manodopera', [], ['upper' => true]).'</th>
            <td class="text-right">'.moneyFormat($sessioni->sum('costo_manodopera'), 2).'</td>
            <td class="text-right">'.moneyFormat($sessioni->sum('prezzo_manodopera'), 2).'</td>
            <td class="text-right">'.moneyFormat($sessioni->sum('prezzo_manodopera_scontato'), 2).'</td>
        </tr>

        <tr>
            <th>'.tr('Totale diritto di chiamata', [], ['upper' => true]).'</th>
            <td class="text-right">'.moneyFormat($sessioni->sum('costo_diritto_chiamata'), 2).'</td>
            <td class="text-right">'.moneyFormat($sessioni->sum('prezzo_diritto_chiamata'), 2).'</td>
            <td class="text-right">'.moneyFormat($sessioni->sum('prezzo_diritto_chiamata'), 2).'</td>
        </tr>

        <tr>
            <th>'.tr('Totale viaggio', [], ['upper' => true]).'</th>
            <td class="text-right">'.moneyFormat($sessioni->sum('costo_viaggio'), 2).'</td>
            <td class="text-right">'.moneyFormat($sessioni->sum('prezzo_viaggio'), 2).'</td>
            <td class="text-right">'.moneyFormat($sessioni->sum('prezzo_viaggio_scontato'), 2).'</td>
        </tr>

        <tr>
            <th>'.tr('Totale righe', [], ['upper' => true]).'</th>
            <td class="text-right">'.moneyFormat($righe->sum('spesa'), 2).'</td>
            <td class="text-right">'.moneyFormat($righe->sum('imponibile'), 2).'</td>
            <td class="text-right">'.moneyFormat($righe->sum('totale_imponibile'), 2).'</td>
        </tr>
    </tbody>';

    // Calcoli
    $imponibile = abs($intervento->imponibile);
    $sconto = $intervento->sconto;
    $totale_imponibile = abs($intervento->totale_imponibile);
    $iva = abs($intervento->iva);
    $totale = abs($intervento->totale);

    echo '
    <tr>
        <td colspan="3" class="text-right">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($imponibile, 2).'
        </td>
    </tr>';

    // SCONTO
    if (!empty($sconto)) {
        echo '
    <tr>
        <td colspan="3" class="text-right">
            <b><span class="tip" title="'.tr('Un importo positivo indica uno sconto, mentre uno negativo indica una maggiorazione').'"> <i class="fa fa-question-circle-o"></i> '.tr('Sconto/maggiorazione', [], ['upper' => true]).':</span></b>
        </td>
        <td class="text-right">
            '.moneyFormat($sconto, 2).'
        </td>
    </tr>';

        // Totale imponibile
        echo '
    <tr>
        <td colspan="3" class="text-right">
            <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($totale_imponibile, 2).'
        </td>
    </tr>';
    }

    // Totale iva
    echo '
    <tr>
        <td colspan="3" class="text-right">
            <b><i class="fa fa-question-circle-o tip" title="'.tr("Il valore dell'IVA totale è esclusivamente indicativo e basato sulle impostazioni dei default del gestionale").'. '.tr("In particolare, l'IVA delle sessioni di lavoro sarà personalizzabile durante la procedura di importazione dell'Attività in Fattura").'."></i> '.tr('IVA', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($iva, 2).'
        </td>
    </tr>';

    // Totale preventivo
    echo '
    <tr>
        <td colspan="3" class="text-right">
            <b>'.tr('Totale', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($totale, 2).'
        </td>
    </tr>';

    echo '
</table>';
}

echo '
<script>$(document).ready(init)</script>';
