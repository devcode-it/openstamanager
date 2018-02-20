<?php

include_once __DIR__.'/../../core.php';

$report_name = 'contratto_'.$idcontratto.'.pdf';

$autofill = [
    'count' => 0, // Conteggio delle righe
    'words' => 70, // Numero di parolo dopo cui contare una riga nuova
    'rows' => 20, // Numero di righe massimo presente nella pagina
    'additional' => 10, // Numero di righe massimo da aggiungere
    'columns' => 4, // Numero di colonne della tabella
];

echo '
<div class="row">
    <div class="col-xs-6">
        <div class="text-center" style="height:5mm;">
            <b>'.tr('Contratto num. _NUM_ del _DATE_', [
                '_NUM_' => $records[0]['numero'],
                '_DATE_' => Translator::dateToLocale($records[0]['data_bozza']),
            ], ['upper' => true]).'</b>
        </div>
    </div>

    <div class="col-xs-5 col-xs-offset-1">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td colspan=2 class="border-full" style="height:16mm;">
                    <p class="small-bold">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
                    <p>$c_indirizzo$ $c_citta_full$</p>
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
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Q.tà', [], ['upper' => true])."</th>
            <th class='text-center' style='width:20%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:20%'>".tr('Imponibile', [], ['upper' => true]).'</th>
        </tr>
    </thead>

    <tbody>';

// RIGHE PREVENTIVO CON ORDINAMENTO UNICO
$righe = $dbo->fetchArray('SELECT * FROM co_righe2_contratti WHERE idcontratto='.prepare($idcontratto).' ORDER BY `order`');
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
            <td class='text-center'>";
    if($r['is_descrizione']==0){
        echo
                (empty($r['qta']) ? '' : Translator::numberToLocale($r['qta'])).' '.$r['um'];
    }
    echo '
            </td>';

    if ($mostra_prezzi) {
        // Prezzo unitario
        echo "
            <td class='text-right'>";
        if($r['is_descrizione']==0){
            echo
                (empty($r['qta']) || empty($r['subtotale']) ? '' : Translator::numberToLocale($r['subtotale'] / $r['qta'])).' &euro;';
        }
        echo '
            </td>';

        // Imponibile
        echo "
            <td class='text-right'>";
        if($r['is_descrizione']==0){
            echo
                (empty($r['subtotale']) ? '' : Translator::numberToLocale($r['subtotale'])).' &euro;';

            if ($r['sconto'] > 0) {
                echo '
                    <br><small class="help-block">- '.tr('sconto _TOT_ _TYPE_', [
                        '_TOT_' => Translator::numberToLocale($r['sconto_unitario']),
                        '_TYPE_' => ($r['tipo_sconto'] == 'PRC' ? '%' : '&euro;'),
                    ]).'</small>';

                if ($count <= 1) {
                    $count += 0.4;
                }
            }
        }
        echo '
            </td>';
    } else {
        echo '
            <td class="text-center">-</td>
            <td class="text-center">-</td>';
    }

    echo '
        </tr>';

    $autofill['count'] += $count;

    $sconto[] = $r['sconto'];
    $imponibile[] = $r['subtotale'];
}

$sconto = sum($sconto);
$imponibile = sum($imponibile);

$totale = $imponibile - $sconto;

echo '
        |autofill|
    </tbody>';

// TOTALE COSTI FINALI
if ($mostra_prezzi) {
    // Eventuale sconto incondizionato
    if (!empty($sconto)) {
        // Totale imponibile
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>'.Translator::numberToLocale($imponibile).' &euro;</b>
        </th>
    </tr>';

        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>-'.Translator::numberToLocale($sconto).' &euro;</b>
        </th>
    </tr>';
    }

    // TOTALE
    echo '
    <tr>
    	<td colspan="3" class="text-right border-top">
            <b>'.tr('Quotazione totale', [], ['upper' => true]).':</b>
    	</td>
    	<th class="text-center">
    		<b>'.Translator::numberToLocale($totale).' &euro;</b>
    	</th>
    </tr>';
}
echo'
</table>';

// CONDIZIONI GENERALI DI FORNITURA

// Lettura pagamenti
$rs = $dbo->fetchArray('SELECT * FROM co_pagamenti WHERE id = '.$records[0]['idpagamento']);
$pagamento = $rs[0]['descrizione'];

echo '
<table class="table table-bordered">
    <tr>
        <th colspan="2" class="text-center" style="font-size:13pt;">
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
            '.tr('Validità offerta', [], ['upper' => true]).'
        </th>

        <td>';

        if (!empty($records[0]['validita'])) {
            echo'
            '.tr('_TOT_ giorni', [
                '_TOT_' => $records[0]['validita'],
            ]);
        } else {
            echo '-';
        }

        echo '
        </td>
    </tr>

    <tr>
        <th>
            '.tr('Validità contratto', [], ['upper' => true]).'
        </th>

        <td>';

        if (!empty($records[0]['data_accettazione']) && !empty($records[0]['data_conclusione'])) {
            echo '
            '.tr('dal _START_ al _END_', [
                '_START_' => Translator::dateToLocale($records[0]['data_accettazione']),
                '_END_' => Translator::dateToLocale($records[0]['data_conclusione']),
            ]);
        } else {
            echo '-';
        }

        echo '
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
<p class="text-center"><b>'.tr('Il tutto S.E. & O.').'</b></p>
<p class="text-center">'.tr("In attesa di un Vostro Cortese riscontro, colgo l'occasione per porgere Cordiali Saluti").'</p>';


//Firma
echo '<div style=\'position:absolute; bottom:'.($settings['margins']['bottom'] + $settings['footer-height']).'px\' > <table >
    <tr>
        <td style="vertical-align:bottom;" width="50%">
            lì, ___________________________
        </td>

        <td align="center" style="vertical-align:bottom;" width="50%">
            FIRMA PER ACCETTAZIONE<br><br>
            _____________________________________
        </td>
    </tr>
</table>
<br></div>';
