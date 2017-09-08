<?php

// TABELLA PRINCIPALE
echo '
<table class="table-bordered">';

if ($mostra_prezzi) {
    // Riga 1
    echo "
    <tr>
        <td rowspan='7'>
            <p class='small-bold'>".strtoupper(tr('Note')).'</p>
            <p>'.nl2br($rs[0]['note'])."</p>
        </td>
        <td style='width:33mm;'>
            <p class='small-bold'>".strtoupper(tr('Totale imponibile')).'</p>
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
            <p class='small-bold'>".strtoupper(tr('Totale imposte'))."</p>
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
            <p class='small-bold'>".strtoupper(tr('Totale documento'))."</p>
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
            <p class='small-bold'>".strtoupper(tr('Note')).'</p>
            '.nl2br($rs[0]['note']).'
        </td>
    </tr>';
}

echo '
</table>';

echo '
<table class="table-bordered">
    <tr>
        <th class="border-bottom border-right" style="width:33%">
            '.strtoupper(tr('Aspetto beni')).'
        </th>

        <th class="border-bottom border-right" style="width:33%">
        '.strtoupper(tr('Causale trasporto')).'
        </th>

        <th class="border-bottom" style="width:33%">
        '.strtoupper(tr('Porto')).'
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
            '.strtoupper(tr('N<sup>o</sup> colli')).'
        </th>

        <th class="border-bottom border-right">
            '.strtoupper(tr('Tipo di spedizione')).'
        </th>

        <th class="border-bottom">
            '.strtoupper(tr('Vettore')).'
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
            '.strtoupper(tr('Firma conducente')).'
        </th>

        <th class="border-bottom border-right">
            '.strtoupper(tr('Firma vettore')).'
        </th>

        <th class="border-bottom">
            '.strtoupper(tr('Firma destinatario')).'
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
