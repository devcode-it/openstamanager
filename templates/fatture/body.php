<?php

include_once __DIR__.'/../../core.php';

$autofill = [
    'count' => 0, // Conteggio delle righe
    'words' => 70, // Numero di parole dopo cui contare una riga nuova
    'rows' => $fattura_accompagnatoria ? 15 : 20, // Numero di righe massimo presente nella pagina
    'additional' => $fattura_accompagnatoria ? 10 : 15, // Numero di righe massimo da aggiungere
    'columns' => 5, // Numero di colonne della tabella
];

$v_iva = [];
$v_totale = [];

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

    <tfoot>
        <tr>
            <td style="border-top:none; border-bottom:1px solid #aaa;"></td>
            <td style="border-top:none; border-bottom:1px solid #aaa;"></td>
            <td style="border-top:none; border-bottom:1px solid #aaa;"></td>
            <td style="border-top:none; border-bottom:1px solid #aaa;"></td>
            <td style="border-top:none; border-bottom:1px solid #aaa;"></td>
        </tr>
    </tfoot>

    <tbody>';

// RIGHE FATTURA CON ORDINAMENTO UNICO
$righe = $fattura->getRighe();
foreach ($righe as $riga) {
    $r = $riga->toArray();
    $count = 0;
    $count += ceil(strlen($r['descrizione']) / $autofill['words']);
    $count += substr_count($r['descrizione'], PHP_EOL);

    $v_iva[$r['desc_iva']] = sum($v_iva[$r['desc_iva']], $r['iva']);
    $v_totale[$r['desc_iva']] = sum($v_totale[$r['desc_iva']], $riga->totale_imponibile);

    // Valori assoluti
    $r['qta'] = abs($r['qta']);
    $r['sconto_unitario'] = abs($r['sconto_unitario']);
    $r['sconto'] = abs($r['sconto']);

    echo '
        <tr>
            <td>
                '.nl2br($r['descrizione']);

    // Codice articolo
    if (!empty($r['codice_articolo'])) {
        echo '
                <br><small>'.tr('COD. _COD_', [
                    '_COD_' => $r['codice_articolo'],
                ]).'</small>';

        if ($count <= 1) {
            $count += 0.4;
        }
    }

    // Seriali
    if (!empty($r['seriali'])) {
        echo '
                <br><small>'.tr('SN').': '.$r['seriali'].'</small>';

        if ($count <= 1) {
            $count += 0.4;
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

        if ($count <= 1) {
            $count += 0.4;
        }
    }

    // Aggiunta dei riferimenti ai documenti
    if (setting('Riferimento dei documenti nelle stampe')) {
        $ref = doc_references($r, $record['dir'], ['iddocumento']);

        if (!empty($ref)) {
            echo '
                <br><small>'.$ref['description'].'</small>';

            if ($count <= 1) {
                $count += 0.4;
            }
        }
    }

    echo '
            </td>';

    echo '
            <td class="text-center">';
    if (empty($r['is_descrizione'])) {
        echo '
                '.Translator::numberToLocale($r['qta'], 'qta').' '.$r['um'];
    }
    echo '
            </td>';

    // Prezzo unitario
    echo "
            <td class='text-right'>";
    if (empty($r['is_descrizione'])) {
        echo '
				'.(empty($r['qta']) ? '' : moneyFormat($riga->prezzo_unitario_vendita));

        if ($riga->sconto > 0) {
            echo "
                <br><small class='text-muted'>".tr('sconto _TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($riga->sconto_unitario),
                    '_TYPE_' => ($riga->tipo_sconto == 'PRC' ? '%' : currency()),
                ]).'</small>';

            if ($count <= 1) {
                $count += 0.4;
            }
        }
    }

    echo '
            </td>';

    // Imponibile
    echo "
            <td class='text-right'>";
    if (empty($r['is_descrizione'])) {
        echo '
				'.moneyFormat($riga->totale_imponibile);
    }
    echo '
            </td>';

    // Iva
    echo '
            <td class="text-center">';
    if (empty($r['is_descrizione'])) {
        echo '
                '.Translator::numberToLocale($riga->aliquota->percentuale, 0);
    }
    echo '
            </td>
        </tr>';

    $autofill['count'] += $count;
}

echo '
        |autofill|
    </tbody>
</table>';

// Aggiungo diciture particolari per l'anagrafica cliente
$dicitura = $dbo->fetchArray('SELECT diciturafissafattura FROM an_anagrafiche WHERE idanagrafica = '.prepare($id_cliente));

if (!empty($dicitura[0]['diciturafissafattura'])) {
    $testo = $dicitura[0]['diciturafissafattura'];

    echo "
<p class='text-center'>
<b>".nl2br($testo).'</b>
</p>';
}

// Aggiungo diciture per condizioni iva particolari
foreach ($v_iva as $key => $value) {
    $dicitura = $dbo->fetchArray('SELECT dicitura FROM co_iva WHERE descrizione = '.prepare($key));

    if (!empty($dicitura[0]['dicitura'])) {
        $testo = $dicitura[0]['dicitura'];

        echo "
<p class='text-center'>
    <b>".nl2br($testo).'</b>
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
