<?php

// TABELLA PRINCIPALE
echo '
<table class="table-bordered">';

if ($mostra_prezzi) {
    // Riga 1
    echo "
    <tr>
        <td rowspan='7'>
            <p class='small-bold'>".tr('Note', [], ['upper' => true]).'</p>
            <p>'.nl2br($records[0]['note'])."</p>
        </td>
        <td style='width:33mm;'>
            <p class='small-bold'>".tr('Totale imponibile', [], ['upper' => true]).'</p>
        </td>
    </tr>';

    // Dati riga 1
    echo "
    <tr>
        <td class='cell-padded text-right'>
            ".Translator::numberToLocale($imponibile_ddt, 2).' &euro;
        </td>
    </tr>';

    // Riga 2
    echo "
    <tr>
        <td style='width:33mm;'>
            <p class='small-bold'>".tr('Totale imposte', [], ['upper' => true])."</p>
        </td>
    </tr>

    <tr>
        <td class='cell-padded text-right'>
            ".Translator::numberToLocale($totale_iva, 2).' &euro;
        </td>
    </tr>';

    // Riga 3
    echo "
    <tr>
        <td>
            <p class='small-bold'>".tr('Totale documento', [], ['upper' => true])."</p>
        </td>
    </tr>

    <tr>
        <td class='cell-padded text-right'>
            ".Translator::numberToLocale($totale_ddt, 2).' &euro;
        </td>
    </tr>';
} else {
    // Riga 1
    echo "
    <tr>
        <td style='height:40mm;'>
            <p class='small-bold'>".tr('Note', [], ['upper' => true]).'</p>
            '.nl2br($records[0]['note']).'
        </td>
    </tr>';
}

echo '
</table>';

echo '
<table class="table-bordered">
    <tr>
        <th class="border-bottom border-right" style="width:33%">
            '.tr('Aspetto beni', [], ['upper' => true]).'
        </th>

        <th class="border-bottom border-right" style="width:33%">
            '.tr('Causale trasporto', [], ['upper' => true]).'
        </th>

        <th class="border-bottom" style="width:33%">
            '.tr('Porto', [], ['upper' => true]).'
        </th>
    </tr>

    <tr>
        <td class="cell-padded border-right">
            $aspettobeni$ &nbsp;
        </td>
        <td class="cell-padded border-right">
            $causalet$ &nbsp;
        </td>
        <td class="cell-padded">
            $porto$ &nbsp;
        </td>
    </tr>

    <tr>
        <th class="border-bottom border-right">
            '.tr('N<sup>o</sup> colli', [], ['upper' => true]).'
        </th>

        <th class="border-bottom border-right">
            '.tr('Tipo di spedizione', [], ['upper' => true]).'
        </th>

        <th class="border-bottom">
            '.tr('Vettore', [], ['upper' => true]).'
        </th>
    </tr>

    <tr>
        <td class="cell-padded border-right">
            $n_colli$ &nbsp;
        </td>
        <td class="cell-padded border-right">
            $spedizione$ &nbsp;
        </td>
        <td class="cell-padded">
            $vettore$ &nbsp;
        </td>
    </tr>

    <tr>
        <th class="border-bottom border-right">
            '.tr('Firma conducente', [], ['upper' => true]).'
        </th>

        <th class="border-bottom border-right">
            '.tr('Firma vettore', [], ['upper' => true]).'
        </th>

        <th class="border-bottom">
            '.tr('Firma destinatario', [], ['upper' => true]).'
        </th>
    </tr>

    <tr>
        <td class="cell-padded border-right">
            &nbsp;<br>&nbsp;
        </td>
        <td class="cell-padded border-right">
            &nbsp;<br>&nbsp;
        </td>
        <td class="cell-padded">
            &nbsp;<br>&nbsp;
        </td>
    </tr>
</table>';

echo '
$pagination$';
