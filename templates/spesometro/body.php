<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

include_once __DIR__.'/../../core.php';

$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

// Intestazione tabella per righe
echo "
<h3 class='text-bold'>".tr('Spesometro dal _START_ al _END_', [
    '_START_' => Translator::dateToLocale($date_start),
    '_END_' => Translator::dateToLocale($date_end),
], ['upper' => true])."</h3>

<table class='table table-bordered'>
    <thead>
        <tr>
            <th class='text-center' style='width:10%'>".tr('Ragione sociale', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('P.Iva', [], ['upper' => true])."</th>
            <th class='text-center' style='width:25%'>".tr('Documento', [], ['upper' => true])."</th>
            <th class='text-center' style='width:20%'>".tr('Aliquota', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Imponibile', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('IVA', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Totale', [], ['upper' => true]).'</th>
        </tr>
    </thead>

    <tbody>';

$imponibile = [];
$iva = [];
$totale = [];

$anagrafiche = $dbo->fetchArray('SELECT idanagrafica, piva, ragione_sociale FROM `an_anagrafiche` WHERE `idanagrafica` IN (SELECT DISTINCT `idanagrafica` FROM `co_documenti` WHERE co_documenti.data>='.prepare($date_start).' AND co_documenti.data<='.prepare($date_end).' AND `co_documenti`.`id` IN (SELECT `iddocumento` FROM co_movimenti WHERE primanota = 1)) ORDER BY `ragione_sociale`');

foreach ($anagrafiche as $i => $anagrafica) {
    $fatture = $dbo->fetchArray('SELECT `co_documenti`.*, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`idanagrafica` = '.prepare($anagrafica['idanagrafica']).' AND `co_documenti`.`id` IN (SELECT `iddocumento` FROM co_movimenti WHERE primanota = 1) AND co_documenti.data>='.prepare($date_start).' AND co_documenti.data<='.prepare($date_end).' ORDER BY `data`');

    $num = 0;
    foreach ($fatture as $key => $fattura) {
        $righe = $dbo->fetchArray('SELECT `idiva`, `desc_iva`, SUM(subtotale) - SUM(sconto) AS imponibile, SUM(iva) AS iva, SUM(subtotale) - SUM(sconto) + SUM(iva) AS totale FROM `co_righe_documenti` WHERE iddocumento='.prepare($fattura['id']).' GROUP BY `idiva`, `desc_iva` ORDER BY `idiva`');

        $fatture[$key]['righe'] = $righe;
        $num += count($righe);
    }

    $extra = ($i % 2) != 0 ? ' class="row-bg"' : '';

    if ($num > 0) {
        echo '
        <tr'.$extra.'>
            <td rowspan="'.$num.'">
                '.$anagrafica['ragione_sociale'].'
            </td>';

        // Partita IVA
        echo '
            <td rowspan="'.$num.'">
                '.$anagrafica['piva'].'
            </td>';

        $count = 0;
        foreach ($fatture as $fattura) {
            $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
                '_DOC_' => $fattura['tipo_documento'],
                '_NUM_' => !empty($fattura['numero_esterno']) ? $fattura['numero_esterno'] : $fattura['numero'],
                '_DATE_' => Translator::dateToLocale($fattura['data']),
            ]);

            // Documenti replicati per IVA
            foreach ($fattura['righe'] as $riga) {
                if ($count != 0) {
                    echo '
        <tr'.$extra.'>';
                }
                ++$count;

                // Documento
                echo '
            <td>
                '.$descrizione.'
            </td>';

                // Descrizione IVA
                echo '
            <td>
                '.$riga['desc_iva'].'
            </td>';

                // Imponible
                echo '
            <td class="text-center">
                '.moneyFormat($riga['imponibile']).'
            </td>';

                // IVA
                echo '
            <td class="text-center">
                '.moneyFormat($riga['iva']).'
            </td>';

                // Totale
                echo '
            <td class="text-center">
                '.moneyFormat($riga['totale']).'
            </td>
        </tr>';

                if (empty($iva[$riga['desc_iva']])) {
                    $iva[$riga['desc_iva']] = [];
                }

                $imponibile[] = $riga['imponibile'];
                $iva[$riga['desc_iva']][] = $riga['iva'];
                $totale[] = $riga['totale'];
            }
        }
    }
}

echo '
    </tbody>';

// Totale imponibile
echo '
    <tr>
        <td colspan="4" class="text-right">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="3" class="text-center">
            <b>'.moneyFormat(sum($imponibile)).'</b>
        </th>
    </tr>';

foreach ($iva as $desc => $values) {
    $sum = sum($values);
    // Totale IVA
    echo '
<tr>
    <td colspan="4" class="text-right">
        <b>'.tr('IVA "_TYPE_"', [
            '_TYPE_' => $desc,
        ], ['upper' => true]).':</b>
    </td>

    <th colspan="3" class="text-center">
        <b>'.moneyFormat($sum).'</b>
    </th>
</tr>';

    $totale_iva += $sum;
}

// Totale IVA
echo '
    <tr>
        <td colspan="4" class="text-right">
            <b>'.tr('Totale IVA', [], ['upper' => true]).':</b>
        </td>

        <th colspan="3" class="text-center">
            <b>'.moneyFormat($totale_iva).'</b>
        </th>
    </tr>';

// TOTALE
echo '
    <tr>
    	<td colspan="4" class="text-right">
            <b>'.tr('Totale', [], ['upper' => true]).':</b>
    	</td>
    	<th colspan="3" class="text-center">
    		<b>'.moneyFormat(sum($totale)).'</b>
    	</th>
    </tr>';

echo '
</table>';
