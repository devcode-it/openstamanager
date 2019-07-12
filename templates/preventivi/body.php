<?php

include_once __DIR__.'/../../core.php';

// Creazione righe fantasma
$autofill = new \Util\Autofill($options['pricing'] ? 5 : 2);
$autofill->setRows(20, 10);

echo '
<div class="row">
    <div class="col-xs-6">
        <div class="text-center" style="height:5mm;">
            <b>'.tr('Preventivo num. _NUM_ del _DATE_', [
                '_NUM_' => $documento['numero'],
                '_DATE_' => Translator::dateToLocale($documento['data_bozza']),
            ], ['upper' => true]).'</b>
        </div>
    </div>

    <div class="col-xs-5 col-xs-offset-1">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td colspan=2 class="border-full" style="height:16mm;">
                    <p class="small-bold">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
                    <p>$c_indirizzo$</p>
                    <p>$c_citta_full$</p>
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
if (!empty($documento['descrizione'])) {
    echo '
<p>'.nl2br($documento['descrizione']).'</p>
<br>';
}

// Intestazione tabella per righe
echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
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
    </tbody>';

// Calcoli
$imponibile = $documento->imponibile;
$sconto = $documento->sconto;
$totale_imponibile = $documento->totale_imponibile;
$totale_iva = $documento->iva;
$totale = $documento->totale;

$show_sconto = $sconto > 0;

// TOTALE COSTI FINALI
if ($options['pricing'] and !isset($options['hide_total'])) {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="3" class="text-right border-top">
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
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($sconto, 2).'</b>
        </th>
    </tr>';

        // Totale imponibile
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
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
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Totale IVA', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($totale_iva, 2).'</b>
        </th>
    </tr>';

    // TOTALE
    echo '
    <tr>
    	<td colspan="3" class="text-right border-top">
            <b>'.tr('Totale documento', [], ['upper' => true]).':</b>
    	</td>
    	<th colspan="2" class="text-right">
    		<b>'.moneyFormat($totale, 2).'</b>
    	</th>
    </tr>';
}

echo'
</table>';

// CONDIZIONI GENERALI DI FORNITURA
$pagamento = $dbo->fetchOne('SELECT * FROM co_pagamenti WHERE id = '.$documento['idpagamento']);

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
            '.$pagamento['descrizione'].'
        </td>
    </tr>

    <tr>
        <th>
            '.tr('Validità offerta', [], ['upper' => true]).'
        </th>

        <td>';

        if (!empty($documento['validita'])) {
            echo'
            '.tr('_TOT_ giorni', [
                '_TOT_' => $documento['validita'],
            ]);
        } else {
            echo '-';
        }

        echo '
        </td>
    </tr>

    <tr>
        <th>
            '.tr('Tempi consegna', [], ['upper' => true]).'
        </th>

        <td>
            '.$documento['tempi_consegna'].'
        </td>
    </tr>

    <tr>
        <th>
            '.tr('Esclusioni', [], ['upper' => true]).'
        </th>

        <td>
            '.nl2br($documento['esclusioni']).'
        </td>
    </tr>
</table>';

// Conclusione
echo '
<p class="text-center">'.tr("In attesa di un Vostro Cortese riscontro, colgo l'occasione per porgere Cordiali Saluti").'</p>';

// Firma
echo '
<div style="position:absolute; bottom:'.($settings['margins']['bottom'] + $settings['footer-height']).'px">
    <table>
        <tr>
            <td style="vertical-align:bottom;" width="50%">
                '.tr('lì').', ___________________________
            </td>
    
            <td align="center" style="vertical-align:bottom;" width="50%">
                '.tr('Firma per accettazione', [], ['upper' => true]).'<br><br>
                _____________________________________________
            </td>
        </tr>
    </table>
<br></div>';
