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

if (!$is_last_page) {
    return;
}

$imponibile = 0;
foreach ($v_totale as $key => $v) {
    $totale_scontato += $v;
}

$sconto = 0;
foreach ($righe as $riga) {
    $sconto += floatval($riga->sconto);
}

$imponibile = $totale_scontato + $sconto;

$rivalsa = 0;
foreach ($righe as $riga) {
    $rivalsa += floatval($riga->rivalsainps);
}

$totale_imponibile = $totale_scontato + $rivalsa;

$totale_iva = 0;
foreach ($righe as $riga) {
    $aliquota = $database->fetchOne('SELECT percentuale FROM co_iva WHERE id = '.prepare($riga->idiva))['percentuale'];
    $totale_iva += $riga['iva'] + $riga['rivalsainps'] * $aliquota / 100;
}

$totale = $totale_iva + $totale_imponibile;

$show_sconto = $sconto > 0;

$volume = $documento->volume ?: $documento->volume_calcolato;
$peso_lordo = $documento->peso ?: $documento->peso_calcolato;

$width = round(100 / ($show_sconto ? 5 : 3), 2);

$has_rivalsa = !empty($rivalsa);
$has_ritenuta = !empty($record['ritenutaacconto']) || !empty($documento->totale_ritenuta_contributi);
$has_split_payment = !empty($record['split_payment']);
$has_sconto_finale = !empty($sconto_finale);

$etichette = [
    'totale' => tr('Totale imponibile', [], ['upper' => true]),
    'totale_parziale' => tr('Totale documento', [], ['upper' => true]),
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

                    <td style='width:80mm;'>
                        <table class='border-bottom'>
                            <tr>
                                <td colspan='4'>
                                    <p class='small-bold text-muted'>".tr('Scadenze pagamenti', [], ['upper' => true]).'</p>
                                </td>
                            </tr>';

// Elenco scadenze
$rs2 = $dbo->fetchArray('SELECT * FROM `co_scadenziario` WHERE `iddocumento`='.prepare($id_record).' ORDER BY `scadenza` ASC');
if (!empty($rs2)) {
    for ($i = 0; $i < sizeof($rs2); ++$i) {
        $pagamento = $dbo->fetchOne('SELECT `fe_modalita_pagamento_lang`.`title` as descrizione FROM `co_pagamenti` INNER JOIN `fe_modalita_pagamento` ON `fe_modalita_pagamento`.`codice` = `co_pagamenti`.`codice_modalita_pagamento_fe` LEFT JOIN `fe_modalita_pagamento_lang` ON (`fe_modalita_pagamento_lang`.`id_record`=`fe_modalita_pagamento`.`codice` AND `fe_modalita_pagamento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `co_pagamenti`.`id`='.$rs2[$i]['id_pagamento'])['descrizione'];
        echo '
                            <tr>
                                <td style=\'width:15%;\'>
                                    <small>'.Translator::dateToLocale($rs2[$i]['scadenza'])."</small>
                                </td>
                                <td style='width:15%;'>
                                    ".(($rs2[$i]['pagato'] == $rs2[$i]['da_pagare']) ? '<small>PAGATO</small>' : '')."
                                </td>
                                <td style='width:15%;'>
                                    <small>".moneyFormat($rs2[$i]['da_pagare'], $d_totali).'</small>
                                </td>
                                <td style=\'width:15%;\'>
                                    <small>'.$pagamento.'</small>
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
                                    <p class='small-bold text-muted'>".tr('Aliquota IVA', [], ['upper' => true])."</p>
                                </td>

                                <td style='width:20mm;' class='text-center'>
                                    <p class='small-bold text-muted'>".tr('Importo', [], ['upper' => true])."</p>
                                </td>

                                <td style='width:20mm;' class='text-center'>
                                    <p class='small-bold text-muted'>".tr('Importo IVA', [], ['upper' => true]).'</p>
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
                                    <small>".moneyFormat($v_totale[$desc_iva], $d_totali)."</small>
                                </td>

                                <td class='text-right'>
                                    <small>".moneyFormat($v_iva[$desc_iva], $d_totali).'</small>
                                </td>
                            </tr>';
        }
    }

    echo '
                        </table>
                        <br>
                        <table class="border-bottom">
                            <tr>
                                <td>
                                </td>
                                <td>
                                    <p><small>$appoggiobancario$</small></p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="small-bold text-muted">'.tr('IBAN').'</p>
                                </td>
                                <td>
                                    <p><small>$codiceiban$</small></p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="small-bold text-muted">'.tr('BIC').'</p>
                                </td>
                                <td>
                                    <p><small>$bic$</small></p>
                                </td>
                            </tr>
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
        </td>
        ';

/*
 * Riga di riepilogo dei totali.
 * Se sconto: Imponibile | Sconto | Totale imponibile | Totale IVA | Totale
 * Altrimenti: Imponibile | Totale IVA | Totale
 */
if ($has_ritenuta || $show_sconto || $has_rivalsa) {
    echo "
    <tr>
        <th class='text-center small' style='width:".$width."'>
            ".tr('Imponibile', [], ['upper' => true]).'
        </th>';
} else {
    echo "
    <tr>
        <th class='text-center small' style='width:".$width."'>
        </th>";
}
if ($show_sconto) {
    echo "
        <th class='text-center small' style='width:".$width."'>
            ".tr('Sconto', [], ['upper' => true])."
        </th>

        <th class='text-center small' style='width:".$width."'>
            ".tr('Totale scontato', [], ['upper' => true]).'
        </th>';
}
if ($has_rivalsa) {
    echo "
        <th class='text-center small' style='width:".$width."'>
            ".tr('Cassa Previdenziale', [], ['upper' => true]).'
        </th>';
} else {
    echo "
        <th class='text-center small' style='width:".$width."'>
        </th>";
}
echo "
        <th class='text-center small' style='width:".$width."'>
            ".(($show_sconto) ? $etichette['totale_parziale'] : $etichette['totale']).'
        </th>
    </tr>';

if ($has_ritenuta || $show_sconto || $has_rivalsa) {
    echo "
    <tr>
        <td class='cell-padded text-center'>
            ".moneyFormat($imponibile, $d_totali).'
        </td>';
} else {
    echo "
    <tr>
        <td class='cell-padded text-center'>
        </td>";
}

if ($show_sconto) {
    echo "
        <td class='cell-padded text-center'>
            ".moneyFormat(abs($sconto), $d_totali)."
        </td>
        <td class='cell-padded text-center'>
            ".moneyFormat($totale_scontato, $d_totali).'
        </td>';
} elseif (!$has_rivalsa) {
    echo "
        <td class='cell-padded text-center'>
        </td>";
}

/*
 * Riga di riepilogo della Rivalsa INPS.
 * Rivalsa INPS | Totale (+ Rivalsa INPS)
 */
if ($has_rivalsa) {
    $rs2 = $dbo->fetchArray('SELECT percentuale, descrizione FROM co_rivalse WHERE id IN (SELECT idrivalsainps FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idrivalsainps!=0)');

    foreach ($rs2 as $rs) {
        $descrizione .= '<p class="text-muted small-bold">'.$rs['descrizione'].'</p>';
    }

    echo '
        <td class="cell-padded text-center">
            '.moneyFormat($rivalsa, 2).'
           '.$descrizione.'
        </td>
        <td class="cell-padded text-center">
            '.moneyFormat($totale_imponibile, $d_totali).'
        </td>
    </tr>';
} elseif ($show_sconto) {
    echo '
        <td class="cell-padded text-center">
        </td>
        <td class="cell-padded text-center">
            '.moneyFormat($totale_imponibile, $d_totali).'
        </td>
    </tr>';
} else {
    echo '
    <td class="cell-padded text-center">
        '.moneyFormat($totale_imponibile, $d_totali).'
    </td>';
}

$first_colspan = 3;
$second_colspan = 2;

if (empty($sconto)) {
    --$first_colspan;
    --$second_colspan;
}

echo '
<tr>
    <th class="text-center small" colspan="'.$first_colspan.'">
        '.tr('Totale IVA', [], ['upper' => true]).'
    </th>

    <th class="text-center small" colspan="'.$second_colspan.'">
        '.(!$has_ritenuta && !$has_split_payment && !$has_sconto_finale ? $etichette['totale_finale'] : $etichette['totale_parziale']).'
    </th>
</tr>

<tr>
        <td class="cell-padded text-center" colspan="'.$first_colspan.'">
            '.moneyFormat($totale_iva, $d_totali).'
        </td>';
if ($has_ritenuta || $has_rivalsa || $has_split_payment || $has_sconto_finale) {
    echo '<td class="cell-padded text-center" colspan="'.$second_colspan.'">
            '.moneyFormat($totale, $d_totali);
} else {
    echo '
            <td class="cell-padded text-center" colspan="'.$second_colspan.'" style="background-color:#77dd77;">
            <b>'.moneyFormat($totale, $d_totali).'</b>';
}
echo '
        </td>
</tr>';

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

    $contributi = tr('_DESCRIZIONE_: _PRC_%', [
        '_DESCRIZIONE_' => $documento->ritenutaContributi->descrizione,
        '_PRC_' => Translator::numberToLocale($documento->ritenutaContributi->percentuale, 2),
    ]);
    $ritenuta_contributi_totale = abs($documento->totale_ritenuta_contributi);
    $acconto = tr('acconto: _PRC_%', [
        '_PRC_' => Translator::numberToLocale($rs2[0]['percentuale'], 2),
    ]);
    $ritenuta_acconto_totale = abs($documento->ritenuta_acconto);

    if (!empty($ritenuta_acconto_totale) && !empty($ritenuta_contributi_totale)) {
        --$first_colspan;
    }

    echo '
    <tr>';
    if (!empty($ritenuta_acconto_totale)) {
        echo '
        <th class="text-center small" colspan="'.$first_colspan.'">
            '.tr('Ritenuta _ACCONTO_', [
            '_ACCONTO_' => $acconto,
        ], ['upper' => true]).'
        </th>';
    }

    if (!empty($ritenuta_contributi_totale)) {
        echo '
        <th class="text-center small" colspan="'.$first_colspan.'">
            '.tr('_CONTRIBUTI_', [
            '_ACCONTO_' => $acconto,
            '_CONTRIBUTI_' => empty($documento->ritenutaContributi) ? null : $contributi,
        ], ['upper' => true]).'
        </th>';
    }

    echo '
        <th class="text-center small" colspan="'.$second_colspan.'">
            '.(!$has_split_payment && !$has_sconto_finale ? $etichette['totale_finale'] : $etichette['totale_parziale']).'
		</th>';

    echo '
	</tr>

    <tr>';
    if (!empty($ritenuta_acconto_totale)) {
        echo '
        <td class="cell-padded text-center" colspan="'.$first_colspan.'">
            '.moneyFormat($ritenuta_acconto_totale, 2).'
        </td>';
    }

    if (!empty($ritenuta_contributi_totale)) {
        echo '
        <td class="cell-padded text-center" colspan="'.$first_colspan.'">
            '.moneyFormat($ritenuta_contributi_totale, 2).'
        </td>';
    }

    $totale = $totale - ($ritenuta_acconto_totale + $ritenuta_contributi_totale);
    echo '

        <td class="cell-padded text-center" colspan="'.$second_colspan.'" style="background-color:#77dd77;">
            <b>'.moneyFormat($totale, 2).'</b>
        </td>
    </tr>';
}

/*
 * Riga di riepilogo per lo Split payment.
 * Totale IVA | Totale (+ Rivalsa INPS - Ritenuta - Totale IVA)
 */
if ($has_split_payment) {
    $first_colspan = 2;
    $second_colspan = 1;

    echo '
    <tr>
        <th class="text-center small" colspan="'.$first_colspan.'">
            '.tr('IVA a carico del destinatario', [], ['upper' => true]).'
        </th>

        <th class="text-center small" colspan="'.$second_colspan.'">
            '.(!$has_sconto_finale ? $etichette['totale_finale'] : $etichette['totale_parziale']).'
        </th>
    </tr>';

    $totale = $totale - $totale_iva;
    echo '
	 <tr>
        <td class="cell-padded text-center" colspan="'.$first_colspan.'">
            '.moneyFormat($totale_iva, 2).'
        </td>

        <td class="cell-padded text-center" colspan="'.$second_colspan.'" style="background-color:#77dd77;">
            <b>'.moneyFormat($totale, 2).'</b>
        </td>
    </tr>';
}

/*
 * Riga di riepilogo per lo sconto in fattura.
 * Sconto in | Totale (+ Rivalsa INPS - Ritenuta - Totale IVA [se split payment] - Sconto finale)
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
