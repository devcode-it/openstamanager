<?php

include_once __DIR__.'/../../core.php';

// Righe documento
$righe = $documento->getRighe();

$has_image = $righe->search(function ($item) {
    return !empty($item->articolo->immagine);
});

if ($has_image) {
    $columns = 6;
    $char_number = $options['pricing'] ? 26 : 63;
} else {
    $columns = 5;
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
        <tr>";

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

foreach ($righe as $riga) {
    $r = $riga->toArray();

    $autofill->count($r['descrizione']);

    echo '
        <tr>';

    if ($has_image) {
        if ($riga->isArticolo()) {
            echo '
            <td align="center">
                <img src="'.$riga->articolo->image.'" style="max-height: 80px">
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
            '_COD_' => $riga->articolo->codice,
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
                $text = tr('sconto _TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($riga->sconto_unitario),
                    '_TYPE_' => ($riga->tipo_sconto == 'PRC' ? '%' : currency()),
                ]);

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

// TOTALE COSTI FINALI
if ($options['pricing']) {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="'.($has_image ? 4 : 3).'" class="text-right border-top">
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
        <td colspan="'.($has_image ? 4 : 3).'" class="text-right border-top">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($sconto, 2).'</b>
        </th>
    </tr>';

        // Totale imponibile
        echo '
    <tr>
        <td colspan="'.($has_image ? 4 : 3).'" class="text-right border-top">
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
        <td colspan="'.($has_image ? 4 : 3).'" class="text-right border-top">
            <b>'.tr('Totale IVA', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($totale_iva, 2).'</b>
        </th>
    </tr>';

    // TOTALE
    echo '
    <tr>
    	<td colspan="'.($has_image ? 4 : 3).'" class="text-right border-top">
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
