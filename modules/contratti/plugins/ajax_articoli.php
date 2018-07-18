<?php

include_once __DIR__.'/../../../core.php';

include_once Modules::filepath('Articoli', 'modutil.php');

//$query = 'SELECT *, (SELECT codice FROM mg_articoli WHERE id=mg_articoli_interventi.idarticolo) AS codice, mg_articoli_interventi.id AS idriga, (SELECT prc_guadagno FROM mg_listini WHERE id=(SELECT idlistino_vendite FROM an_anagrafiche WHERE idanagrafica=(SELECT idanagrafica FROM in_interventi WHERE id=mg_articoli_interventi.idintervento) ) ) AS prc_guadagno FROM mg_articoli_interventi WHERE idintervento='.prepare($id_record).' '.Modules::getAdditionalsQuery('Magazzino');
//$rs = $dbo->fetchArray($query);

if (!empty($get['idcontratto_riga'])) {
    $idcontratto_riga = $get['idcontratto_riga'];
}

$query = 'SELECT * FROM co_righe_contratti_articoli WHERE id_riga_contratto='.prepare($idcontratto_riga).' '.Modules::getAdditionalsQuery('Magazzino').' ORDER BY id ASC';
$rs = $dbo->fetchArray($query);

if (!empty($rs)) {
    echo '
<table class="table table-striped table-condensed table-hover table-bordered">
    <tr>
        <th>'.tr('Articolo').'</th>
        <th width="8%">'.tr('Q.tà').'</th>';

    if (Auth::admin() || Auth::user()['gruppo'] != 'Tecnici') {
        echo '
        <th width="15%">'.tr('Prezzo di acquisto').'</th>';
    }

    if (Auth::admin() || Auth::user()['gruppo'] != 'Tecnici') {
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

        if (Auth::admin() || Auth::user()['gruppo'] != 'Tecnici') {
            echo '
        <td class="text-right">
            '.Translator::numberToLocale($r['prezzo_acquisto']).' &euro;
        </td>';
        }

        if (Auth::admin() || Auth::user()['gruppo'] != 'Tecnici') {
            // Prezzo unitario
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
        <td>';

            /*if ($r['abilita_serial']) {
                echo '
            <button type="button" class="btn btn-info btn-xs" data-toggle="tooltip" onclick="launch_modal(\''.tr('Modifica articoli').'\', \''.$rootdir.'/modules/fatture/add_serial.php?id_module='.$id_module.'&id_record='.$id_record.'&idarticolo='.$r['idriga'].'&idriga='.$r['id'].'\', 1);"><i class="fa fa-barcode"></i></button>';
            }*/

            if (empty($readonly)) {
                echo '
			<button type="button" class="btn btn-warning btn-xs" data-title="'.tr('Modifica spesa').'" onclick="launch_modal(\'Modifica spesa\', \''.$rootdir.'/modules/contratti/plugins/add_articolo.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id'].'\', 1, \'#bs-popup2\');" >
			<i class="fa fa-edit"></i></button>
            <button type="button" class="btn btn-danger btn-xs" data-toggle="tooltip" title="'.tr('Elimina materiale').'" onclick="if(confirm(\''.tr('Eliminare questo materiale?').'\') ){ ritorna_al_magazzino(\''.$r['id'].'\'); }"><i class="fa fa-angle-double-left"></i> <i class="fa fa-truck"></i></button>';
            }

            echo '
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
    function ritorna_al_magazzino( id ){
        $.post(globals.rootdir + '/modules/contratti/plugins/actions.php', {op: 'unlink_articolo', idriga: id, id_record: '<?php echo $id_record; ?>', id_module: '<?php echo $id_module; ?>' }, function(data, result){
            if( result == 'success' ){
                // ricarico l'elenco degli articoli
                $('#articoli').load(globals.rootdir + '/modules/contratti/plugins/ajax_articoli.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&idcontratto_riga=<?php echo $idcontratto_riga; ?>');
            }
        });
    }
</script>
