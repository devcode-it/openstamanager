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
$imponibile = $documento->imponibile;
$sconto = $documento->sconto;
$totale_imponibile = $documento->totale_imponibile;
$totale_iva = $documento->iva;
$totale = $documento->totale;
$sconto_finale = $documento->getScontoFinale();
$netto_a_pagare = $documento->netto;

$volume = $documento->volume ?: $documento->volume_calcolato;
$peso_lordo = $documento->peso ?: $documento->peso_calcolato;

// TABELLA PRINCIPALE
echo '
<table class="table table-striped table-bordered">';

if ($options['pricing']) {
    // Riga 1
    echo "
    <tr>
        <td rowspan='10'>
            <p class='small-bold text-muted'>".tr('Note', [], ['upper' => true]).'</p>
            <p>'.nl2br((string) $documento['note'])."</p>
        </td>
        <td style='width:33mm;'>
            <p class='small-bold text-muted'>".tr('Totale imponibile', [], ['upper' => true]).'</p>
        </td>
    </tr>';

    // Dati riga 1
    echo "
    <tr>
        <td class='cell-padded text-right'>
            ".moneyFormat($totale_imponibile, $d_totali).'
        </td>
    </tr>';

    // Riga 2
    echo "
    <tr>
        <td style='width:33mm;'>
            <p class='small-bold text-muted'>".tr('Totale IVA', [], ['upper' => true])."</p>
        </td>
    </tr>

    <tr>
        <td class='cell-padded text-right'>
            ".moneyFormat($totale_iva, $d_totali).'
        </td>
    </tr>';

    // Riga 3
    echo "
    <tr>
        <td>
            <p class='small-bold text-muted'>".tr('Totale documento', [], ['upper' => true])."</p>
        </td>
    </tr>

    <tr>
        <td class='cell-padded text-right'>
            ".moneyFormat($totale, $d_totali).'
        </td>
    </tr>';

    if ($sconto_finale) {
        // Riga 4 SCONTO IN FATTURA
        echo "
        <tr>
            <td>
                <p class='small-bold text-muted'>".tr('Sconto in fattura', [], ['upper' => true])."</p>
            </td>
        </tr>

        <tr>
            <td class='cell-padded text-right'>
                ".moneyFormat($sconto_finale, $d_totali).'
            </td>
        </tr>';

        // Riga 5 NETTO A PAGARE
        echo "
        <tr>
            <td>
                <p class='small-bold text-muted'>".tr('Netto a pagare', [], ['upper' => true])."</p>
            </td>
        </tr>

        <tr>
            <td class='cell-padded text-right'>
                ".moneyFormat($netto_a_pagare, $d_totali).'
            </td>
        </tr>';
    }
} elseif ($documento['note']) {
    // Riga 1
    echo "
    <tr>
        <td style='height:40mm;'>
            <p class='small-bold text-muted'>".tr('Note', [], ['upper' => true]).'</p>
            '.nl2br((string) $documento['note']).'
        </td>
    </tr>';
}

echo '
</table>';

// Informazioni aggiuntive
echo '
<table class="table table-striped border-bottom">
    <tr>
        <th class="small-bold text-muted" class style="width:25%;">
            '.tr('Aspetto beni', [], ['upper' => true]).'
        </th>

        <th class="small-bold text-muted" class style="width:20%">
            '.tr('Num. colli', [], ['upper' => true]).'
        </th>

        <th class="small-bold text-muted" class style="width:20%">
            '.tr('Data ora trasporto', [], ['upper' => true]).'
        </th>

        <th class="small-bold text-muted" style="width:30%">
            '.tr('Causale trasporto', [], ['upper' => true]).'
        </th>

        <th class="small-bold text-muted" style="width:25%">
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
            '.(!empty($documento['data_ora_trasporto']) ? Translator::TimestampToLocale($documento['data_ora_trasporto']) : '').' &nbsp;
        </td>

        <td class="cell-padded">
            $causalet$ &nbsp;
        </td>

        <td class="cell-padded">
            $porto$ &nbsp;
        </td>
    </tr>

    <tr>
        <th class="small-bold text-muted">
            '.tr('Peso lordo', [], ['upper' => true]).'
        </th>

        <th class="small-bold text-muted">
            '.tr('Volume', [], ['upper' => true]).'
        </th>

        <th class="small-bold text-muted">
            '.tr('Vettore', [], ['upper' => true]).'
        </th>

        <th class="small-bold text-muted" colspan="2">
            '.tr('Tipo di spedizione', [], ['upper' => true]).'
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
            $vettore$ &nbsp;
        </td>

        <td class="cell-padded" colspan="2">
            $spedizione$ &nbsp;
        </td>
    </tr>
</table>';

// Firme
if ($documento->direzione == 'entrata') {
    echo '
    <table class="table-bordered">
        <tr>
            <th class="small-bold text-muted" style="width:33%">
                '.tr('Firma conducente', [], ['upper' => true]).'
            </th>

            <th class="small-bold text-muted" style="width:33%">
                '.tr('Firma vettore', [], ['upper' => true]).'
            </th>

            <th class="small-bold text-muted" style="width:33%">
                '.tr('Firma destinatario', [], ['upper' => true]).'
            </th>
        </tr>

        <tr>
            <td style="height: 10mm"></td>
            <td style="height: 10mm"></td>
            <td style="height: 10mm"></td>
        </tr>
    </table>';
}

if (empty($options['last-page-footer'])) {
    echo '$default_footer$';
}
