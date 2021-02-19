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

// Righe documento
$righe = $documento->getRighe();

$columns = 6;

//Immagine solo per documenti di vendita
if ($documento->direzione == 'entrata') {
    $has_image = $righe->search(function ($item) {
        return !empty($item->articolo->immagine);
    }) !== false;

    if ($has_image) {
        ++$columns;
        $char_number = $options['pricing'] ? 26 : 63;
    }
}

if ($documento->direzione == 'uscita') {
    ++$columns;
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
            <th class='text-center' style='width:5%'>".tr('#', [], ['upper' => true]).'</th>';

            if ($documento->direzione == 'uscita') {
                echo "
            <th class='text-center' style='width:10%'>".tr('Codice', [], ['upper' => true]).'</th>';
            }

            if ($has_image) {
                echo "
            <th class='text-center' style='width:20%'>".tr('Immagine', [], ['upper' => true]).'</th>';
            }

            echo "
            <th class='text-center'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Q.tà', [], ['upper' => true]).'</th>';

if ($options['pricing']) {
    echo "
            <th class='text-center' style='width:15%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Imponibile', [], ['upper' => true])."</th>
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
            <td class="text-center" style="vertical-align: middle">
                '.$num.'
            </td>';

    if ($documento->direzione == 'uscita') {
        echo '
            <td class="text-center" style="vertical-align: middle">
                '.$riga->articolo->codice.'
            </td>';
    }

    if ($has_image) {
        if ($riga->isArticolo() && !empty($riga->articolo->image)) {
            echo '
            <td align="center">
                <img src="'.$riga->articolo->image.'" style="max-height: 80px; max-width:120px">
            </td>';

            $autofill->set(5);
        } else {
            echo '
            <td></td>';
        }
    }

    echo '
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
                '.Translator::numberToLocale(abs($qta), 'qta').' '.$um.'
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

            // Iva
            echo '
            <td class="text-center">
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

$show_sconto = $sconto > 0;

$colspan = 4;
($documento->direzione == 'uscita' ? $colspan++ : $colspan);
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
            <b>'.moneyFormat($show_sconto ? $imponibile : $totale_imponibile, 2).'</b>
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
            <b>'.moneyFormat($sconto, 2).'</b>
        </th>
    </tr>';

        // Totale imponibile
        echo '
    <tr>
        <td colspan="'.$colspan.'" class="text-right border-top">
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
        <td colspan="'.$colspan.'" class="text-right border-top">
            <b>'.tr('Totale IVA', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($totale_iva, 2).'</b>
        </th>
    </tr>';

    // TOTALE
    echo '
    <tr>
    	<td colspan="'.$colspan.'" class="text-right border-top">
            <b>'.tr('Totale documento', [], ['upper' => true]).':</b>
    	</td>
    	<th colspan="2" class="text-right">
    		<b>'.moneyFormat($totale, 2).'</b>
    	</th>
    </tr>';
}

echo '
</table>';

if (!empty($documento['note'])) {
    echo '
<br>
<p class="small-bold">'.tr('Note', [], ['upper' => true]).':</p>
<p>'.nl2br($documento['note']).'</p>';
}
