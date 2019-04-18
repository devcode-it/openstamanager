<?php

include_once __DIR__.'/../../core.php';

$report_name = 'fattura_'.$numero.'.pdf';

$autofill = [
    'count' => 0, // Conteggio delle righe
    'words' => 70, // Numero di parole dopo cui contare una riga nuova
    'rows' => $fattura_accompagnatoria ? 15 : 20, // Numero di righe massimo presente nella pagina
    'additional' => $fattura_accompagnatoria ? 10 : 15, // Numero di righe massimo da aggiungere
    'columns' => 5, // Numero di colonne della tabella
];

$v_iva = [];
$v_totale = [];

$sconto = [];
$imponibile = [];
$iva = [];

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
$righe = $dbo->fetchArray("SELECT *,
	IFNULL((SELECT `codice` FROM `mg_articoli` WHERE `id` = `co_righe_documenti`.`idarticolo`), '') AS codice_articolo,
    (SELECT GROUP_CONCAT(`serial` SEPARATOR ', ') FROM `mg_prodotti` WHERE `id_riga_documento` = `co_righe_documenti`.`id`) AS seriali,
    (SELECT `percentuale` FROM `co_iva` WHERE `id` = `co_righe_documenti`.`idiva`) AS perc_iva
FROM `co_righe_documenti` WHERE `iddocumento` = ".prepare($id_record).' ORDER BY `order`');
foreach ($righe as $r) {
    $count = 0;
    $count += ceil(strlen($r['descrizione']) / $autofill['words']);
    $count += substr_count($r['descrizione'], PHP_EOL);

    $v_iva[$r['desc_iva']] = sum($v_iva[$r['desc_iva']], $r['iva']);
    $v_totale[$r['desc_iva']] = sum($v_totale[$r['desc_iva']], [
        $r['subtotale'], -$r['sconto'],
    ]);

    // Valori assoluti
    $r['qta'] = abs($r['qta']);
    if (empty($r['sconto_globale'])) {
        $r['subtotale'] = abs($r['subtotale']);
    } else {
        $r['subtotale'] = ($r['subtotale']);
    }
    $r['sconto_unitario'] = abs($r['sconto_unitario']);
    $r['sconto'] = abs($r['sconto']);
    if (empty($r['sconto_globale'])) {
        $r['iva'] = abs($r['iva']);
    } else {
        $r['iva'] = ($r['iva']);
    }

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
				'.(empty($r['qta']) ? '' : moneyFormat($r['subtotale'] / $r['qta']));

        if ($r['sconto'] > 0) {
            echo "
                <br><small class='text-muted'>".tr('sconto _TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($r['sconto_unitario']),
                    '_TYPE_' => ($r['tipo_sconto'] == 'PRC' ? '%' : currency()),
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
				'.moneyFormat($r['subtotale'] - $r['sconto']);

        if ($r['sconto'] > 0) {
            /*echo "
                <br><small class='text-muted'>".tr('sconto _TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($r['sconto']),
                    '_TYPE_' => currency(),
                ]).'</small>';*/

            if ($count <= 1) {
                $count += 0.4;
            }
        }
    }
    echo '
            </td>';

    // Iva
    echo '
            <td class="text-center">';
    if (empty($r['is_descrizione']) && empty($r['sconto_globale'])) {
        echo '
                '.Translator::numberToLocale($r['perc_iva']);
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
    <tr>';
if (abs($record['bollo']) > 0) {
    echo '
        <td width="85%">';
} else {
    echo '
        <td width="100%">';
}
    if (!empty($record['note'])) {
        echo '
            <p class="small-bold">'.tr('Note', [], ['upper' => true]).':</p>
            <p>'.nl2br($record['note']).'</p>';
    }
    echo '
        </td>';
if (abs($record['bollo']) > 0) {
    echo '
        <td width="15%" align="right">';
}
if (abs($record['bollo']) > 0) {
    echo '
            <table style="width: 20mm; font-size: 50%; text-align: center" class="table-bordered">
                <tr>
                    <td style="height: 20mm;">
                        <br><br>
                        '.tr('Spazio per applicazione marca da bollo', [], ['upper' => true]).'
                    </td>
                </tr>
            </table>';
}
if (abs($record['bollo']) > 0) {
    echo '
        </td>';
}

echo '
    </tr>';
echo '
</table>';

// Calcoli
$imponibile = sum(array_column($righe, 'subtotale'));
$sconto = sum(array_column($righe, 'sconto'));
$iva = sum(array_column($righe, 'iva'));

$imponibile_scontato = sum($imponibile, -$sconto);

$totale_iva = sum($iva, $record['iva_rivalsainps']);

$totale = sum([
    $imponibile_scontato,
    $record['rivalsainps'],
    $totale_iva,
]);

$netto_a_pagare = sum([
    $totale,
    $record['bollo'],
    -$record['ritenutaacconto'],
]);

$imponibile = abs($imponibile);
$sconto = abs($sconto);
$iva = abs($iva);
$imponibile_scontato = abs($imponibile_scontato);
$totale_iva = abs($totale_iva);
$totale = abs($totale);
$netto_a_pagare = abs($netto_a_pagare);
