<?php

include_once __DIR__.'/../../core.php';

$plugin = Plugins::get($id_plugin);
$is_add = filter('add') ? true : false;

$pricing = Auth::admin() || Auth::user()['gruppo'] != 'Tecnici';

$rs = $dbo->fetchArray('SELECT * FROM co_promemoria_articoli WHERE id_promemoria = '.prepare($id_record).' ORDER BY id ASC');

if (!empty($rs)) {
    echo '
<table class="table table-striped table-condensed table-hover table-bordered">
    <tr>
        <th>'.tr('Articolo').'</th>
        <th width="8%">'.tr('Q.tà').'</th>';

    if ($pricing) {
        echo '
        <th width="15%">'.tr('Prezzo di acquisto').'</th>';
    }

    if ($pricing) {
        echo '
        <th width="15%">'.tr('Prezzo di vendita').'</th>
        <th width="10%">'.tr('Iva').'</th>
        <th width="15%">'.tr('Imponibile').'</th>';
    }

    if (!$record['flag_completato']) {
        echo '
        <th width="80"></th>';
    }
    echo '
    </tr>';

    foreach ($rs as $r) {
        $extra = '';
        $mancanti = 0;

        // Individuazione dei seriali
        if (!empty($r['idarticolo']) && !empty($r['abilita_serial'])) {
            $serials = array_column($dbo->fetchArray('SELECT serial FROM mg_prodotti WHERE serial IS NOT NULL AND id_riga_intervento='.prepare($r['id'])), 'serial');
            $mancanti = $r['qta'] - count($serials);

            if ($mancanti > 0) {
                $extra = 'class="warning"';
            } else {
                $mancanti = 0;
            }
        }

        echo '
    <tr '.$extra.'>
        <td>
            <input type="hidden" name="id" value="'.$r['id'].'">
            '.Modules::link('Articoli', $r['idarticolo'], (!empty($r['codice']) ? $r['codice'].' - ' : '').$r['descrizione']);

        // Info extra (lotto, serial, altro)
        if (!empty($r['abilita_serial'])) {
            if (!empty($mancanti)) {
                echo '
            <br><b><small class="text-danger">'.tr('_NUM_ serial mancanti', [
                '_NUM_' => $mancanti,
            ]).'</small></b>';
            }
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

        if ($pricing) {
            echo '
        <td class="text-right">
            '.moneyFormat($r['prezzo_acquisto']).'
        </td>';
        }

        if ($pricing) {
            // Prezzo unitario
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
            <span class="prezzo_articolo">'.Translator::numberToLocale(sum($r['prezzo_vendita'] * $r['qta'], -$r['sconto'])).'</span> '.currency().'
        </td>';
        }

        if (!empty($is_add)) {
            echo '
        <td>
            <button type="button" class="btn btn-warning btn-xs" data-title="'.tr('Modifica spesa').'" onclick="launch_modal(\'Modifica spesa\', \''.$plugin->fileurl('add_articolo.php').'?id_plugin='.$id_plugin.'&id_record='.$id_record.'&idriga='.$r['id'].'\', \'#bs-popup2\');">
                <i class="fa fa-edit"></i>
            </button>

            <button type="button" class="btn btn-danger btn-xs" data-toggle="tooltip" title="'.tr('Elimina materiale').'" onclick="if(confirm(\''.tr('Eliminare questo materiale?').'\') ){ ritorna_al_magazzino(\''.$r['id'].'\'); }">
                <i class="fa fa-angle-double-left"></i><i class="fa fa-truck"></i>
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
<p>'.tr('Nessun articolo caricato').'.</p>';
}

echo '
<script type="text/javascript">
    function ritorna_al_magazzino(id){
        $.post(globals.rootdir + "/actions.php", {op: "unlink_articolo", idriga: id, id_plugin: '.$id_plugin.' }, function(data, result){
            if(result == "success"){
                refreshArticoli('.$id_record.');
            }
        });
    }
</script>';
