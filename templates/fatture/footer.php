<?php

// SCADENZE  |  TOTALI
// TABELLA PRINCIPALE
echo "
<table class='table-bordered'>
    <tr>
        <td colspan=".(!empty($sconto) ? 5 : 3)." class='cell-padded' style='height:".($records[0]['ritenutaacconto'] != 0 ? 20 : 30)."mm'>";

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
                                    <small>".Translator::numberToLocale($rs2[$i]['da_pagare']).' &euro;</small>
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
                                    <small>".Translator::numberToLocale($v_totale[$desc_iva])." &euro;</small>
                                </td>

                                <td class='text-right'>
                                    <small>".Translator::numberToLocale($v_iva[$desc_iva]).' &euro;</small>
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
$width = round(100/(!empty($sconto) ? 5 : 3), 2);
echo "
    <tr>
        <th class='text-center small' style='width:".$width."'>
            ".tr('Imponibile', [], ['upper' => true])."
        </th>";

if (!empty($sconto)) {
        echo "
        <th class='text-center small' style='width:".$width."'>
            ".tr('Sconto', [], ['upper' => true])."
        </th>

        <th class='text-center small' style='width:".$width."'>
            ".tr('Imponibile scontato', [], ['upper' => true])."
        </th>";
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
            ".Translator::numberToLocale($imponibile)." &euro;
        </td>";

if (!empty($sconto)) {
    echo "

        <td class='cell-padded text-center'>
            ".Translator::numberToLocale($sconto)." &euro;
        </td>

        <td class='cell-padded text-center'>
            ".Translator::numberToLocale($imponibile - $sconto)." &euro;
        </td>";
}

    echo "
        <td class='cell-padded text-center'>
            ".Translator::numberToLocale($iva)." &euro;
        </td>

        <td class='cell-padded text-center'>
            ".Translator::numberToLocale($totale).' &euro;
        </td>
    </tr>';

// Ritenuta d'acconto
if ($records[0]['ritenutaacconto'] != 0) {
    $rs2 = $dbo->fetchArray('SELECT percentuale FROM co_ritenutaacconto WHERE id=(SELECT idritenutaacconto FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idritenutaacconto!=0 LIMIT 0,1)');

    echo "
    <tr>
        <th class='text-center small' colspan=".(!empty($sconto) ? 3 : 2).">
            ".tr("Ritenuta d'acconto _PRC_%", [
                '_PRC_' => Translator::numberToLocale($rs2[0]['percentuale'], 0),
            ], ['upper' => true])."
        </th>

        <th class='text-center small' colspan=".(!empty($sconto) ? 2 : 1).">
            ".tr('Netto a pagare', [], ['upper' => true])."
        </th>
    </tr>

    <tr>
        <td class='cell-padded text-center' colspan=".(!empty($sconto) ? 3 : 2).">
            ".Translator::numberToLocale($records[0]['ritenutaacconto'])." &euro;
        </td>

        <td class='cell-padded text-center' colspan=".(!empty($sconto) ? 2 : 1).">
            ".Translator::numberToLocale($totale - $records[0]['ritenutaacconto']).' &euro;
        </td>
    </tr>';
}

echo '
</table>';

echo '
<table style="font-size:7pt; color:#999;">
    <tr>
        <td style="text-align:center;">
            $dicitura_fissa_fattura$
        </td>
    </tr>
</table>

$default_footer$';
