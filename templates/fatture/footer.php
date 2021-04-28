<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

if (!empty($options['last-page-footer']) && !$is_last_page) {
    return;
}

// Calcoli
$imponibile = abs($documento->imponibile);
$sconto = $documento->sconto;
$totale_imponibile = abs($documento->totale_imponibile);
$totale_iva = abs($documento->iva);
$sconto_finale = abs($documento->getScontoFinale());
$rivalsa = floatval($record['rivalsainps']);
$totale = abs($documento->totale) - $rivalsa;

$show_sconto = $sconto > 0;

$volume = $documento->volume ?: $documento->volume_calcolato;
$peso_lordo = $documento->peso ?: $documento->peso_calcolato;

$width = round(100 / ($show_sconto ? 5 : 3), 2);

$has_rivalsa = !empty($record['rivalsainps']);
$has_ritenuta = !empty($record['ritenutaacconto']) || !empty($documento->totale_ritenuta_contributi) || !empty($record['spit_payment']);
$has_split_payment = !empty($record['split_payment']);
$has_sconto_finale = !empty($sconto_finale);

$etichette = [
    'totale' => tr('Totale', [], ['upper' => true]),
    'totale_parziale' => tr('Totale parziale', [], ['upper' => true]),
    'totale_finale' => tr('Netto a pagare', [], ['upper' => true]),
];

// SCADENZE  |  TOTALI
// TABELLA PRINCIPALE
echo "
<table class='table-bordered'>
    <tr>
        <td colspan=".($show_sconto ? 5 : 3)." class='cell-padded' style='height:".($record['ritenutaacconto'] != 0 ? 20 : 30)."mm'>";

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

                    <td style="width:10mm;">&nbsp;</td>
                </tr>';
// Fine tabelle iva
echo '
            </table>';
// Fine tabella (scadenze + iva)
echo '
        </td>';

/*
 * Riga di riepilogo dei totali.
 * Se sconto: Imponibile | Sconto | Totale imponibile | Totale IVA | Totale
 * Altrimenti: Imponibile | Totale IVA | Totale
 */
echo "
    <tr>
        <th class='text-center small' style='width:".$width."'>
            ".tr('Imponibile', [], ['upper' => true]).'
        </th>';

if ($show_sconto) {
    echo "
        <th class='text-center small' style='width:".$width."'>
            ".tr('Sconto', [], ['upper' => true])."
        </th>

        <th class='text-center small' style='width:".$width."'>
            ".tr('Totale imponibile', [], ['upper' => true]).'
        </th>';
}

echo "
        <th class='text-center small' style='width:".$width."'>
            ".tr('Totale IVA', [], ['upper' => true])."
        </th>

        <th class='text-center small' style='width:".$width."'>
            ".(!$has_rivalsa && !$has_ritenuta && !$has_split_payment && !$has_sconto_finale ? $etichette['totale'] : $etichette['totale_parziale'])."
        </th>
    </tr>

    <tr>
        <td class='cell-padded text-center'>
            ".moneyFormat($show_sconto ? $imponibile : $totale_imponibile, 2).'
        </td>';

if ($show_sconto) {
    echo "

        <td class='cell-padded text-center'>
            ".moneyFormat(abs($sconto), 2)."
        </td>

        <td class='cell-padded text-center'>
            ".moneyFormat($totale_imponibile, 2).'
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

/*
 * Riga di riepilogo della Rivalsa INPS.
 * Rivalsa INPS | Totale (+ Rivalsa INPS)
 */
if ($has_rivalsa) {
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
            '.(!$has_ritenuta && !$has_split_payment && !$has_sconto_finale ? $etichette['totale_finale'] : $etichette['totale_parziale']).'
        </th>
    </tr>

    <tr>
        <td class="cell-padded text-center" colspan="'.$first_colspan.'">
            '.moneyFormat($rivalsa, 2).'
        </td>';

    $totale = $totale + $rivalsa;
    echo '

        <td class="cell-padded text-center" colspan="'.$second_colspan.'">
            '.moneyFormat($totale, 2).'
        </td>
    </tr>';
}

/*
 * Riga di riepilogo di Ritenuta d'acconto e Ritenuta contributi.
 * Ritenuta | Totale (+ Rivalsa INPS - Ritenuta)
 */
if ($has_ritenuta) {
    $rs2 = $dbo->fetchArray('SELECT percentuale FROM co_ritenutaacconto WHERE id=(SELECT idritenutaacconto FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idritenutaacconto!=0 LIMIT 0,1)');

    $first_colspan = 3;
    $second_colspan = 2;

    if (empty($sconto)) {
        --$first_colspan;
        --$second_colspan;
    }

    $contributi = (!empty($record['ritenutaacconto']) ? ' - ' : '').tr('contributi: _PRC_%', [
        '_PRC_' => Translator::numberToLocale($documento->ritenutaContributi->percentuale, 2),
    ]);
    $acconto = tr('acconto: _PRC_%', [
        '_PRC_' => Translator::numberToLocale($rs2[0]['percentuale'], 0),
    ]);
    $ritenuta_totale = abs($documento->ritenuta_acconto) + abs($documento->totale_ritenuta_contributi);

    echo '
    <tr>
        <th class="text-center small" colspan="'.$first_colspan.'">
            '.tr('Ritenuta (_ACCONTO__CONTRIBUTI_)', [
            '_ACCONTO_' => $acconto,
            '_CONTRIBUTI_' => empty($documento->ritenutaContributi) ? null : $contributi,
            ], ['upper' => true]).'
        </th>';

    echo '
        <th class="text-center small" colspan="'.$second_colspan.'">
            '.(!$has_split_payment && !$has_sconto_finale ? $etichette['totale_finale'] : $etichette['totale_parziale']).'
		</th>';

    echo '
	</tr>

    <tr>
        <td class="cell-padded text-center" colspan="'.$first_colspan.'">
            '.moneyFormat($ritenuta_totale, 2).'
        </td>';

    $totale = $totale - $ritenuta_totale;
    echo '

        <td class="cell-padded text-center" colspan="'.$second_colspan.'">
            '.moneyFormat($totale, 2).'
        </td>
    </tr>';
}

/*
 * Riga di riepilogo per lo Split payment.
 * Totale IVA | Totale (+ Rivalsa INPS - Ritenuta - Totale IVA)
 */
if ($has_split_payment) {
    $first_colspan = 1;
    $second_colspan = 2;

    echo '
    <tr>
        <th class="text-center small" colspan="'.$first_colspan.'">
            '.tr('IVA a carico del destinatario', [], ['upper' => true]).'
        </th>

        <th class="text-center small" colspan="'.$second_colspan.'">
            '.(!$has_split_payment && !$has_sconto_finale ? $etichette['totale_finale'] : $etichette['totale_parziale']).'
        </th>
    </tr>';

    $totale = $totale - $totale_iva;
    echo '
	 <tr>
        <td class="cell-padded text-center" colspan="'.$first_colspan.'">
            '.moneyFormat($totale_iva, 2).'
        </td>

        <td class="cell-padded text-center" colspan="'.$second_colspan.'">
            '.moneyFormat($totale, 2).'
        </td>
    </tr>';
}

/*
 * Riga di riepilogo per lo Sconto finale sulla fattura.
 * Sconto finale | Totale (+ Rivalsa INPS - Ritenuta - Totale IVA [se split payment] - Sconto finale)
 */
if ($has_sconto_finale) {
    $first_colspan = 1;
    $second_colspan = 2;

    echo '
    <tr>
        <th class="text-center small" colspan="'.$first_colspan.'">
            '.tr('Sconto in fattura', [], ['upper' => true]).($documento->sconto_finale_percentuale ? ' ('.numberFormat($documento->sconto_finale_percentuale, 2).'%)' : '').'
        </th>

        <th class="text-center small" colspan="'.$second_colspan.'">
            '.tr('Netto a pagare', [], ['upper' => true]).'
        </th>
    </tr>';

    $totale = $totale - $sconto_finale;
    echo '
	 <tr>
        <td class="cell-padded text-center" colspan="'.$first_colspan.'">
            '.moneyFormat($sconto_finale, 2).'
        </td>

        <td class="cell-padded text-center" colspan="'.$second_colspan.'">
            '.moneyFormat($totale, 2).'
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
         <th class="small" class style="width:15%">
            '.tr('Peso lordo', [], ['upper' => true]).'
        </th>

         <th class="small" class style="width:15%">
            '.tr('Volume', [], ['upper' => true]).'
        </th>

        <th class="small" class style="width:15%">
            '.tr('Aspetto beni', [], ['upper' => true]).'
        </th>

        <th class="small" class style="width:10%">
            '.tr('Colli', [], ['upper' => true]).'
        </th>

        <th class="small" style="width:30%">
            '.tr('Causale trasporto', [], ['upper' => true]).'
        </th>

        <th class="small" style="width:15%">
            '.tr('Porto', [], ['upper' => true]).'
        </th>
    </tr>

    <tr>
        <td class="cell-padded">
        '.(!empty($peso_lordo) ? Translator::numberToLocale($peso_lordo).'&nbsp;KG' : '').'
        </td>

        <td class="cell-padded">
            '.(!empty($volume) ? Translator::numberToLocale($volume).'&nbsp;M<sup>3</sup>' : '').'
        </td>

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
<div style="font-size: 6pt; text-align: left;" class="text-muted">
    <span>$dicitura_fissa_fattura$</span>
</div>';

if (empty($options['last-page-footer'])) {
    echo '$default_footer$';
}
