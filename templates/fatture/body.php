<?php

include_once __DIR__.'/../../core.php';

$report_name = 'fattura_'.$numero.'.pdf';

$autofill = [
    'count' => 0, // Conteggio delle righe
    'words' => 70, // Numero di parolo dopo cui contare una riga nuova
    'rows' => 20, // Numero di righe massimo presente nella pagina
    'additional' => 15, // Numero di righe massimo da aggiungere
    'columns' => 6, // Numero di colonne della tabella
];

$v_iva = [];
$v_totale = [];

$sconto = 0;
$imponibile = 0;
$iva = 0;

// Intestazione tabella per righe
echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Q.tà', [], ['upper' => true])."</th>
            <th class='text-center' style='width:7%'>".tr('Um', [], ['upper' => true])."</th>
            <th class='text-center' style='width:16%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:20%'>".tr('Importo', [], ['upper' => true])."</th>
            <th class='text-center' style='width:7%'>".tr('IVA', [], ['upper' => true]).' (%)</th>
        </tr>
    </thead>

    <tbody>';

// RIGHE FATTURA CON ORDINAMENTO UNICO
$righe = $dbo->fetchArray("SELECT *, IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo),'') AS codice_articolo, (SELECT percentuale FROM co_iva WHERE id=idiva) AS perc_iva FROM `co_righe_documenti` WHERE iddocumento=".prepare($iddocumento).' ORDER BY `order`');
foreach ($righe as $r) {
    $autofill['count'] += ceil(strlen($r['descrizione']) / $autofill['words']);
    $autofill['count'] += substr_count($r['descrizione'], PHP_EOL);

    echo '
        <tr>
            <td>
                '.nl2br($r['descrizione']);

    if (!empty($r['codice_articolo'])) {
        echo '
                <br><small>'.tr('COD. _COD_', [
                    '_COD_' => $r['codice_articolo'],
                ]).'</small>';
        $autofill['count'] += 0.4;
    }

    // Aggiunta riferimento a ordine
    if (!empty($r['idordine'])) {
        $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM or_ordini WHERE id='.prepare($r['idordine']));
        $numero = !empty($rso[0]['numero_esterno']) ? $rso[0]['numero_esterno'] : $rso[0]['numero'];

        echo '
                <br><small>'.tr('Rif. ordine n<sup>o</sup>_NUM_ del _DATE_', [
                    '_NUM_' => $numero,
                    '_DATE_' => Translator::dateToLocale($rso[0]['data']),
                ]).'</small>';
        $autofill['count'] += 0.4;
    }

    // Aggiunta riferimento a ddt
    elseif (!empty($r['idddt'])) {
        $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM dt_ddt WHERE id='.prepare($r['idddt']));
        $numero = !empty($rso[0]['numero_esterno']) ? $rso[0]['numero_esterno'] : $rso[0]['numero'];

        echo '
                <br><small>'.tr('Rif. ddt n<sup>o</sup>_NUM_ del _DATE_', [
                    '_NUM_' => $numero,
                    '_DATE_' => Translator::dateToLocale($rso[0]['data']),
                ]).'</small>';
        $autofill['count'] += 0.4;
    }
    echo '
            </td>';

    echo "
            <td class='text-center'>
                ".(empty($r['qta']) ? '' : Translator::numberToLocale($r['qta'], 2)).'
            </td>';

    // Unità di miusura
    echo "
            <td class='text-center'>
                ".nl2br(strtoupper($r['um'])).'
            </td>';

    // Prezzo unitario
    echo "
            <td class='text-right'>
                ".(empty($r['qta']) || empty($r['subtotale']) ? '' : Translator::numberToLocale($r['subtotale'] / $r['qta'], 2)).' &euro;
            </td>';

    // Imponibile
    echo "
            <td class='text-right'>
                ".(empty($r['subtotale']) ? '' : Translator::numberToLocale($r['subtotale'], 2)).' &euro;';

    if ($r['sconto'] > 0) {
        $autofill['count'] += 0.4;
        echo "
                <br><small class='help-block'>- sconto ".Translator::numberToLocale($r['sconto_unitario']).($r['tipo_sconto'] == 'PRC' ? '%' : ' &euro;').'</small>';
    }

    echo '
            </td>';

    // Iva
    echo '
            <td class="text-center">
                '.Translator::numberToLocale($r['perc_iva'], 2).'
            </td>
        </tr>';

    $imponibile += $r['subtotale'];
    $iva += $r['iva'];
    $sconto += $r['sconto'];

    $v_iva[$r['desc_iva']] += $r['iva'];
    $v_totale[$r['desc_iva']] += $r['subtotale'] - $r['sconto'];
}

echo '
        |autofill|
    </tbody>
</table>';


// Aggiungo diciture per condizioni iva particolari
foreach ($v_iva as $key => $value) {
    $dicitura = $dbo->fetchArray('SELECT dicitura FROM co_iva WHERE descrizione = '.prepare($key));

    if (!empty($dicitura[0]['dicitura'])) {
        $testo = $dicitura[0]['dicitura'];

        echo "
<p class='text-center'>
    <b>".nl2br($testo)."</b>
</p>";
    }
}

if (!empty($records[0]['note'])) {
    echo '
<br>
<p class="small-bold">'.tr('Note', [], ['upper' => true]).':</p>
<p>'.nl2br($records[0]['note']).'</p>';
}

// Info per il footer
$totale_iva = $iva;
$imponibile_documento = $imponibile - $sconto;
$totale_documento = $imponibile - $sconto + $totale_iva;
