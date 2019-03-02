<?php

if (file_exists(__DIR__.'/../../../core.php')) {
    include_once __DIR__.'/../../../core.php';
} else {
}

$show_prezzi = Auth::user()['gruppo'] != 'Tecnici' || (Auth::user()['gruppo'] == 'Tecnici' && setting('Mostra i prezzi al tecnico'));

$query = 'SELECT * FROM in_righe_interventi WHERE idintervento='.prepare($id_record).' ORDER BY id ASC';
$rs2 = $dbo->fetchArray($query);

if (count($rs2) > 0) {
    echo '
<table class="table table-striped table-condensed table-hover table-bordered">
    <tr>
        <th>'.tr('Descrizione').'</th>
        <th width="8%">'.tr('Q.tà').'</th>
        <th width="15%">'.tr('Prezzo di acquisto').'</th>';

    if ($show_prezzi) {
        echo '
        <th width="15%">'.tr('Prezzo di vendita').'</th>
        <th width="10%">'.tr('Iva').'</th>
        <th width="15%">'.tr('Subtotale').'</th>';
    }

    if (!$record['flag_completato']) {
        echo '
        <th width="120" class="text-center">'.tr('#').'</th>';
    }
    echo '
    </tr>';

    foreach ($rs2 as $r) {
        echo '
    <tr>
        <td>
            <input type="hidden" name="id" value="'.$r['id'].'">
            '.nl2br($r['descrizione']).'
        </td>';

        // Quantità
        echo '
        <td class="text-right">
            '.Translator::numberToLocale($r['qta'], 'qta').' '.$r['um'].'
        </td>';

        //Costo unitario
        echo '
        <td class="text-right">
            '.Translator::numberToLocale($r['prezzo_acquisto']).' &euro;
        </td>';

        if ($show_prezzi) {
            // Prezzo unitario
            $netto = $r['prezzo_vendita'] - $r['sconto_unitario'];

            echo '
        <td class="text-right">
            '.Translator::numberToLocale($r['prezzo_vendita']).' &euro;';

            if ($r['sconto_unitario'] > 0) {
                echo '
            <br><span class="label label-danger">
                - '.tr('sconto _TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($r['sconto_unitario']),
                    '_TYPE_' => ($r['tipo_sconto'] == 'PRC' ? '%' : '&euro;'),
                ]).'
            </span>';
            }

            echo '
        </td>';

            echo '
        <td class="text-right">
            <span>'.Translator::numberToLocale($r['iva']).'</span> &euro;';
            echo '
        </td>';

            // Prezzo di vendita
            echo '
        <td class="text-right">
            <span class="prezzo_articolo">'.Translator::numberToLocale(sum($r['prezzo_vendita'] * $r['qta'], -$r['sconto'])).'</span> &euro;
        </td>';
        }

        // Pulsante per riportare nel magazzino centrale.
        // Visibile solo se l'intervento non è stato nè fatturato nè completato.
        if (!$record['flag_completato']) {
            echo '
        <td class="text-center">
            <button type="button" class="btn btn-warning btn-xs" data-toggle="tooltip" onclick="launch_modal(\''.tr('Modifica spesa').'\', \''.$rootdir.'/modules/interventi/add_righe.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id'].'\', 1);"><i class="fa fa-edit"></i></button>
            <button type="button" class="btn btn-danger btn-xs" data-toggle="tooltip" onclick="if(confirm(\''.tr('Eliminare questa spesa?').'\')){ elimina_riga( \''.$r['id'].'\' ); }"><i class="fa fa-trash"></i></button>
        </td>';
        }
        echo '
    </tr>';
    }

    echo '
</table>';
} else {
    echo '
<p>'.tr('Nessuna spesa presente').'.</p>';
}

?>

<script type="text/javascript">
    function elimina_riga( id ){
        $.post(globals.rootdir + '/modules/interventi/actions.php', { op: 'delriga', idriga: id }, function(data, result){
            if( result=='success' ){
                //ricarico l'elenco delle righe
                $('#righe').load( globals.rootdir + '/modules/interventi/ajax_righe.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>');

                $('#costi').load(globals.rootdir + '/modules/interventi/ajax_costi.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>');
            }
        });
    }
</script>
