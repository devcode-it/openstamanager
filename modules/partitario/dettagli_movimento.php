<?php

include_once __DIR__.'/../../core.php';

$id_movimento = $get['id_movimento'];
$id_conto = $get['id_conto'];

$query = 'SELECT *, (subtotale-sconto) AS imponibile, (co_movimenti.descrizione) AS desc_fatt, (co_righe_documenti.descrizione) AS desc_riga FROM co_movimenti INNER JOIN co_righe_documenti ON co_movimenti.iddocumento =  co_righe_documenti.iddocumento WHERE co_movimenti.id = '.prepare($id_movimento).' AND co_movimenti.idconto = '.prepare($id_conto);
$rs = $dbo->fetchArray($query);

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <tr>
        <th>'.tr('Descrizione riga').'</th>
        <th width="100">'.tr('Imponibile').'</th>
        <th width="100">'.tr('Q.tà').'</th
        <th width="100">'.tr('Um').'</th>
    </tr>';

$totale_imponibile = 0;

for ($i = 0; $i < sizeof($rs); ++$i) {
    echo '
    <tr>
        <td>
            <span>'.$rs[$i]['desc_riga'].'</span>
        </td>

        <td>
            <span>'.Translator::numberToLocale($rs[$i]['imponibile']).' &euro; </span>
        </td>

        <td
            <span>'.Translator::numberToLocale($rs[$i]['qta']).'</span>
        </td>

        <td>
            <span>'.$rs[$i]['um'].'</span>
        </td>
    </tr>';

    $totale_imponibile += $rs[$i]['imponibile'];
    $totale_qta += $rs[$i]['qta'];
}

echo '
    <tr>
        <th>'.tr('Totali').': </th>
        <th width="100"><span>'.Translator::numberToLocale($totale_imponibile).' &euro;</span></th>
        <th width="100"><span>'.Translator::numberToLocale($totale_qta).'</span></th>
        <th width="100"></th>
    </tr>
</table>

<br><a class="btn btn-info btn-block" target="_blank" href="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$rs[0]['iddocumento'].'">'.$rs[0]['desc_fatt'].'</a>';
