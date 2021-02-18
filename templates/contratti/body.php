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

use Carbon\CarbonInterval;

include_once __DIR__.'/../../core.php';

// Creazione righe fantasma
$autofill = new \Util\Autofill($options['pricing'] ? 4 : 2);
$autofill->setRows(20, 10);

echo '
<div class="row">
    <div class="col-xs-6">
        <div class="text-center" style="height:5mm;">
            <b>'.tr('Contratto num. _NUM_ del _DATE_', [
                '_NUM_' => $documento['numero'],
                '_DATE_' => Translator::dateToLocale($documento['data_bozza']),
            ], ['upper' => true]).'</b>
        </div>';

// Elenco impianti
$impianti = $dbo->fetchArray('SELECT nome, matricola FROM my_impianti WHERE id IN (SELECT my_impianti_contratti.idimpianto FROM my_impianti_contratti WHERE idcontratto = '.prepare($documento['id']).')');
if (!empty($impianti)) {
    $list = [];
    foreach ($impianti as $impianto) {
        $list[] = $impianto['nome']." <span style='color:#777;'>(".$impianto['matricola'].')</span>';
    }

    echo '
        <br>
        <p class="small-bold">'.tr('Impianti', [], ['upper' => true]).'</p>
        <p><small>'.implode(', ', $list).'</small></p>';
}

echo '
    </div>

	<div class="col-xs-6" style="margin-left: 10px">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td colspan=2 class="border-full" style="height:16mm;">
                    <p class="small-bold">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
                    <p>$c_indirizzo$ $c_citta_full$</p>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.tr('Partita IVA', [], ['upper' => true]).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_piva$</small>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.tr('Codice fiscale', [], ['upper' => true]).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_codicefiscale$</small>
                </td>
            </tr>
        </table>
    </div>
</div>';

// Descrizione
if (!empty($documento['descrizione'])) {
    echo '
<p>'.nl2br($documento['descrizione']).'</p>
<br>';
}

// Intestazione tabella per righe
echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Q.tà', [], ['upper' => true]).'</th>';

if ($options['pricing']) {
    echo "
            <th class='text-center' style='width:20%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:20%'>".tr('Imponibile', [], ['upper' => true]).'</th>';
}

echo '
        </tr>
    </thead>

    <tbody>';

// Righe documento
$righe = $documento->getRighe();
foreach ($righe as $riga) {
    $r = $riga->toArray();

    $autofill->count($r['descrizione']);

    echo '
        <tr>
            <td>
                '.nl2br($r['descrizione']);

    if ($riga->isArticolo()) {
        // Codice articolo
        $text = tr('COD. _COD_', [
            '_COD_' => $riga->codice,
        ]);
        echo '
                <br><small>'.$text.'</small>';

        $autofill->count($text, true);
    }

    echo '
            </td>';

    if (!$riga->isDescrizione()) {
        echo '
            <td class="text-center">
                '.Translator::numberToLocale(abs($riga->qta), 'qta').' '.$r['um'].'
            </td>';

        if ($options['pricing']) {
            // Prezzo unitario
            echo '
            <td class="text-right">
				'.moneyFormat($riga->prezzo_unitario);

            if ($riga->sconto > 0) {
                $text = discountInfo($riga, false);

                echo '
                <br><small class="text-muted">'.$text.'</small>';

                $autofill->count($text, true);
            }

            echo '
            </td>';

            // Imponibile
            echo '
            <td class="text-right">
				'.moneyFormat($riga->totale_imponibile).'
            </td>';
        }
    } else {
        echo '
            <td></td>';

        if ($options['pricing']) {
            echo '
            <td></td>
            <td></td>';
        }
    }

    echo '
        </tr>';

    $autofill->next();
}

echo '
        |autofill|
    </tbody>';

// Calcoli
$imponibile = $documento->imponibile;
$sconto = $documento->sconto;
$totale_imponibile = $documento->totale_imponibile;
$totale_iva = $documento->iva;
$totale = $documento->totale;

$show_sconto = $sconto > 0;

// TOTALE COSTI FINALI
if ($options['pricing']) {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($show_sconto ? $imponibile : $totale_imponibile, 2).'</b>
        </th>
    </tr>';

    // Eventuale sconto incondizionato
    if ($show_sconto) {
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($sconto, 2).'</b>
        </th>
    </tr>';

        // Totale imponibile
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($totale_imponibile, 2).'</b>
        </th>
    </tr>';
    }

    // IVA
    echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Totale IVA', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($totale_iva, 2).'</b>
        </th>
    </tr>';

    // TOTALE
    echo '
    <tr>
    	<td colspan="3" class="text-right border-top">
            <b>'.tr('Totale documento', [], ['upper' => true]).':</b>
    	</td>
    	<th colspan="2" class="text-right">
    		<b>'.moneyFormat($totale, 2).'</b>
    	</th>
    </tr>';
}
echo '
</table>';

// CONDIZIONI GENERALI DI FORNITURA
$pagamento = $dbo->fetchOne('SELECT * FROM co_pagamenti WHERE id = '.$documento['idpagamento']);

echo '
<table class="table table-bordered">
    <tr>
        <th colspan="2" class="text-center" style="font-size:13pt;">
            '.tr('Condizioni generali di fornitura', [], ['upper' => true]).'
        </th>
    </tr>

    <tr>
        <th style="width:25%">
            '.tr('Pagamento', [], ['upper' => true]).'
        </th>

        <td>
            '.$pagamento['descrizione'].'
        </td>
    </tr>

    <tr>
        <th>
            '.tr('Validità offerta', [], ['upper' => true]).'
        </th>

        <td>';

        if (!empty($documento->validita) && !empty($documento->tipo_validita)) {
            $intervallo = CarbonInterval::make($documento->validita.' '.$documento->tipo_validita);

            echo $intervallo->forHumans();
        } elseif (!empty($documento->validita)) {
            echo tr('_TOT_ giorni', [
                '_TOT_' => $documento->validita,
            ]);
        } else {
            echo '-';
        }

        echo '
        </td>
    </tr>

    <tr>
        <th>
            '.tr('Validità contratto', [], ['upper' => true]).'
        </th>

        <td>';

        if (!empty($documento['data_accettazione']) && !empty($documento['data_conclusione'])) {
            echo '
            '.tr('dal _START_ al _END_', [
                '_START_' => Translator::dateToLocale($documento['data_accettazione']),
                '_END_' => Translator::dateToLocale($documento['data_conclusione']),
            ]);
        } else {
            echo '-';
        }

        echo '
        </td>
    </tr>

    <tr>
        <th>
            '.tr('Esclusioni', [], ['upper' => true]).'
        </th>

        <td>
            '.nl2br($documento['esclusioni']).'
        </td>
    </tr>
</table>';

// Conclusione
if (empty($documento->stato->fatturabile)) {
    echo '
<p class="text-center"><b>'.tr('Il tutto S.E. & O.').'</b></p>
<p class="text-center">'.tr("In attesa di un Vostro Cortese riscontro, colgo l'occasione per porgere Cordiali Saluti").'</p>';
}
