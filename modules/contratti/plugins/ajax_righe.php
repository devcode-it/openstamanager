<?php

include_once __DIR__.'/../../../core.php';

if (!empty($get['idcontratto_riga']))
	$idcontratto_riga = $get['idcontratto_riga'];

$query = 'SELECT * FROM co_righe_contratti_materiali WHERE id_riga_contratto='.prepare($idcontratto_riga).' '.Modules::getAdditionalsQuery('Magazzino').' ORDER BY id ASC';
$rs2 = $dbo->fetchArray($query);




if (count($rs2) > 0) {
    echo '
<table class="table table-striped table-condensed table-hover table-bordered">
    <tr>
        <th>'.tr('Descrizione').'</th>
        <th width="8%">'.tr('Q.tà').'</th>
        <th width="15%">'.tr('Prezzo di acquisto').'</th>';

    if (Auth::admin() || $_SESSION['gruppo'] != 'Tecnici') {
        echo '
        <th width="15%">'.tr('Prezzo di vendita').'</th>
        <th width="10%">'.tr('Iva').'</th>
        <th width="15%">'.tr('Subtotale').'</th>';
    }

    if (!$records[0]['flag_completato']) {
        echo '
        <th width="80"></th>';
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
            '.Translator::numberToLocale($r['qta']).' '.$r['um'].'
        </td>';

        //Costo unitario
        echo '
        <td class="text-right">
            '.Translator::numberToLocale($r['prezzo_acquisto']).' &euro;
        </td>';

        if (Auth::admin() || $_SESSION['gruppo'] != 'Tecnici') {
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
         if (empty($readonly)) {
            echo '
        <td>
        
			 <button type="button" class="btn btn-warning btn-xs" data-title="'.tr('Modifica spesa').'" onclick="launch_modal(\'Modifica spesa\', \''.$rootdir.'/modules/contratti/plugins/add_righe.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id'].'\', 1, \'#bs-popup2\');" >
			 <i class="fa fa-edit"></i></button>
			
			
            <button type="button" class="btn btn-danger btn-xs" data-toggle="tooltip" onclick="if(confirm(\''.tr('Eliminare questa spesa?').'\')){ elimina_riga( \''.$r['id'].'\' ); }"><i class="fa fa-trash"></i></button>
        </td>';
        }
        echo '
    </tr>';
    }

    echo '
</table>';
}

?>

<script type="text/javascript">
    function elimina_riga( id ){
        $.post(globals.rootdir + '/modules/contratti/plugins/actions.php', { op: 'delriga', idriga: id }, function(data, result){
            if( result=='success' ){
                //ricarico l'elenco delle righe
                $('#righe').load( globals.rootdir + '/modules/contratti/plugins/ajax_righe.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&idcontratto_riga=<?php echo $idcontratto_riga; ?>');

            }
        });
    }
</script>
