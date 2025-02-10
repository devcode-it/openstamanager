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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Anagrafiche\Anagrafica;
use Modules\Banche\Banca;
use Modules\Pagamenti\Pagamento;

include_once __DIR__.'/../../core.php';

$anagrafica = Anagrafica::find($documento['idanagrafica']);
$anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));
$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

$pagamento = Pagamento::find($documento['idpagamento']);

// Banca dell'Azienda corrente impostata come predefinita per il Cliente
$banca_azienda = Banca::where('id_anagrafica', '=', $anagrafica_azienda->id)
    ->where('id_pianodeiconti3', '=', $pagamento['idconto_vendite'] ?: 0);
try {
    $banca = (clone $banca_azienda)
        ->findOrFail($anagrafica->idbanca_vendite);
} catch (ModelNotFoundException) {
    // Ricerca prima banca dell'Azienda con Conto corrispondente
    $banca = (clone $banca_azienda)
        ->orderBy('predefined', 'DESC')
        ->first();
}

// Ri.Ba: Banca predefinita *del Cliente* piuttosto che dell'Azienda
if ($pagamento && $pagamento->isRiBa()) {
    $banca = Banca::where('id_anagrafica', $anagrafica->id)
        ->where('predefined', 1)
        ->first();
}

// Righe documento
$righe = $documento->getRighe();

$has_image = $righe->search(fn ($item) => !empty($item->articolo->immagine)) !== false && $options['images'] == true;

$columns = $options['no-iva'] ? 5 : 6;
$columns = $options['pricing'] ? $columns : 3;

if ($has_image) {
    ++$columns;
}

// Creazione righe fantasma
$autofill = new Util\Autofill($columns);
$rows_per_page = 22;
$rows_first_page = 36;
$autofill->setRows($rows_per_page, 0, $rows_first_page);

// Conto le righe da diminuire
$c = 0;
$destinazione ? $c += 2 : null;

// Diminuisco le righe disponibili per pagina
$autofill->setRows($rows_per_page - $c, 0, $rows_first_page - $c);

// Descrizione
if (!empty($documento['descrizione'])) {
    echo '
<p>'.nl2br((string) $documento['descrizione']).'</p>
<br>';
    $autofill->count($documento['descrizione']);
}

// Intestazione tabella per righe
echo "
<table class='table table-striped border-bottom' id='contents'>
    <thead>
        <tr>
            <th class='text-center' width='35' >#</th>";

if ($has_image) {
    echo "
            <th class='text-center' width='95' >Foto</th>";
}

echo "
            <th class='text-center' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Q.tà', [], ['upper' => true]).'</th>';

if ($options['pricing']) {
    echo "
            <th class='text-center' style='width:15%'>".tr('Prezzo unitario', [], ['upper' => true]).'</th>';
    if (!$options['no-iva']) {
        echo "
            <th class='text-center' style='width:10%'>".tr('IVA', [], ['upper' => true]).' (%)</th>';
    }
    echo "
            <th class='text-center' style='width:15%'>".($options['hide-total'] ? tr('Importo ivato', [], ['upper' => true]) : tr('Importo', [], ['upper' => true])).'</th>';
}

echo '
        </tr>
    </thead>

    <tbody>';

$num = 0;
$has_gruppo = false;
$subtotale_gruppo = 0;
$iva_gruppo = 0;
foreach ($righe as $key => $riga) {
    ++$num;
    $r = $riga->toArray();

    // Gestione gruppo
    $style_titolo = '';
    $colspan_titolo = '';
    if ($riga->is_titolo) {
        $subtotale_gruppo = 0;
        $iva_gruppo = 0;
        $has_gruppo = true;
        $colspan_titolo = $options['pricing'] ? 5 : 2;
        $descrizione = '<b>'.nl2br((string) $r['descrizione']).'</b>';
    } else {
        $descrizione = nl2br((string) $r['descrizione']);
    }
    $subtotale_gruppo += $riga->totale_imponibile;
    $iva_gruppo += $riga->iva;

    $autofill->count($descrizione);

    echo '
        <tr>
            <td class="text-center" nowrap="nowrap" style="vertical-align: middle" width="25">
                '.$num.'
            </td>';

    if ($has_image) {
        if ($riga->isArticolo() && !empty($riga->articolo->image)) {
            echo '
            <td align="center">
                <img src="'.$riga->articolo->image.'" style="max-height: 60px; max-width:80px">
            </td>';

            $autofill->set(5);
        } else {
            echo '
            <td></td>';
        }
    }

    echo '
            <td style="vertical-align: middle" colspan="'.$colspan_titolo.'">
                '.$descrizione;

    if ($riga->isArticolo()) {
        if ($options['hide-item-number']) {
            $text = '';
        } else {
            // Codice articolo
            $text = tr('COD. _COD_', [
                '_COD_' => $riga->codice,
            ]);
        }

        echo '
                <br><small>'.$text.'</small>';

        $autofill->count($text, true);
    }

    echo '
            </td>';

    if (!$riga->is_titolo) {
        if (!$riga->isDescrizione()) {
            echo '
                <td class="text-center" style="vertical-align: middle" >
                    '.Translator::numberToLocale(abs($riga->qta), $d_qta).' '.$r['um'].'
                </td>';

            if ($options['pricing']) {
                // Prezzo unitario
                echo '
                <td class="text-right" style="vertical-align: middle">
                    '.moneyFormat($prezzi_ivati ? $riga->prezzo_unitario_ivato : $riga->prezzo_unitario, $d_importi);

                if ($riga->sconto != 0) {
                    $text = discountInfo($riga, false);

                    echo '
                    <br><small class="text-muted">'.$text.'</small>';
                }

                echo '
                </td>';
                if (!$options['no-iva']) {
                    // Iva
                    echo '
                    <td class="text-center" style="vertical-align: middle">
                        '.Translator::numberToLocale($riga->aliquota->percentuale, 0).'
                    </td>';
                }
                // Imponibile
                echo '
                <td class="text-right" style="vertical-align: middle" >
                    '.(($options['hide-total'] || $prezzi_ivati) ? moneyFormat($riga->totale, $d_importi) : moneyFormat($riga->totale_imponibile, $d_importi)).'
                </td>';
            }
        } else {
            echo '
                <td></td>';

            if ($options['pricing']) {
                echo '
                <td></td>
                <td></td>';
                if (!$options['no-iva']) {
                    echo '
                    <td></td>';
                }
            }
        }
    }

    echo '
        </tr>';

    $autofill->next();

    $next = $righe->flatten()[$num];

    if ($has_gruppo && ($next->is_titolo || $next == null) && ($options['pricing'] || $options['show-only-total'])) {
        echo '
        <tr>
            <td colspan="'.($options['show-only-total'] ? (($has_image) ? 3 : 2) : (($has_image) ? 6 : 5)).'" class="text-right">
                <b>'.tr('Subtotale', [], ['upper' => true]).':</b>
            </td>
            <td colspan="1" class="text-right">
                '.moneyFormat($subtotale_gruppo, $d_totali).'
            </td>
        </tr>';
        if (!$options['no-iva']) {
            echo '
        <tr>
            <td colspan="'.($options['show-only-total'] ? (($has_image) ? 3 : 2) : (($has_image) ? 6 : 5)).'" class="text-right">
                <b>'.tr('Iva', [], ['upper' => true]).':</b>
            </td>
            <td colspan="1" class="text-right">
                '.moneyFormat($iva_gruppo, $d_totali).'
            </td>
        </tr>
        <tr>
            <td colspan="'.($options['show-only-total'] ? (($has_image) ? 3 : 2) : (($has_image) ? 6 : 5)).'" class="text-right">
                <b>'.tr('Subtotale ivato', [], ['upper' => true]).':</b>
            </td>
            <td colspan="1" class="text-right">
                <b>'.moneyFormat($subtotale_gruppo + $iva_gruppo, $d_totali).'</b>
            </td>
        </tr>';
        }
        $autofill->set(3);
        $autofill->next();
    }
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
if (($options['pricing'] && !isset($options['hide-total'])) || $options['show-only-total']) {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="'.($options['show-only-total'] ? (($has_image) ? 3 : 2) : (($has_image) ? 6 : 5)).'" class="text-right text-muted">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="1" class="text-right">
            <b>'.moneyFormat($show_sconto ? $imponibile : $totale_imponibile, $d_totali).'</b>
        </th>
    </tr>';

    // Eventuale sconto incondizionato
    if ($show_sconto) {
        echo '
    <tr>
        <td colspan="'.($options['show-only-total'] ? (($has_image) ? 3 : 2) : (($has_image) ? 6 : 5)).'" class="text-right text-muted">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>

        <th colspan="1" class="text-right">
            <b>'.moneyFormat($sconto, $d_totali).'</b>
        </th>
    </tr>';

        // Totale imponibile
        echo '
    <tr>
        <td colspan="'.($options['show-only-total'] ? (($has_image) ? 3 : 2) : (($has_image) ? 6 : 5)).'" class="text-right text-muted">
            <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="1" class="text-right">
            <b>'.moneyFormat($totale_imponibile, $d_totali).'</b>
        </th>
    </tr>';
    }

    // IVA
    if (!$options['no-iva']) {
        echo '
        <tr>
            <td colspan="'.($options['show-only-total'] ? (($has_image) ? 3 : 2) : (($has_image) ? 6 : 5)).'" class="text-right text-muted">
                <b>'.tr('Totale IVA', [], ['upper' => true]).':</b>
            </td>

            <th colspan="1" class="text-right">
                <b>'.moneyFormat($totale_iva, $d_totali).'</b>
            </th>
        </tr>';

        // TOTALE
        echo '
        <tr>
            <td colspan="'.($options['show-only-total'] ? (($has_image) ? 3 : 2) : (($has_image) ? 6 : 5)).'" class="text-right text-muted">
                <b>'.tr('Totale documento', [], ['upper' => true]).':</b>
            </td>
            <th colspan="1" class="text-right">
                <b>'.moneyFormat($totale, $d_totali).'</b>
            </th>
        </tr>';

        if ($sconto_finale) {
            // SCONTO IN FATTURA
            echo '
            <tr>
                <td colspan="'.($options['show-only-total'] ? (($has_image) ? 3 : 2) : (($has_image) ? 6 : 5)).'" class="text-right border-top">
                    <b>'.tr('Sconto in fattura', [], ['upper' => true]).':</b>
                </td>
                <th colspan="1" class="text-right">
                    <b>'.moneyFormat($sconto_finale, $d_totali).'</b>
                </th>
            </tr>';

            // NETTO A PAGARE
            echo '
            <tr>
                <td colspan="'.($options['show-only-total'] ? (($has_image) ? 3 : 2) : (($has_image) ? 6 : 5)).'" class="text-right border-top">
                    <b>'.tr('Netto a pagare', [], ['upper' => true]).':</b>
                </td>
                <th colspan="1" class="text-right">
                    <b>'.moneyFormat($netto_a_pagare, $d_totali).'</b>
                </th>
            </tr>';
        }
    }
}

echo '
</table>';

if ($options['no-iva']) {
    echo '
    <p colspan="3" class="text-right text-muted">
        <small>Importo IVA esclusa</small>
    </p>
';
}

// CONDIZIONI GENERALI DI FORNITURA

echo '

<table class="table table-striped">
    <tr>
        <th colspan="6" class="text-left text-muted">
            '.tr('Condizioni generali di fornitura', [], ['upper' => true]).'
        </th>
    </tr>

    <tr>
        <td class="text-muted small-bold text-muted border-bottom" style="width:25%">
            '.tr('Pagamento', [], ['upper' => true]).'
        </td>

        <td class="border-bottom">
            '.($pagamento ? $pagamento->getTranslation('title') : '').'
        </td>
    </tr>

    <tr>
        <td class="text-muted border-bottom small-bold text-muted">
            '.tr('Validità offerta', [], ['upper' => true]).'
        </td>

        <td class="border-bottom">';

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
        <td class="text-muted border-bottom small-bold text-muted">
            '.tr('Tempi consegna', [], ['upper' => true]).'
        </td>

        <td class="border-bottom">
            '.$documento['tempi_consegna'].'
        </td>
    </tr>

    <tr>
        <td class="text-muted border-bottom small-bold text-muted">
            '.tr('Esclusioni', [], ['upper' => true]).'
        </td>

        <td class="border-bottom">
            '.nl2br((string) $documento['esclusioni']).'
        </td>
    </tr>

    <tr>
        <td class="text-muted border-bottom small-bold text-muted">
            '.tr('Garanzia', [], ['upper' => true]).'
        </td>

        <td class="border-bottom">
            '.nl2br((string) $documento['garanzia']).'
        </td>
    </tr>
    <tr>
        <td></td>
    </tr>
    ';
if ($banca) {
    $autofill->count($banca);
    echo '
    <tr>
        <td class="text-muted border-bottom small-bold text-muted"></td>
        <td class="border-bottom">
            <p><small>'.$banca['nome'].'</small></p>
        </td>
    </tr>
    <tr>
        <td class="text-muted border-bottom small-bold text-muted">
            '.tr('IBAN', [], ['upper' => true]).'
        </td>
        <td class="border-bottom">
            <p>'.$banca['iban'].'</p>
        </td>
    </tr>
    <tr>
        <td class="text-muted border-bottom small-bold text-muted">
            '.tr('BIC', [], ['upper' => true]).'
        </td>
        <td class="border-bottom">
            <p>'.$banca['bic'].'</p>
        </td>
    </tr>';
}
echo '
</table>';

// Conclusione
echo '
<p class="text-center">'.tr("In attesa di un Vostro Cortese riscontro, colgo l'occasione per porgere Cordiali Saluti").'</p>';

if (!empty($documento->condizioni_fornitura)) {
    echo '<pagebreak>'.$documento->condizioni_fornitura;
}
