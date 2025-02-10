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
use Modules\Pagamenti\Pagamento;

include_once __DIR__.'/../../core.php';

$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

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
$rows_per_page = 23;
$rows_first_page = 36;
$autofill->setRows($rows_per_page, 0, $rows_first_page);

// Conteggio righe intestazione
$c = 0;
($f_sitoweb || $f_pec) ? ++$c : null;
$destinazione ? $c += 2 : null;

// Diminuisco le righe disponibili per pagina
$autofill->setRows($rows_per_page - $c, 0, $rows_first_page - $c);

// Elenco impianti
$impianti = $dbo->fetchArray('SELECT nome, matricola FROM my_impianti WHERE id IN (SELECT my_impianti_contratti.idimpianto FROM my_impianti_contratti WHERE idcontratto = '.prepare($documento['id']).')');

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

if (!setting('Visualizza riferimento su ogni riga in stampa')) {
    $riferimenti = [];
    $id_rif = [];

    foreach ($righe as $riga) {
        $riferimento = ($riga->getOriginalComponent() ? $riga->getOriginalComponent()->getDocument()->getReference() : null);
        if (!empty($riferimento)) {
            if (!array_key_exists($riferimento, $riferimenti)) {
                $riferimenti[$riferimento] = [];
            }

            if (!in_array($riga->id, $riferimenti[$riferimento])) {
                $id_rif[] = $riga->id;
                $riferimenti[$riferimento][] = $riga->id;
            }
        }
    }
}
$num = 0;
foreach ($righe as $riga) {
    ++$num;
    $r = $riga->toArray();

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
        <td>';

    $text = '';

    foreach ($riferimenti as $key => $riferimento) {
        if (in_array($riga->id, $riferimento)) {
            if ($riga->id === $riferimento[0]) {
                $riga_ordine = $database->fetchOne('SELECT numero_cliente, data_cliente FROM or_ordini WHERE id = '.prepare($riga->idordine));
                if (!empty($riga_ordine['numero_cliente']) && !empty($riga_ordine['data_cliente'])) {
                    $text = $text.'<b>Ordine n. '.$riga_ordine['numero_cliente'].' del '.Translator::dateToLocale($riga_ordine['data_cliente']).'</b><br>';
                }

                $text = '<b>'.$key.'</b><br>';

                if ($options['pricing']) {
                    $text = $text.'<td></td><td></td>';
                }
                $text = $text.'</td><td></td></tr><tr><td>';

                echo nl2br($text);
                $autofill->count($text);
            }
        }
        $r['descrizione'] = str_replace('Rif. '.strtolower((string) $key), '', $r['descrizione']);
    }

    $source_type = $riga::class;
    $autofill->count($r['descrizione']);

    if (!setting('Visualizza riferimento su ogni riga in stampa')) {
        echo $r['descrizione'];
    } else {
        echo nl2br((string) $r['descrizione']);
    }

    if ($riga->isArticolo()) {
        echo nl2br('<br><small>'.$riga->codice.'</small>');
        $autofill->count($riga->codice, true);
    }

    if ($riga->isArticolo()) {
        // Seriali
        $seriali = $riga->serials;
        if (!empty($seriali)) {
            $text = tr('SN').': '.implode(', ', $seriali);
            echo '
                    <small>'.$text.'</small>';

            $autofill->count($text, true);
        }
    }

    echo '
        </td>';

    if (!$riga->isDescrizione()) {
        echo '
            <td class="text-center">
                '.Translator::numberToLocale(abs($riga->qta), $d_qta).' '.$r['um'].'
            </td>';

        if ($options['pricing']) {
            // Prezzo unitario
            echo '
            <td class="text-right">
				'.moneyFormat($prezzi_ivati ? $riga->prezzo_unitario_ivato : $riga->prezzo_unitario, $d_importi);

            if ($riga->sconto > 0) {
                $text = discountInfo($riga, false);

                echo '
                <br><small class="text-muted">'.$text.'</small>';
            }

            echo '
            </td>';

            // IVA
            echo '
            <td class="text-right">
                '.Translator::numberToLocale($riga->aliquota->percentuale, 0).'
            </td>';

            // Imponibile
            echo '
            <td class="text-right">
				'.moneyFormat($riga->subtotale + $riga->iva, $d_importi).'
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
$sconto_finale = $documento->getScontoFinale();
$netto_a_pagare = $documento->netto;

$show_sconto = $sconto > 0;

// TOTALE COSTI FINALI
if (($options['pricing'] && !isset($options['hide-total'])) || $options['show-only-total']) {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="'.($options['show-only-total'] ? (($has_image) ? 3 : 2) : 5).'" class="text-right text-muted">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="'.($options['show-only-total'] ? (($has_image) ? ($options['no-iva'] ? 1 : 2) : 1) : (($has_image) ? 3 : 2)).'" class="text-right">
            <b>'.moneyFormat($show_sconto ? $imponibile : $totale_imponibile, $d_totali).'</b>
        </th>
    </tr>';

    // Eventuale sconto incondizionato
    if ($show_sconto) {
        echo '
    <tr>
        <td colspan="'.($options['show-only-total'] ? 2 : 5).'" class="text-right text-muted">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>

        <th colspan="'.($options['show-only-total'] ? (($has_image) ? 2 : 1) : (($has_image) ? 3 : 2)).'" class="text-right">
            <b>'.moneyFormat($sconto, $d_totali).'</b>
        </th>
    </tr>';

        // Totale imponibile
        echo '
    <tr>
        <td colspan="'.($options['show-only-total'] ? 2 : 5).'" class="text-right text-muted">
            <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="'.($options['show-only-total'] ? (($has_image) ? 2 : 1) : (($has_image) ? 3 : 2)).'" class="text-right">
            <b>'.moneyFormat($totale_imponibile, $d_totali).'</b>
        </th>
    </tr>';
    }
    if (!$options['no-iva']) {
        // IVA
        echo '
        <tr>
            <td colspan="'.($options['show-only-total'] ? 2 : 5).'" class="text-right text-muted">
                <b>'.tr('Totale IVA', [], ['upper' => true]).':</b>
            </td>

            <th colspan="'.($options['show-only-total'] ? (($has_image) ? 2 : 1) : (($has_image) ? 3 : 2)).'" class="text-right">
                <b>'.moneyFormat($totale_iva, $d_totali).'</b>
            </th>
        </tr>';

        // TOTALE
        echo '
        <tr>
            <td colspan="'.($options['show-only-total'] ? 2 : 5).'" class="text-right text-muted">
                <b>'.tr('Totale documento', [], ['upper' => true]).':</b>
            </td>

            <th colspan="2" class="text-right">
                <b>'.moneyFormat($totale, $d_totali).'</b>
            </th>
        </tr>';

        if ($sconto_finale) {
            // SCONTO IN FATTURA
            echo '
            <tr>
                 <td colspan="'.($options['show-only-total'] ? 2 : 5).'" class="text-right">
                    <b>'.tr('Sconto in fattura', [], ['upper' => true]).':</b>
                </td>
               <th colspan="'.($options['show-only-total'] ? (($has_image) ? 2 : 1) : (($has_image) ? 3 : 2)).'" class="text-right">
                    <b>'.moneyFormat($sconto_finale, $d_totali).'</b>
                </th>
            </tr>';

            // NETTO A PAGARE
            echo '
            <tr>
                <td colspan="'.($options['show-only-total'] ? 2 : 5).'" class="text-right">
                    <b>'.tr('Netto a pagare', [], ['upper' => true]).':</b>
                </td>
                <th colspan="'.($options['show-only-total'] ? (($has_image) ? 2 : 1) : (($has_image) ? 3 : 2)).'" class="text-right">
                    <b>'.moneyFormat($netto_a_pagare, $d_totali).'</b>
                </th>
            </tr>';
            $autofill->set(2);
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
$pagamento = Pagamento::find($documento['idpagamento']);

echo '
<table class="table table-striped">
    <tr>
        <th colspan="6" class="text-left text-muted">
            '.tr('Condizioni generali di fornitura', [], ['upper' => true]).'
        </th>
    </tr>

    <tr>
        <td class="text-muted small-bold border-bottom" style="width:25%">
            '.tr('Pagamento', [], ['upper' => true]).'
        </td>

        <td class="border-bottom">
            '.($pagamento ? $pagamento->getTranslation('title') : '').'
        </td>
    </tr>

    <tr>
        <td class="text-muted border-bottom small-bold">
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
        <td class="text-muted border-bottom small-bold">
            '.tr('Validità contratto', [], ['upper' => true]).'
        </td>

        <td class="border-bottom">';

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
        <td class="text-muted border-bottom small-bold">
            '.tr('Esclusioni', [], ['upper' => true]).'
        </td>

        <td class="border-bottom">
            '.nl2br((string) $documento['esclusioni']).'
        </td>
    </tr>
</table>';

// Conclusione
if (empty($documento->stato->fatturabile)) {
    echo '
<p class="text-center"><b>'.tr('Il tutto S.E. & O.').'</b></p>
<p class="text-center">'.tr("In attesa di un Vostro Cortese riscontro, colgo l'occasione per porgere Cordiali Saluti").'</p>';
}

if (!empty($documento->condizioni_fornitura)) {
    echo '<pagebreak>'.$documento->condizioni_fornitura;
}
