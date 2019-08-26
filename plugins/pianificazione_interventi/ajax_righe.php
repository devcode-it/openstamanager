<?php

include_once __DIR__.'/../../core.php';

$plugin = Plugins::get($id_plugin);
$is_add = filter('add') ? true : false;

$pricing = Auth::admin() || Auth::user()['gruppo'] != 'Tecnici';

$rs2 = $dbo->fetchArray('SELECT * FROM co_promemoria_righe WHERE id_promemoria='.prepare($id_record).' ORDER BY id ASC');

if (!empty($rs2)) {
    echo '
<table class="table table-striped table-condensed table-hover table-bordered">
    <tr>
        <th>'.tr('Descrizione').'</th>
        <th width="8%">'.tr('Q.tà').'</th>
        <th width="15%">'.tr('Prezzo di acquisto').'</th>';

    if ($pricing) {
        echo '
        <th width="15%">'.tr('Prezzo di vendita').'</th>
        <th width="10%">'.tr('Iva').'</th>
        <th width="15%">'.tr('Subtotale').'</th>';
    }

    if (!$record['flag_completato']) {
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
            '.Translator::numberToLocale($r['qta'], 'qta').' '.$r['um'].'
        </td>';

        //Costo unitario
        echo '
        <td class="text-right">
            '.moneyFormat($r['prezzo_acquisto']).'
        </td>';

        if ($pricing) {
            // Prezzo unitario
            $netto = $r['prezzo_vendita'] - $r['sconto_unitario'];

            echo '
        <td class="text-right">
            '.moneyFormat($r['prezzo_vendita']).'';

            if ($r['sconto_unitario'] > 0) {
                echo '
            <br><span class="label label-danger">
                - '.tr('sconto _TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($r['sconto_unitario']),
                    '_TYPE_' => ($r['tipo_sconto'] == 'PRC' ? '%' : currency()),
                ]).'
            </span>';
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
            '.moneyFormat(sum($r['prezzo_vendita'] * $r['qta'], -$r['sconto'])).'
        </td>';
        }

        if (!empty($is_add)) {
            echo '
        <td>

             <button type="button" class="btn btn-warning btn-xs" data-title="'.tr('Modifica spesa').'" onclick="launch_modal(\'Modifica spesa\', \''.$plugin->fileurl('add_righe.php').'?id_plugin='.$id_plugin.'&id_record='.$id_record.'&idriga='.$r['id'].'\', \'#bs-popup2\');">
                <i class="fa fa-edit"></i>
             </button>

            <button type="button" class="btn btn-danger btn-xs" data-toggle="tooltip" onclick="if(confirm(\''.tr('Eliminare questa spesa?').'\')){ elimina_riga( \''.$r['id'].'\' ); }">
                <i class="fa fa-trash"></i>
            </button>
        </td>';
        }

        echo '
    </tr>';
    }

    echo '
</table>';
} else {
    echo '
<p>'.tr('Nessuna riga caricata').'.</p>';
}

echo '

<script type="text/javascript">
    function elimina_riga(id){
        $.post(globals.rootdir + "/actions.php", { op: "delriga", idriga: id, id_plugin: '.$id_plugin.'}, function(data, result){
            if(result == "success"){
                refreshRighe('.$id_record.');
            }
        });
    }
</script>';
