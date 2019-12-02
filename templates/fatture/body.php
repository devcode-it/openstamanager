<?php

include_once __DIR__.'/../../core.php';

$v_iva = [];
$v_totale = [];

// Creazione righe fantasma
$autofill = new \Util\Autofill(5, 40);
$rows_per_page = $fattura_accompagnatoria ? 15 : 20;
if (!empty($options['last-page-footer'])) {
    $rows_per_page += 7;
}
$autofill->setRows($rows_per_page);

// Intestazione tabella per righe
echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:14%'>".tr('Q.tà', [], ['upper' => true])."</th>
            <th class='text-center' style='width:16%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:20%'>".tr('Importo', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('IVA', [], ['upper' => true]).' (%)</th>
        </tr>
    </thead>

    <tbody>';

// Righe documento
$righe = $documento->getRighe();
foreach ($righe as $riga) {
    $r = $riga->toArray();

    $autofill->count($r['descrizione']);

    $v_iva[$r['desc_iva']] = sum($v_iva[$r['desc_iva']], $riga->iva);
    $v_totale[$r['desc_iva']] = sum($v_totale[$r['desc_iva']], $riga->totale_imponibile);

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
    if (!empty($record['ref_documento'])) {
        $data = $dbo->fetchArray("SELECT IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM co_documenti WHERE id = ".prepare($record['ref_documento']));

        $text = tr('Rif. fattura _NUM_ del _DATE_', [
            '_NUM_' => $data[0]['numero'],
            '_DATE_' => Translator::dateToLocale($data[0]['data']),
        ]);

        echo '
        <br><small>'.$text.'</small>';

        $autofill->count($text, true);
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
    } else {
        echo '
            <td></td>
            <td></td>
            <td></td>
            <td></td>';
    }

    echo '
        </tr>';

    $autofill->next();
}

echo '
        |autofill|
    </tbody>
</table>';

// Aggiungo diciture particolari per l'anagrafica cliente
$dicitura = $dbo->fetchOne('SELECT diciturafissafattura AS dicitura FROM an_anagrafiche WHERE idanagrafica = '.prepare($id_cliente));

if (!empty($dicitura['dicitura'])) {
    echo '
<p class="text-center">
    <b>'.nl2br($dicitura['dicitura']).'</b>
</p>';
}

// Aggiungo diciture per condizioni iva particolari
foreach ($v_iva as $key => $value) {
    $dicitura = $dbo->fetchOne('SELECT dicitura FROM co_iva WHERE descrizione = '.prepare($key));

    if (!empty($dicitura['dicitura'])) {
        echo '
<p class="text-center">
    <b>'.nl2br($dicitura['dicitura']).'</b>
</p>';
    }
}
echo '
<table class="table">';
echo '
    <tr>
        <td width="100%">';

    if (!empty($record['note'])) {
        echo '
            <p class="small-bold">'.tr('Note', [], ['upper' => true]).':</p>
            <p>'.nl2br($record['note']).'</p>';
    }

    echo '
        </td>';

echo '
    </tr>';
echo '
</table>';
