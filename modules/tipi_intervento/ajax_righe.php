<?php

if (file_exists(__DIR__.'/../../../core.php')) {
    include_once __DIR__.'/../../../core.php';
} else {
    include_once __DIR__.'/../../core.php';
}

$righe = $dbo->fetchArray('SELECT * FROM in_righe_tipiinterventi WHERE id_tipointervento='.prepare($id_record));

    echo '
    <table class="table table-striped table-condensed table-hover table-bordered">
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th width="8%">'.tr('Q.tà').'</th>
            <th width="15%">'.tr('Prezzo di acquisto').'</th>
            <th width="15%">'.tr('Prezzo di vendita').'</th>
            <th width="8%">'.tr('Iva').'</th>
            <th width="8%">'.tr('Subtotale').'</th>
            <th class="text-center" width="8%">'.tr('#').'</th>
        </tr>';

    foreach ($righe as $riga) {
        $rs = $dbo->fetchArray('SELECT percentuale FROM co_iva WHERE id='.$riga['idiva']);
        $iva = ($riga['subtotale'] * $rs[0]['percentuale']) / 100;

        echo '
            <tr>
                <td class="text-left">'.$riga['descrizione'].'</td>
                <td class="text-right">'.number_format($riga['qta'], 2, ',', '.').' '.$riga['um'].'</td>
                <td class="text-right">'.number_format($riga['prezzo_acquisto'], 2, ',', '.').' &euro;</td>
                <td class="text-right">'.number_format($riga['prezzo_vendita'], 2, ',', '.').' &euro;</td>
                <td class="text-right">'.number_format($iva, 2, ',', '.').' &euro;</td>
                <td class="text-right">'.number_format($riga['subtotale'], 2, ',', '.').' &euro;</td>
                <td class="text-center"><button type="button" class="btn btn-xs btn-warning" onclick="launch_modal(\''.tr('Aggiungi riga').'\', \''.$module->fileurl('add_righe.php').'?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$riga['id'].'\', 1);"><i class="fa fa-edit"></i></button> <button type="button" class="btn btn-xs btn-danger" data-toggle="tooltip" onclick="if(confirm(\''.tr('Eliminare questa riga?').'\')){ elimina_riga( \''.$riga['id'].'\' ); }"><i class="fa fa-trash"></i></button></td>
            </tr>';
    }

    echo '
    </table>';

?>

<script type="text/javascript">
    function elimina_riga( id ){
        $.post('<?php echo $module->fileurl('actions.php'); ?>', { op: 'delriga', idriga: id }, function(data, result){
            if( result=='success' ){
                //ricarico l'elenco delle righe
                $('#righe').load( '<?php echo $module->fileurl('ajax_righe.php'); ?>?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>');
            }
        });
    }
</script>
