<?php

include_once __DIR__.'/../../core.php';

$report_name = 'ordine_'.$numero_ord.'.pdf';

$autofill = [
    'count' => 0, // Conteggio delle righe
    'words' => 70, // Numero di parolo dopo cui contare una riga nuova
    'rows' => 20, // Numero di righe massimo presente nella pagina
    'additional' => 15, // Numero di righe massimo da aggiungere
    'columns' => 5, // Numero di colonne della tabella
];

$sconto = [];
$imponibile = [];
$iva = [];

// Intestazione tabella per righe
echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Q.tà', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Imponibile', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('IVA', [], ['upper' => true]).' (%)</th>
        </tr>
    </thead>

    <tbody>';

// RIGHE PREVENTIVO CON ORDINAMENTO UNICO
$righe = $dbo->fetchArray("SELECT *, IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo),'') AS codice_articolo, (SELECT percentuale FROM co_iva WHERE id=idiva) AS perc_iva FROM `or_righe_ordini` WHERE idordine=".prepare($idordine).' ORDER BY `order`');
foreach ($righe as $r) {
    $count = 0;
    $count += ceil(strlen($r['descrizione']) / $autofill['words']);
    $count += substr_count($r['descrizione'], PHP_EOL);

    echo '
        <tr>
            <td>
                '.nl2br($r['descrizione']);

    if (!empty($r['codice_articolo'])) {
        echo '
                <br><small>'.tr('COD. _COD_', [
                    '_COD_' => $r['codice_articolo'],
                ]).'</small>';

        if ($count <= 1) {
            $count += 0.4;
        }
    }

    echo '
            </td>';

    echo "
            <td class='text-center'>
                ".(empty($r['qta']) ? '' : Translator::numberToLocale($r['qta'], 2)).' '.$r['um'].'
            </td>';

    if ($mostra_prezzi) {
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
            echo "
                <br><small class='help-block'>- sconto ".Translator::numberToLocale($r['sconto_unitario']).($r['tipo_sconto'] == 'PRC' ? '%' : ' &euro;').'</small>';

            if ($count <= 1) {
                $count += 0.4;
            }
        }

        echo '
            </td>';
    } else {
        echo '
            <td class="text-center">-</td>
            <td class="text-center">-</td>';
    }

    // Iva
    echo '
            <td class="text-center">
                '.Translator::numberToLocale($r['perc_iva'], 2).'
            </td>
        </tr>';

    $autofill['count'] += $count;

    $sconto[] = $r['sconto'];
    $imponibile[] = $r['subtotale'];
    $iva[] = $r['iva'];
}

$sconto = sum($sconto);
$imponibile = sum($imponibile);
$iva = sum($iva);

$totale = $imponibile - $sconto;

echo '
        |autofill|
    </tbody>';

// TOTALE COSTI FINALI
if ($mostra_prezzi) {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($imponibile, 2).' &euro;</b>
        </th>
    </tr>';

    // Eventuale sconto incondizionato
    if (!empty($sconto)) {
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-center">
            <b>-'.Translator::numberToLocale($sconto, 2).' &euro;</b>
        </th>
    </tr>';

        // Imponibile scontato
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Imponibile scontato', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($totale, 2).' &euro;</b>
        </th>
    </tr>';
    }

    // IVA
    echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Totale IVA', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($iva, 2).' &euro;</b>
        </th>
    </tr>';

    $totale = sum($totale, $iva);

    // TOTALE
    echo '
    <tr>
    	<td colspan="3" class="text-right border-top">
            <b>'.tr('Quotazione totale', [], ['upper' => true]).':</b>
    	</td>
    	<th colspan="2" class="text-center">
    		<b>'.Translator::numberToLocale($totale, 2).' &euro;</b>
    	</th>
    </tr>';
}

echo'
</table>';

if (!empty($records[0]['note'])) {
    echo '
<br>
<p class="small-bold">'.tr('Note', [], ['upper' => true]).':</p>
<p>'.nl2br($records[0]['note']).'</p>';
}
