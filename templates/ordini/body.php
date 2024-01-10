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

$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

// Righe documento
$righe = $documento->getRighe();

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

$columns = 7;

$has_image = $righe->search(function ($item) {
    return !empty($item->articolo->immagine);
}) !== false && $options['images'] == true;

if ($has_image) {
    ++$columns;
    $char_number = $options['pricing'] ? 26 : 63;
}

if ($documento->direzione == 'uscita') {
    $columns += 2;
    $char_number = $options['pricing'] ? 26 : 63;
} else {
    $char_number = $options['pricing'] ? 45 : 82;
}
$columns = $options['pricing'] ? $columns : $columns - 3;

// Creazione righe fantasma
$autofill = new \Util\Autofill($columns, $char_number);
$autofill->setRows(30);

// Intestazione tabella per righe
echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:4%'>".tr('#', [], ['upper' => true]).'</th>';

            if ($has_image) {
                echo "
            <th class='text-center' style='width:20%'>".tr('Immagine', [], ['upper' => true]).'</th>';
            }

            echo "
            <th class='text-center'>".tr('Descrizione', [], ['upper' => true]).'</th>
            ';

            if ($documento->direzione == 'uscita') {
                echo "
            <th class='text-center' style='width:11%'>".tr('Codice', [], ['upper' => true])."</th>
            <th class='text-center' style='width:11%'>".tr('Codice fornitore', [], ['upper' => true]).'</th>';
            }
            echo "
            <th class='text-center' style='width:9%'>".tr('Q.tà', [], ['upper' => true]).'</th>';

if ($options['pricing']) {
    echo "
            <th class='text-center' style='width:11%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:11%'>".tr('Imponibile', [], ['upper' => true])."</th>
            <th class='text-center' style='width:5%'>".tr('IVA', [], ['upper' => true]).' (%)</th>';
}

            echo "
            <th class='text-center' style='width:10%'>".tr('Data evasione', [], ['upper' => true]).'</th>
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
            <td class="text-center" style="vertical-align: middle">';

    $text = '';

    foreach ($riferimenti as $key => $riferimento) {
        if (in_array($riga->id, $riferimento)) {
            if ($riga->id === $riferimento[0]) {
                $riga_ordine = $database->fetchOne('SELECT numero_cliente, data_cliente FROM or_ordini WHERE id = '.prepare($riga->idordine));
                if (!empty($riga_ordine['numero_cliente']) && !empty($riga_ordine['data_cliente'])) {
                    $text = $text.'<b>Ordine n. '.$riga_ordine['numero_cliente'].' del '.Translator::dateToLocale($riga_ordine['data_cliente']).'</b><br>';
                }
                $r['descrizione'] = str_replace('Rif. '.strtolower($key), '', $r['descrizione']);
                preg_match("/Rif\.(.*)/s", $r['descrizione'], $rif2);
                $r['descrizione'] = str_replace('Rif.'.strtolower($rif2[1]), '', $r['descrizione']);

                if (!empty($rif2)) {
                    $text .= '<b>'.$rif2[0].'</b>';
                }
                $text .= '<b>'.$key.'</b></td>';
                if ($options['pricing']) {
                    $text .= '
                        <td></td>
                        <td></td>
                        <td></td>';
                }

                $text .= '<td></td><td></td></tr><tr><td class="text-center" nowrap="nowrap" style="vertical-align: middle">';

                echo '
                </td>';
                if ($has_image) {
                    echo '
                    <td></td>';
                }
                echo '
                <td>
                    '.nl2br($text);
            }
        }
        $r['descrizione'] = preg_replace("/Rif\.(.*)/s", '', $r['descrizione']);
        $autofill->count($r['descrizione']);
    }

    $source_type = get_class($riga);
    if (!setting('Visualizza riferimento su ogni riga in stampa')) {
        echo $num.'</td>';
        if ($has_image) {
            if ($riga->isArticolo() && !empty($riga->articolo->image)) {
                echo '
                <td align="center">
                    <img src="'.$riga->articolo->image.'" style="max-height: 80px; max-width:120px">
                </td>';
            } else {
                echo '
                <td></td>';
            }
        }
        echo '
            <td>'.$r['descrizione'];
    } else {
        echo $num.'
            </td>';
        if ($has_image) {
            if ($riga->isArticolo() && !empty($riga->articolo->image)) {
                echo '
                    <td align="center">
                        <img src="'.$riga->articolo->image.'" style="max-height: 80px; max-width:120px">
                    </td>';
            } else {
                echo '
                    <td></td>';
            }
        }
        echo '
            <td>'.nl2br($r['descrizione']);
    }

    if ($documento->direzione == 'uscita') {
        echo '
            <td class="text-center" style="vertical-align: middle">
                '.$riga->articolo->codice.'
            </td>
            <td class="text-center" style="vertical-align: middle">
                '.($riga->articolo ? $riga->articolo->dettaglioFornitore($documento->idanagrafica)->codice_fornitore : '').'
            </td>';
    }

    if ($riga->isArticolo()) {
        if ($documento->direzione == 'entrata' && !$options['hide-item-number']) {
            // Codice articolo
            $text = tr('COD. _COD_', [
                '_COD_' => $riga->codice,
            ]);
            echo '
                    <br><small>'.$text.'</small>';

            $autofill->count($text, true);
        }

        // Seriali
        $seriali = $riga->serials;
        if (!empty($seriali)) {
            $text = tr('SN').': '.implode(', ', $seriali);
            echo '
                    <br><small>'.$text.'</small>';

            $autofill->count($text, true);
        }
    }

    echo '
            </td>';

    if (!$riga->isDescrizione()) {
        $qta = $riga->qta;
        $um = $r['um'];

        if ($riga->isArticolo() && $documento->direzione == 'uscita' && !empty($riga->articolo->um_secondaria)) {
            $um = $riga->articolo->um_secondaria;
            $qta *= $riga->articolo->fattore_um_secondaria;
        }

        echo '
            <td class="text-center">
                '.Translator::numberToLocale(abs($qta), $d_qta).' '.$um.'
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

                $autofill->count($text, true);
            }

            echo '
            </td>';

            // Imponibile
            echo '
            <td class="text-right">
				'.moneyFormat($prezzi_ivati ? $riga->totale : $riga->totale_imponibile, $d_importi).'
            </td>';

            // Iva
            echo '
            <td class="text-center">
                '.Translator::numberToLocale($riga->aliquota->percentuale, 0).'
            </td>';
        }

        echo '
        <td class="text-center">
            '.Translator::dateToLocale($riga->data_evasione).($riga->ora_evasione ? '<br>'.Translator::timeToLocale($riga->ora_evasione).'' : '').'
        </td>';
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

$colspan = 5;
($documento->direzione == 'uscita' ? $colspan += 2 : $colspan);
($has_image ? $colspan++ : $colspan);

// TOTALE COSTI FINALI
if ($options['pricing']) {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="'.$colspan.'" class="text-right border-top">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($show_sconto ? $imponibile : $totale_imponibile, $d_totali).'</b>
        </th>
    </tr>';

    // Eventuale sconto incondizionato
    if ($show_sconto) {
        echo '
    <tr>
        <td colspan="'.$colspan.'" class="text-right border-top">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($sconto, $d_totali).'</b>
        </th>
    </tr>';

        // Totale imponibile
        echo '
    <tr>
        <td colspan="'.$colspan.'" class="text-right border-top">
            <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($totale_imponibile, $d_totali).'</b>
        </th>
    </tr>';
    }

    // IVA
    echo '
    <tr>
        <td colspan="'.$colspan.'" class="text-right border-top">
            <b>'.tr('Totale IVA', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($totale_iva, $d_totali).'</b>
        </th>
    </tr>';

    // TOTALE
    echo '
    <tr>
    	<td colspan="'.$colspan.'" class="text-right border-top">
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
            <td colspan="'.$colspan.'" class="text-right border-top">
                <b>'.tr('Sconto in fattura', [], ['upper' => true]).':</b>
            </td>
            <th colspan="2" class="text-right">
                <b>'.moneyFormat($sconto_finale, $d_totali).'</b>
            </th>
        </tr>';

        // NETTO A PAGARE
        echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right border-top">
                <b>'.tr('Netto a pagare', [], ['upper' => true]).':</b>
            </td>
            <th colspan="2" class="text-right">
                <b>'.moneyFormat($netto_a_pagare, $d_totali).'</b>
            </th>
        </tr>';
    }
}

echo '
</table>';

if (!empty($documento->condizioni_fornitura)) {
    echo '<pagebreak>'.$documento->condizioni_fornitura;
}

if (!empty($documento['note'])) {
    echo '
<br>
<p class="small-bold">'.tr('Note', [], ['upper' => true]).':</p>
<p>'.nl2br($documento['note']).'</p>';
}
