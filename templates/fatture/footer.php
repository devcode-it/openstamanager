<?php

// SCADENZE  |  TOTALI
// TABELLA PRINCIPALE
echo "
<table>
    <tr>
        <td style='width:158.6mm;' class='border-top border-left'></td>
        <td style='width:33mm;' class='border-full'>
            <p class='small-bold'>".tr('Totale imponibile', [], ['upper' => true])."</p>
        </td>
    </tr>
    <tr>
        <td rowspan=10 class='border-right border-bottom border-left cell-padded'>";

// Tabella (scadenze + iva)
echo "
            <table>
                <tr>
                    <td style='width:45mm;'>
                        <table>
                            <tr>
                                <td colspan='2' class='border-bottom'>
                                    <p class='small-bold'>".tr('Scadenze pagamenti', [], ['upper' => true]).'</p>
                                </td>
                            </tr>';

// Elenco scadenze
$rs2 = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE iddocumento='.prepare($iddocumento).' ORDER BY `data_emissione` ASC');
if (!empty($rs2)) {
    for ($i = 0; $i < sizeof($rs2); ++$i) {
        echo "
                            <tr>
                                <td style='width:50%;' class='border-bottom'>
                                    <small>".Translator::dateToLocale($rs2[$i]['scadenza'])."</small>
                                </td>
                                <td style='width:50%;' align='right' class='border-bottom'>
                                    <small>".Translator::numberToLocale($rs2[$i]['da_pagare'], 2).' &euro;</small>
                                </td>
                            </tr>';
    }
} else {
    echo "
                            <tr>
                                <td style='width:50%;'>
                                    &nbsp;
                                </td>
                                <td style='width:50%;' align='right'>
                                    &nbsp;
                                </td>
                            </tr>";
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
                        <table>
                            <tr>
                                <td style='width:40mm;' class='border-bottom'>
                                    <p class='small-bold'>".tr('Aliquota IVA', [], ['upper' => true])."</p>
                                </td>

                                <td style='width:20mm;' class='border-bottom text-center'>
                                    <p class='small-bold'>".tr('Importo', [], ['upper' => true])."</p>
                                </td>

                                <td style='width:20mm;' class='border-bottom text-center'>
                                    <p class='small-bold'>".tr('Importo IVA', [], ['upper' => true]).'</p>
                                </td>
                            </tr>';

    foreach ($v_iva as $desc_iva => $tot_iva) {
        if (!empty($desc_iva)) {
            echo "
                            <tr>
                                <td style='' class='border-bottom'>
                                    <small>".$desc_iva."</small>
                                </td>

                                <td style='' align='right' class='border-bottom'>
                                    <small>".Translator::numberToLocale($v_totale[$desc_iva], 2)." &euro;</small>
                                </td>

                                <td style='' align='right' class='border-bottom'>
                                    <small>".Translator::numberToLocale($v_iva[$desc_iva], 2).' &euro;</small>
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

// TOTALE IMPONIBILE
echo "
        <td style='text-align:right;' class='border-bottom border-right cell-padded'>
            ".Translator::numberToLocale($imponibile_documento, 2).' &euro;
        </td>
    </tr>';

// Riga 2
echo "
    <tr>
        <td style='width:33mm;' class='border-bottom border-right'>
            <p class='small-bold'>".tr('Totale IVA', [], ['upper' => true])."</p>
        </td>
    </tr>

    <tr>
        <td style='text-align:right;' class='border-bottom border-right cell-padded'>
            ".Translator::numberToLocale($totale_iva, 2)." &euro;
        </td>
    </tr>

    <tr>
        <td class='border-bottom border-right'>
            <p class='small-bold'>".tr('Totale documento', [], ['upper' => true])."</p>
        </td>
    </tr>

    <tr>
        <td style='text-align:right;' class='border-bottom border-right cell-padded'>
            ".Translator::numberToLocale($totale_documento, 2).' &euro;
        </td>
    </tr>';

// Riga 4 (opzionale, solo se c'è la ritenuta d'acconto)
if ($rs[0]['ritenutaacconto'] != 0) {
    $rs2 = $dbo->fetchArray('SELECT percentuale FROM co_ritenutaacconto WHERE id=(SELECT idritenutaacconto FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento).' AND idritenutaacconto!=0 LIMIT 0,1)');

    echo "
    <tr>
        <td class='border-bottom border-right'>
            <p class='small-bold'>".tr("Ritenuta d'acconto _PRC_%", [
                '_PRC_' => $rs2[0]['percentuale'],
            ], ['upper' => true])."</p>
        </td>
    </tr>

    <tr>
        <td style='text-align:right;' class='border-bottom border-right cell-padded'>
            ".Translator::numberToLocale($rs[0]['ritenutaacconto'], 2)." &euro;
        </td>
    </tr>

    <tr>
        <td class='border-bottom border-right'>
            <p class='small-bold'>".tr('Netto a pagare', [], ['upper' => true])."</p>
        </td>
    </tr>

    <tr>
        <td style='text-align:right;' class='border-bottom border-right cell-padded'>
            ".Translator::numberToLocale($totale_documento - $rs[0]['ritenutaacconto'], 2).' &euro;
        </td>
    </tr>';
}

echo '
</table>';

echo '
<br>
<table style="font-size:7pt; color:#999;">
    <tr><td style="text-align:center;">
        $dicitura_fissa_fattura$
    </td></tr>
</table>

<br>

$pagination$';
