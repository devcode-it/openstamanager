<?php

include_once __DIR__.'/../../core.php';

$id_movimento = get('id_movimento');

$query = 'SELECT *, (subtotale-sconto) AS imponibile, (co_movimenti.descrizione) AS desc_fatt, (co_righe_documenti.descrizione) AS desc_riga FROM co_movimenti INNER JOIN co_righe_documenti ON co_movimenti.iddocumento = co_righe_documenti.iddocumento WHERE co_movimenti.id = '.prepare($id_movimento);
$righe = $dbo->fetchArray($query);

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <tr>
        <th>' . tr('Descrizione riga') . '</th>
        <th width="100">' . tr('Imponibile') . '</th>
    </tr>';

    foreach ($righe as $riga) {
        echo '
    <tr>
        <td>
            <span>' . $riga['desc_riga'] . '</span>
        </td>

        <td class="text-right">
            <span>' . moneyFormat($riga['imponibile']) . ' </span>
        </td>
    </tr>';
    }

    $totale_imponibile = sum(array_column($righe, 'imponibile'));

    echo '
    <tr>
        <th class="text-right">' . tr('Totali') . ': </th>
        <th width="100" class="text-right"><span>' . moneyFormat($totale_imponibile) . '</span></th>
    </tr>
</table>

'.Modules::link($id_module, $righe[0]['iddocumento'], $righe[0]['desc_fatt'], null, 'class="btn btn-info btn-block"');
