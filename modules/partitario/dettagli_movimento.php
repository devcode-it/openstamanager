<?php

include_once __DIR__.'/../../core.php';

$id_movimento = get('id_movimento');
$id_conto = get('id_conto');

$query = 'SELECT *, (subtotale-sconto) AS imponibile, (co_movimenti.descrizione) AS desc_fatt, (co_righe_documenti.descrizione) AS desc_riga FROM co_movimenti INNER JOIN co_righe_documenti ON co_movimenti.iddocumento =  co_righe_documenti.iddocumento WHERE co_movimenti.id = '.prepare($id_movimento).' AND co_movimenti.idconto = '.prepare($id_conto);
$rs = $dbo->fetchArray($query);

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <tr>
        <th>'.tr('Descrizione riga').'</th>
        <th width="100">'.tr('Imponibile').'</th>
    </tr>';

$totale_imponibile = 0;

for ($i = 0; $i < sizeof($rs); ++$i) {
    echo '
    <tr>
        <td>
            <span>'.$rs[$i]['desc_riga'].'</span>
        </td>

        <td class="text-right">
            <span>'.moneyFormat($rs[$i]['imponibile']).' </span>
        </td>
    </tr>';

    $totale_imponibile += $rs[$i]['imponibile'];
    $totale_qta += $rs[$i]['qta'];
}

echo '
    <tr>
        <th class="text-right">'.tr('Totali').': </th>
        <th width="100" class="text-right"><span>'.moneyFormat($totale_imponibile).'</span></th>
    </tr>
</table>

<br><a class="btn btn-info btn-block" target="_blank" href="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$rs[0]['iddocumento'].'">'.$rs[0]['desc_fatt'].'</a>';
