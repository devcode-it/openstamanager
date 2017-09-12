<?php

include_once __DIR__.'/../../core.php';

$report_name = 'preventivo_'.$idpreventivo.'.pdf';

$autofill = [
    'count' => 0, // Conteggio delle righe
    'words' => 70, // Numero di parolo dopo cui contare una riga nuova
    'rows' => 20, // Numero di righe massimo presente nella pagina
    'additional' => 10, // Numero di righe massimo da aggiungere
    'columns' => 5, // Numero di colonne della tabella
];

echo '
<div class="row">
    <div class="col-xs-6">
        <div class="text-center" style="height:5mm;">
            <b>'.tr('Preventivo N<sup>o</sup> _NUM_ del _DATE_', [
                '_NUM_' => $records[0]['numero'],
                '_DATE_' => Translator::dateToLocale($records[0]['data']),
            ], ['upper' => true]).'</b>
        </div>
    </div>

    <div class="col-xs-5 col-xs-offset-1">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td colspan=2 class="border-full" style="height:16mm;">
                    <p class="small-bold">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
                    <p>$c_indirizzo$ $c_citta$</p>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.tr('Partita IVA', [], ['upper' => true]).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_piva$</small>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.tr('Codice fiscale', [], ['upper' => true]).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_codicefiscale$</small>
                </td>
            </tr>
        </table>
    </div>
</div>';

// Descrizione
if (!empty($records[0]['descrizione'])) {
    echo '
<p>'.nl2br($records[0]['descrizione']).'</p>
<br>';
}

$sconto = [];
$imponibile = [];
$iva = [];

// Intestazione tabella per righe
echo "
<table class='table table-striped' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Q.tà', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Costo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Imponibile', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('IVA', [], ['upper' => true]).' (%)</th>
        </tr>
    </thead>

    <tbody>';

// RIGHE PREVENTIVO CON ORDINAMENTO UNICO
$righe = $dbo->fetchArray("SELECT *, IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo),'') AS codice, (SELECT percentuale FROM co_iva WHERE id=idiva) AS perc_iva FROM `co_righe_preventivi` WHERE idpreventivo=".prepare($idpreventivo).' ORDER BY `order`');
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

    // Costo unitario
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

$totale = $imponibile - $sconto;
$iva = sum($iva);

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

    // TOTALE INTERVENTO
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

// CONDIZIONI GENERALI DI FORNITURA

// Lettura pagamenti
$query = 'SELECT * FROM co_pagamenti WHERE id = '.$records[0]['idpagamento'];
$rs = $dbo->fetchArray($query);
$pagamento = $rs[0]['descrizione'];

// Lettura resa
$query = 'SELECT * FROM dt_porto WHERE id = '.$records[0]['idporto'];
$rs = $dbo->fetchArray($query);
$resa_materiale = $rs[0]['descrizione'];

echo '
<table class="table table-bordered">
    <tr>
        <th colspan="2" class="text-center">
            '.tr('Condizioni generali di fornitura', [], ['upper' => true]).'
        </th>
    </tr>

    <tr>
        <th style="width:25%">
            '.tr('Pagamento', [], ['upper' => true]).'
        </th>

        <td>
            '.$pagamento.'
        </td>
    </tr>

    <tr>
        <th>
            '.tr('Resa materiale', [], ['upper' => true]).'
        </th>

        <td>
            '.$resa_materiale.'
        </td>
    </tr>

    <tr>
        <th>
            '.tr('Validità offerta', [], ['upper' => true]).'
        </th>

        <td>
            '.tr('_TOT_ giorni', [
                '_TOT_' => $records[0]['validita'],
            ]).' giorni
        </td>
    </tr>

    <tr>
        <th>
            '.tr('Tempi consegna', [], ['upper' => true]).'
        </th>

        <td>
            '.$records[0]['tempi_consegna'].'
        </td>
    </tr>

    <tr>
        <th>
            '.tr('Esclusioni', [], ['upper' => true]).'
        </th>

        <td>
            '.nl2br($records[0]['esclusioni']).'
        </td>
    </tr>
</table>';

// Conclusione
echo '
<p class="text-center">'.tr("In attesa di un Vostro Cortese riscontro, colgo l'occasione per porgere Cordiali Saluti").'</p>';
