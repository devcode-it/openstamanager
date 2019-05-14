<?php

// SCADENZE  |  TOTALI
// TABELLA PRINCIPALE
echo "
<table class='table-bordered'>
    <tr>
        <td colspan=".(!empty($sconto) ? 5 : 3)." class='cell-padded' style='height:".($record['ritenutaacconto'] != 0 ? 20 : 30)."mm'>";

// Tabella (scadenze + iva)
echo "
            <table class='table-normal'>
                <tr>
                    <td style='width:10mm;'>&nbsp;</td>

                    <td style='width:45mm;'>
                        <table class='border-bottom'>
                            <tr>
                                <td colspan='2'>
                                    <p class='small-bold'>".tr('Scadenze pagamenti', [], ['upper' => true]).'</p>
                                </td>
                            </tr>';

// Elenco scadenze
$rs2 = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE iddocumento='.prepare($id_record).' ORDER BY `data_emissione` ASC');
if (!empty($rs2)) {
    for ($i = 0; $i < sizeof($rs2); ++$i) {
        echo "
                            <tr>
                                <td style='width:50%;'>
                                    <small>".Translator::dateToLocale($rs2[$i]['scadenza'])."</small>
                                </td>
                                <td style='width:50%;' class='text-right'>
                                    <small>".moneyFormat($rs2[$i]['da_pagare'], 2).'</small>
                                </td>
                            </tr>';
    }
}

echo '
                        </table>
                    </td>';
// Fine elenco scadenze

// Separatore
echo "
                    <td style='width:10mm;'>&nbsp;</td>";

// Tabella iva
echo "
                    <td style='width:75mm;'>";
if (!empty($v_iva)) {
    echo "
                        <table class='border-bottom'>
                            <tr>
                                <td style='width:40mm;'>
                                    <p class='small-bold'>".tr('Aliquota IVA', [], ['upper' => true])."</p>
                                </td>

                                <td style='width:20mm;' class='text-center'>
                                    <p class='small-bold'>".tr('Importo', [], ['upper' => true])."</p>
                                </td>

                                <td style='width:20mm;' class='text-center'>
                                    <p class='small-bold'>".tr('Importo IVA', [], ['upper' => true]).'</p>
                                </td>
                            </tr>';

    foreach ($v_iva as $desc_iva => $tot_iva) {
        if (!empty($desc_iva)) {
            echo '
                            <tr>
                                <td>
                                    <small>'.$desc_iva."</small>
                                </td>

                                <td class='text-right'>
                                    <small>".moneyFormat($v_totale[$desc_iva], 2)."</small>
                                </td>

                                <td class='text-right'>
                                    <small>".moneyFormat($v_iva[$desc_iva], 2).'</small>
                                </td>
                            </tr>';
        }
    }

    echo '
                        </table>';
}

echo '
                    </td>

                    <td style="width:10mm;">&nbsp;</td>";
                </tr>';
// Fine tabelle iva
echo '
            </table>';
// Fine tabella (scadenze + iva)
echo '
        </td>';

// TOTALI
$width = round(100 / (!empty($sconto) ? 5 : 3), 2);
echo "
    <tr>
        <th class='text-center small' style='width:".$width."'>
            ".tr('Imponibile', [], ['upper' => true]).'
        </th>';

if (!empty($sconto)) {
    echo "
        <th class='text-center small' style='width:".$width."'>
            ".tr('Sconto', [], ['upper' => true])."
        </th>

        <th class='text-center small' style='width:".$width."'>
            ".tr('Imponibile scontato', [], ['upper' => true]).'
        </th>';
}

echo "
        <th class='text-center small' style='width:".$width."'>
            ".tr('Totale IVA', [], ['upper' => true])."
        </th>

        <th class='text-center small' style='width:".$width."'>
            ".tr('Totale documento', [], ['upper' => true])."
        </th>
    </tr>

    <tr>
        <td class='cell-padded text-center'>
            ".moneyFormat($imponibile, 2).'
        </td>';

if (!empty($sconto)) {
    echo "

        <td class='cell-padded text-center'>
            ".moneyFormat($sconto, 2)."
        </td>

        <td class='cell-padded text-center'>
            ".moneyFormat($imponibile - $sconto, 2).'
        </td>';
}

echo "
        <td class='cell-padded text-center'>
            ".moneyFormat($totale_iva, 2)."
        </td>

        <td class='cell-padded text-center'>
            ".moneyFormat($totale, 2).'
        </td>
    </tr>';

// Rivalsa INPS
if (!empty($record['rivalsainps'])) {
    $rs2 = $dbo->fetchArray('SELECT percentuale FROM co_rivalse WHERE id=(SELECT idrivalsainps FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idrivalsainps!=0 LIMIT 0,1)');

    $first_colspan = 3;
    $second_colspan = 2;

    if (empty($sconto)) {
        --$first_colspan;
        --$second_colspan;
    }

    echo '
    <tr>
        <th class="text-center small" colspan="'.$first_colspan.'">
            '.tr('Rivalsa _PRC_%', [
                '_PRC_' => Translator::numberToLocale($rs2[0]['percentuale'], 0),
            ], ['upper' => true]).'
        </th>';

    echo '

        <th class="text-center small" colspan="'.$second_colspan.'">
            '.tr('Totale documento', [], ['upper' => true]).'
        </th>
    </tr>

    <tr>
        <td class="cell-padded text-center" colspan="'.$first_colspan.'">
            '.moneyFormat($record['rivalsainps'], 2).'
        </td>';

    echo '

        <td class="cell-padded text-center" colspan="'.$second_colspan.'">
            '.moneyFormat($totale, 2).'
        </td>
    </tr>';
}

$fattura = \Modules\Fatture\Fattura::find($id_record);

// Ritenuta d'acconto ( + se no rivalsa inps)
if (!empty($record['ritenutaacconto']) || !empty($fattura->totale_ritenuta_contributi) || !empty($record['spit_payment'])) {
    $rs2 = $dbo->fetchArray('SELECT percentuale FROM co_ritenutaacconto WHERE id=(SELECT idritenutaacconto FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idritenutaacconto!=0 LIMIT 0,1)');

    $first_colspan = 3;
    $second_colspan = 2;

    if (empty($sconto)) {
        --$first_colspan;
        --$second_colspan;
    }

    $contributi = (!empty($record['ritenutaacconto']) ? ' - ' : '').tr('contributi: _PRC_%', [
        '_PRC_' => Translator::numberToLocale($fattura->ritenutaContributi->percentuale, 2),
    ]);
    $acconto = tr('acconto: _PRC_%', [
        '_PRC_' => Translator::numberToLocale($rs2[0]['percentuale'], 0),
    ]);

    echo '
    <tr>
        <th class="text-center small" colspan="'.$first_colspan.'">
            '.tr('Ritenuta (_ACCONTO__CONTRIBUTI_)', [
            '_ACCONTO_' => $acconto,
            '_CONTRIBUTI_' => empty($fattura->ritenutaContributi) ? null : $contributi,
            ], ['upper' => true]).'
        </th>';

    echo '
        <th class="text-center small" colspan="'.$second_colspan.'">';
    if (empty($record['split_payment'])) {
        echo   tr('Netto a pagare', [], ['upper' => true]);
    } else {
        echo   tr('Totale', [], ['upper' => true]);
    }
    echo '
		</th>';

    echo'
	</tr>

    <tr>
        <td class="cell-padded text-center" colspan="'.$first_colspan.'">
            '.moneyFormat($record['ritenutaacconto'] + $fattura->totale_ritenuta_contributi, 2).'
        </td>';

    echo '

        <td class="cell-padded text-center" colspan="'.$second_colspan.'">
            '.moneyFormat($totale - $record['ritenutaacconto'] - $fattura->totale_ritenuta_contributi, 2).'
        </td>
    </tr>';
}

// Split payment
if (!empty($record['split_payment'])) {
    $first_colspan = 1;
    $second_colspan = 2;

    echo '
    <tr>
        <th class="text-center small" colspan="'.$first_colspan.'">
            '.tr('iva a carico del destinatario', [], ['upper' => true]).'
        </th>

        <th class="text-center small" colspan="'.$second_colspan.'">
            '.tr('Netto a pagare', [], ['upper' => true]).'
        </th>
    </tr>';

    echo '
	 <tr>
        <td class="cell-padded text-center" colspan="'.$first_colspan.'">
        '.moneyFormat($totale_iva, 2).'
        </td>

        <td class="cell-padded text-center" colspan="'.$second_colspan.'">
            '.moneyFormat($totale - $totale_iva - $record['ritenutaacconto'] - $fattura->totale_ritenuta_contributi, 2).'
        </td>
    </tr>';
}

echo '
</table>';

if ($fattura_accompagnatoria) {
    // Informazioni aggiuntive
    echo '
<table class="table-bordered">
    <tr>
        <th class="small" class style="width:25%">
            '.tr('Aspetto beni', [], ['upper' => true]).'
        </th>

        <th class="small" class style="width:20%">
            '.tr('Num. colli', [], ['upper' => true]).'
        </th>

        <th class="small" style="width:30%">
            '.tr('Causale trasporto', [], ['upper' => true]).'
        </th>

        <th class="small" style="width:25%">
            '.tr('Porto', [], ['upper' => true]).'
        </th>
    </tr>

    <tr>
        <td class="cell-padded">
            $aspettobeni$ &nbsp;
        </td>

        <td class="cell-padded">
            $n_colli$ &nbsp;
        </td>

        <td class="cell-padded">
            $causalet$ &nbsp;
        </td>

        <td class="cell-padded">
            $porto$ &nbsp;
        </td>
    </tr>
</table>';

    // Firme
    echo '
<table class="table-bordered">
    <tr>
        <th class="small" style="width:33%">
            '.tr('Tipo di spedizione', [], ['upper' => true]).'
        </th>

        <th class="small" style="width:33%">
            '.tr('Firma conducente', [], ['upper' => true]).'
        </th>

        <th class="small" style="width:33%">
            '.tr('Firma destinatario', [], ['upper' => true]).'
        </th>
    </tr>

    <tr>
        <td style="height: 10mm">$spedizione$ $vettore$</td>
        <td style="height: 10mm"></td>
        <td style="height: 10mm"></td>
    </tr>
</table>';
}

echo '
<table style="font-size:7pt; color:#999;">
    <tr>
        <td style="text-align:center;">
            $dicitura_fissa_fattura$
        </td>
    </tr>
</table>';

if ($options['hide_footer']) {
    echo '
	<table style="color:#aaa; font-size:10px;">
	<tr>
		<td align="left" style="width:97mm; height:5mm;">
			&nbsp;
		</td>
		<td align="right" style="width:97mm;">
			&nbsp;
		</td>
	</tr>
	</table>';
} else {
    echo '$default_footer$';
}
