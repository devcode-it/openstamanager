<?php

include_once __DIR__.'/../../../core.php';

// Movimentazione degli articoli

echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'._('Movimenti').'</h3>
    </div>
    <div class="box-body">';

// Calcolo la quantità dai movimenti in magazzino
$rst = $dbo->fetchArray('SELECT SUM(qta) AS qta_totale FROM mg_movimenti WHERE idarticolo='.prepare($id_record).' AND (idintervento IS NULL OR idautomezzo = 0)');
$qta_totale = $rst[0]['qta_totale'];

echo '
<p>'._('Quantità calcolata dai movimenti').': '.Translator::numberToLocale($qta_totale).' '.$rs[0]['unita_misura'].'</p>';

// Elenco movimenti magazzino
$query = 'SELECT * FROM mg_movimenti WHERE idarticolo='.prepare($id_record).' ORDER BY data DESC';
if (empty($_GET['show_all1'])) {
    $query .= ' LIMIT 0, 20';
}

$rs2 = $dbo->fetchArray($query);

if (!empty($rs2)) {
    if (empty($_GET['show_all1'])) {
        echo '
        <p><a href="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&show_all1=1#tab_2">[ '._('Mostra tutti i movimenti').' ]</a></p>';
    } else {
        echo '
        <p><a href="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&show_all1=0#tab_2">[ '._('Mostra solo gli ultimi 20 movimenti').' ]</a></p>';
    }

    echo '
        <table class="table table-striped table-condensed table-bordered">
            <tr>
                <th class="text-center" width="100">'._('Q.tà').'</th>
                <th width="720">'._('Causale').'</th>
                <th>'._('Data').'</th>
            </tr>';
    foreach($rs2 as $r){
        // Quantità
        echo '
            <tr>
                <td class="text-right">'.Translator::numberToLocale($r['qta']).'</td>';

        // Causale
        echo '
                <td>'.$r['movimento'].'</td>';

        // Data
        echo '
                <td>'.Translator::timestampToLocale($r['data']).'</td>
            </tr>';
    }
    echo '
        </table>';
} else {
    echo '
        <p>'._('Nessun movimento disponibile').'...</p>';
}

echo '
    </div>
</div>';
