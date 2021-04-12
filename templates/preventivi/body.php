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
use Modules\Anagrafiche\Anagrafica;

include_once __DIR__.'/../../core.php';

$has_images = null;

// Righe documento
$righe = $documento->getRighe();

foreach ($righe as $riga) {
    if ($riga->articolo->immagine) {
        $has_images = 1;
    }
}

$anagrafica = Anagrafica::find($documento['idanagrafica']);
$pagamento = $dbo->fetchOne('SELECT * FROM co_pagamenti WHERE id = '.prepare($documento['idpagamento']));

//Verifico se c'è una banca predefinita per il mio cliente
if (!empty($anagrafica->idbanca_vendite)) {
    $banca = $dbo->fetchOne('SELECT co_banche.nome, co_banche.iban, co_banche.bic FROM co_banche INNER JOIN co_pianodeiconti3 ON co_banche.id_pianodeiconti3 = co_pianodeiconti3.id WHERE co_pianodeiconti3.id = '.prepare($pagamento['idconto_vendite']).' AND co_banche.id ='.prepare($anagrafica->idbanca_vendite));
} elseif (!empty($pagamento['idconto_vendite'])) {
    //Altrimenti prendo quella associata la metodo di pagamento selezionato
    $banca = $dbo->fetchOne('SELECT co_banche.nome, co_banche.iban, co_banche.bic FROM co_banche INNER JOIN co_pianodeiconti3 ON co_banche.id_pianodeiconti3 = co_pianodeiconti3.id WHERE co_pianodeiconti3.id = '.prepare($pagamento['idconto_vendite']).' AND co_banche.deleted_at IS NULL');
}

// Creazione righe fantasma
$autofill = new \Util\Autofill($options['pricing'] ? (($has_images) ? 7 : 6) : 3);
$autofill->setRows(20, 10);

echo '
<div class="row">
    <div class="col-xs-6">
        <div class="text-center" style="height:5mm;">
            <b>'.tr('Preventivo num. _NUM_ del _DATE_', [
                '_NUM_' => $documento['numero'].(count($documento->revisioni) > 1 ? ' '.tr('rev.').' '.$documento->numero_revision : ''),
                '_DATE_' => Translator::dateToLocale($documento['data_bozza']),
            ], ['upper' => true]).'</b>
        </div>

        <table class="table">
            <tr>
                <td colspan="2" style="height:10mm;padding-top:2mm;">
                    <p class="small-bold">'.tr('Pagamento', [], ['upper' => true]).'</p>
                    <p>'.$pagamento['descrizione'].'</p>
                </td>
                <td colspan="2" style="height:10mm;padding-top:2mm;">
                    <p class="small-bold">'.tr('Banca di appoggio', [], ['upper' => true]).'</p>
                    <p><small>'.$banca['nome'].'</small></p>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="height:10mm;padding-top:2mm;white-space: nowrap;">
                    <p class="small-bold">'.tr('IBAN').'</p>
                    <p>'.$banca['iban'].'</p>
                </td>
                <td colspan="2" style="height:10mm;padding-top:2mm;">
                    <p class="small-bold">'.tr('BIC').'</p>
                    <p>'.$banca['bic'].'</p>
                </td>
            </tr>
        </table>
    </div>

	<div class="col-xs-6" style="margin-left: 10px">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td colspan=2 class="border-full" style="height:16mm;">
                    <p class="small-bold">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
                    <p>$c_indirizzo$</p>
                    <p>$c_citta_full$</p>
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
            <th class='text-center'>#</th>";

if ($has_images) {
    echo "
            <th class='text-center' width='95' >Foto</th>";
}

echo "
            <th class='text-center' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Q.tà', [], ['upper' => true]).'</th>';

if ($options['pricing']) {
    echo "
            <th class='text-center' style='width:15%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Importo', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('IVA', [], ['upper' => true]).' (%)</th>';
}

echo '
        </tr>
    </thead>

    <tbody>';

$num = 0;
foreach ($righe as $riga) {
    ++$num;
    $r = $riga->toArray();

    $autofill->count($r['descrizione']);

    echo '
        <tr>
            <td class="text-center" style="vertical-align: middle" width="25">
                '.$num.'
            </td>';

    if ($has_images) {
        echo '<td class=\"text-center\" style=\"vertical-align: middle\" >';
        if (!empty($riga->articolo->immagine)) {
            echo '<img src="files/articoli/'.$riga->articolo->immagine.'" width="78">';
        }

        echo '</td>';
    }

    echo '
            <td style="vertical-align: middle">
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
            <td class="text-center" style="vertical-align: middle" >
                '.Translator::numberToLocale(abs($riga->qta), 'qta').' '.$r['um'].'
            </td>';

        if ($options['pricing']) {
            // Prezzo unitario
            echo '
            <td class="text-right" style="vertical-align: middle">
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
            <td class="text-right" style="vertical-align: middle" >
				'.moneyFormat($riga->totale_imponibile).'
            </td>';

            // Iva
            echo '
            <td class="text-center" style="vertical-align: middle">
                '.Translator::numberToLocale($riga->aliquota->percentuale, 0).'
            </td>';
        }
    } else {
        echo '
            <td></td>';

        if ($options['pricing']) {
            echo '
            <td></td>
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
$sconto_finale = $documento->getScontoFinale();
$netto_a_pagare = $documento->netto;

$show_sconto = $sconto > 0;

// TOTALE COSTI FINALI
if (($options['pricing'] && !isset($options['hide_total'])) || $options['show_only_total']) {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="'.($options['show_only_total'] ? 2 : 4).'" class="text-right border-top">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="'.($options['show_only_total'] ? (($has_images) ? 2 : 1) : (($has_images) ? 3 : 2)).'" class="text-right">
            <b>'.moneyFormat($show_sconto ? $imponibile : $totale_imponibile, 2).'</b>
        </th>
    </tr>';

    // Eventuale sconto incondizionato
    if ($show_sconto) {
        echo '
    <tr>
        <td colspan="'.($options['show_only_total'] ? 2 : 4).'" class="text-right border-top">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>

        <th colspan="'.($options['show_only_total'] ? (($has_images) ? 2 : 1) : (($has_images) ? 3 : 2)).'" class="text-right">
            <b>'.moneyFormat($sconto, 2).'</b>
        </th>
    </tr>';

        // Totale imponibile
        echo '
    <tr>
        <td colspan="'.($options['show_only_total'] ? 2 : 4).'" class="text-right border-top">
            <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="'.($options['show_only_total'] ? (($has_images) ? 2 : 1) : (($has_images) ? 3 : 2)).'" class="text-right">
            <b>'.moneyFormat($totale_imponibile, 2).'</b>
        </th>
    </tr>';
    }

    // IVA
    echo '
    <tr>
        <td colspan="'.($options['show_only_total'] ? 2 : 4).'" class="text-right border-top">
            <b>'.tr('Totale IVA', [], ['upper' => true]).':</b>
        </td>

        <th colspan="'.($options['show_only_total'] ? (($has_images) ? 2 : 1) : (($has_images) ? 3 : 2)).'" class="text-right">
            <b>'.moneyFormat($totale_iva, 2).'</b>
        </th>
    </tr>';

    // TOTALE
    echo '
    <tr>
    	<td colspan="'.($options['show_only_total'] ? 2 : 4).'" class="text-right border-top">
            <b>'.tr('Totale documento', [], ['upper' => true]).':</b>
    	</td>
    	<th colspan="'.($options['show_only_total'] ? (($has_images) ? 2 : 1) : (($has_images) ? 3 : 2)).'" class="text-right">
    		<b>'.moneyFormat($totale, 2).'</b>
    	</th>
    </tr>';

    if ($sconto_finale) {
        // SCONTO FINALE
        echo '
        <tr>
            <td colspan="'.($options['show_only_total'] ? 2 : 4).'" class="text-right border-top">
                <b>'.tr('Sconto finale', [], ['upper' => true]).':</b>
            </td>
            <th colspan="'.($options['show_only_total'] ? (($has_images) ? 2 : 1) : (($has_images) ? 3 : 2)).'" class="text-right">
                <b>'.moneyFormat($sconto_finale, 2).'</b>
            </th>
        </tr>';

        // NETTO A PAGARE
        echo '
        <tr>
            <td colspan="'.($options['show_only_total'] ? 2 : 4).'" class="text-right border-top">
                <b>'.tr('Netto a pagare', [], ['upper' => true]).':</b>
            </td>
            <th colspan="'.($options['show_only_total'] ? (($has_images) ? 2 : 1) : (($has_images) ? 3 : 2)).'" class="text-right">
                <b>'.moneyFormat($netto_a_pagare, 2).'</b>
            </th>
        </tr>';
    }
}

echo '
</table>';

// CONDIZIONI GENERALI DI FORNITURA

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
            '.tr('Tempi consegna', [], ['upper' => true]).'
        </th>

        <td>
            '.$documento['tempi_consegna'].'
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

    <tr>
        <th>
            '.tr('Garanzia', [], ['upper' => true]).'
        </th>

        <td>
            '.nl2br($documento['garanzia']).'
        </td>
    </tr>
</table>';

// Conclusione
echo '
<p class="text-center">'.tr("In attesa di un Vostro Cortese riscontro, colgo l'occasione per porgere Cordiali Saluti").'</p>';

if (!empty($documento->condizioni_fornitura)) {
    echo '<pagebreak>'.$documento->condizioni_fornitura;
}
