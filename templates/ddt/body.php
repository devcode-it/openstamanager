<?php

include_once __DIR__.'/../../core.php';

// Creazione righe fantasma
$autofill = new \Util\Autofill($options['pricing'] ? 5 : 2);
$autofill->setRows(16);

// Intestazione tabella per righe
echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center'>".tr('Descrizione', [], ['upper' => true])."</th>
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

    // Aggiunta dei riferimenti ai documenti
    if (setting('Riferimento dei documenti nelle stampe')) {
        $ref = doc_references($r, $record['dir'], ['iddocumento']);

        if (!empty($ref)) {
            echo '
                <br><small>'.$ref['description'].'</small>';

            $autofill->count($ref['description'], true);
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
				'.moneyFormat($riga->prezzo_unitario_vendita);

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
    </tbody>
</table>';
