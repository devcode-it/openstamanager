<?php

use Modules\Interventi\Intervento;

include_once __DIR__.'/../../core.php';

$show_prezzi = Auth::user()['gruppo'] != 'Tecnici' || (Auth::user()['gruppo'] == 'Tecnici' && setting('Mostra i prezzi al tecnico'));

$intervento = $intervento ?: Intervento::find($id_record);
$righe = $intervento->getRighe();

if (!$righe->isEmpty()) {
    echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <thead>
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th class="text-center" width="8%">'.tr('Q.tà').'</th>';

    if ($show_prezzi) {
        echo '
            <th class="text-center" width="15%">'.tr('Prezzo di acquisto').'</th>
            <th class="text-center" width="15%">'.tr('Prezzo di vendita').'</th>
            <th class="text-center" width="10%">'.tr('Iva').'</th>
            <th class="text-center" width="15%">'.tr('Imponibile').'</th>';
    }

    if (!$record['flag_completato']) {
        echo '
            <th class="text-center"  width="120" class="text-center">'.tr('#').'</th>';
    }
    echo '
        </tr>
    </thead>

    <tbody>';

    foreach ($righe as $riga) {
        $r = $riga->toArray();

        $extra = '';
        $mancanti = $riga->isArticolo() ? $riga->missing_serials_number : 0;
        if ($mancanti > 0) {
            $extra = 'class="warning"';
        }
        $descrizione = (!empty($riga->articolo) ? $riga->articolo->codice.' - ' : '').$riga['descrizione'];

        echo '
        <tr '.$extra.'>
            <td>
                '.Modules::link($riga->isArticolo() ? Modules::get('Articoli')['id'] : null, $riga->isArticolo() ? $riga['idarticolo'] : null, $descrizione);

        if ($riga->isArticolo()) {
            if (!empty($mancanti)) {
                echo '
                <br><b><small class="text-danger">'.tr('_NUM_ serial mancanti', [
                    '_NUM_' => $mancanti,
                ]).'</small></b>';
            }

            $serials = $riga->serials;
            if (!empty($serials)) {
                echo '
                <br>'.tr('SN').': '.implode(', ', $serials);
            }
        }

        echo '
            </td>';

        // Quantità
        echo '
            <td class="text-right">
                '.Translator::numberToLocale($r['qta'], 'qta').' '.$r['um'].'
            </td>';

        if ($show_prezzi) {
            //Costo unitario
            echo '
            <td class="text-right">
                '.moneyFormat($riga->costo_unitario).'
            </td>';
            
            // Prezzo unitario
            echo '
            <td class="text-right">
                '.moneyFormat($riga->prezzo_unitario);

            if (abs($r['sconto_unitario']) > 0) {
                $text = $r['sconto_unitario'] > 0 ? tr('sconto _TOT_ _TYPE_') : tr('maggiorazione _TOT_ _TYPE_');

                echo '
                <br><small class="label label-danger">'.replace($text, [
                    '_TOT_' => Translator::numberToLocale(abs($r['sconto_unitario'])),
                    '_TYPE_' => ($r['tipo_sconto'] == 'PRC' ? '%' : currency()),
                ]).'</small>';
            }

            echo '
            </td>';

            echo '
            <td class="text-right">
                '.moneyFormat($r['iva']).'
            </td>';

            // Prezzo di vendita
            echo '
            <td class="text-right">
                '.moneyFormat($riga->imponibile).'
            </td>';
        }

        // Pulsante per riportare nel magazzino centrale.
        // Visibile solo se l'intervento non è stato nè fatturato nè completato.
        if (!$record['flag_completato']) {
            echo '
            <td class="text-center">';

            if ($r['abilita_serial']) {
                echo '
                <button type="button" class="btn btn-info btn-xs" data-toggle="tooltip" onclick="launch_modal(\''.tr('Modifica articoli').'\', \''.$rootdir.'/modules/fatture/add_serial.php?id_module='.$id_module.'&id_record='.$id_record.'&idarticolo='.$r['idriga'].'&idriga='.$r['id'].'\');">
                    <i class="fa fa-barcode"></i>
                </button>';
            }

            echo '
                <button type="button" class="btn btn-warning btn-xs" data-toggle="tooltip" onclick="launch_modal(\''.tr('Modifica').'\', \''.$structure->fileurl('row-edit.php').'?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id'].'&type='.urlencode(get_class($riga)).'\');">
                    <i class="fa fa-edit"></i>
                </button>

                <button type="button" class="btn btn-danger btn-xs" data-toggle="tooltip" onclick="elimina_riga(\''.addslashes(get_class($riga)).'\', \''.$riga->id.'\');">
                    <i class="fa fa-trash"></i>
                </button>
            </td>';
        }
        echo '
        </tr>';
    }

    echo '
    </tbody>
</table>';
} else {
    echo '
<p>'.tr('Nessuna riga presente').'.</p>';
}

?>

<script type="text/javascript">
    function elimina_riga(type, id){
        if(confirm('<?php echo tr('Eliminare questa riga?'); ?>')) {
            $.post(globals.rootdir + '/actions.php', {
                op: 'delete_riga',
                id_module: globals.id_module,
                id_record: globals.id_record,
                type: type,
                idriga: id,
            }, function (data, result) {
                if (result == 'success') {
                    // Ricarico le righe
                    $('#righe').load('<?php echo $module->fileurl('row-list.php'); ?>?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>');

                    // Ricarico la tabella dei costi
                    $('#costi').load('<?php echo $module->fileurl('ajax_costi.php'); ?>?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>');

                    // Toast
                    alertPush();
                }
            });
        }
    }
</script>
