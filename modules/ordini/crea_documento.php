<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

$data = [
    'preventivo' => [
        'table' => 'co_preventivi',
        'rows' => 'co_righe_preventivi',
        'id' => 'idpreventivo',
        'condition' => '',
    ],
];

$documento = get('documento');

$pos = 'preventivo';
$op = 'ordine_da_preventivo';

$head = tr('Preventivo numero _NUM_');

$table = $data[$pos]['table'];
$rows = $data[$pos]['rows'];
$id = $data[$pos]['id'];
$row = str_replace('id', 'id_riga_', $id);

$module_name = 'Ordini cliente';

$op = !empty(get('op')) ? get('op') : $op;

$button = tr('Crea ordine');
$button = !empty(get('op')) ? tr('Aggiungi') : $button;

// Info documento
$rs = $dbo->fetchArray('SELECT * FROM '.$table.' WHERE id='.prepare($id_record));
$numero = !empty($rs[0]['numero_esterno']) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];
$idanagrafica = $rs[0]['idanagrafica'];
$idsede = $rs[0]['idsede'];
$idpagamento = $rs[0]['idpagamento'];
$idconto = $rs[0]['idconto'];

/*
    Form di inserimento riga documento
*/
echo '
<p>'.str_replace('_NUM_', $numero, $head).'.</p>';

// Selezione articoli del preventivo da copiare nell'ordine, usando l'ordinamento scelto dall'utente
$rs = $dbo->fetchArray('SELECT * FROM '.$table.' INNER JOIN '.$rows.' ON '.$table.'.id='.$rows.'.'.$id.' WHERE '.$table.'.id='.prepare($id_record).' ORDER BY `order`');

if (!empty($rs)) {
    echo '
<p>'.tr('Seleziona le righe e le relative quantità da inserire nell\'ordine.').'.</p>

<form action="'.$rootdir.'/editor.php?id_module='.Modules::get($module_name)['id'].(!empty(get('iddocumento')) ? '&id_record='.get('iddocumento') : '').'" method="post">
    <input type="hidden" name="'.$id.'" value="'.$id_record.'">
    <input type="hidden" name="idanagrafica" value="'.$idanagrafica.'">
    <input type="hidden" name="idsede" value="'.$idsede.'">
    <input type="hidden" name="idconto" value="'.$idconto.'">
    <input type="hidden" name="idpagamento" value="'.$idpagamento.'">
    <input type="hidden" name="iddocumento" value="'.$id_record.'">

    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="dir" value="'.$dir.'">
    <div class="row">

        <div class="col-md-6">
            {[ "type": "date", "label": "'.tr('Data del documento').'", "name": "data", "required": 1, "value": "-now-" ]}
        </div>
    </div>

    <div class="clearfix"></div>
    <br>

    <table class="table table-striped table-hover table-condensed">
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th width="10%">'.tr('Q.tà').'</th>
            <th width="15%">'.tr('Q.tà da evadere').'</th>
            <th width="20%">'.tr('Subtot.').'</th>
            <th width="20%">'.tr('Seriali').'</th>
        </tr>';

    $totale = 0.00;

    foreach ($rs as $i => $r) {
        // Descrizione
        echo '
        <tr>
            <td '.($r['is_descrizione'] ? 'colspan="5"' : '').' >

                <input type="hidden" name="abilita_serial['.$r['id'].']" value="'.$r['abilita_serial'].'" />
                <input type="hidden" id="idarticolo_'.$i.'" name="idarticolo['.$r['id'].']" value="'.$r['idarticolo'].'" />
                <input type="hidden" id="descrizione_'.$i.'" name="descrizione['.$r['id'].']" value="'.$r['descrizione'].'" />';

        // Checkbox - da evadere?
        echo '
                <input type="checkbox" checked="checked" id="checked_'.$i.'" name="evadere['.$r['id'].']" value="on" onclick="ricalcola_subtotale_riga('.$i.');" />';

        echo nl2br($r['descrizione']);

        echo '
            </td>';

        if ($r['is_descrizione']) {
            continue;
        }

        // Q.tà rimanente
        echo '
        <td>
            <input type="hidden" id="qtamax_'.$i.'" value="'.($r['qta']).'" />
            <input type="hidden" id="um_'.$i.'" name="um['.$r['id'].']" value="'.$r['um'].'" />
            <p class="text-center">'.Translator::numberToLocale($r['qta'], 'qta').'</p>
        </td>';

        // Q.tà da evadere
        echo '
        <td>
            {[ "type": "number", "name": "qta_da_evadere['.$r['id'].']", "id": "qta_'.$i.'", "required": 1, "value": "'.$r['qta'].'", "extra" : "onkeyup=\"ricalcola_subtotale_riga('.$i.');\"", "decimals": "qta", "min-value": "0" ]}
        </td>';

        // Subtotale
        $subtotale = $r['subtotale'] / $r['qta'] * ($r['qta']);
        $sconto = $r['sconto'] / $r['qta'] * ($r['qta']);
        $iva = $r['iva'] / $r['qta'] * ($r['qta']);

        echo '
        <td>
            <input type="hidden" id="subtot_'.$i.'" name="subtot['.$r['id'].']" value="'.($r['subtotale'] / $r['qta']).'" />
            <input type="hidden" id="sconto_'.$i.'" name="sconto['.$r['id'].']" value="'.($r['sconto'] / $r['qta']).'" />
            <input type="hidden" id="idiva_'.$i.'" name="idiva['.$r['id'].']" value="'.$r['idiva'].'" />
            <input type="hidden" id="iva_'.$i.'" name="iva['.$r['id'].']" value="'.($r['iva'] / $r['qta']).'" />

            <big id="subtotale_'.$i.'">'.Translator::numberToLocale($subtotale - $sconto + $iva).' &euro;</big><br/>

            <small style="color:#777;" id="subtotaledettagli_'.$i.'">'.Translator::numberToLocale($subtotale - $sconto).' + '.Translator::numberToLocale($iva).'</small>
        </td>';

        // Seriali
        echo '
        <td>';
        if (!empty($r['abilita_serial'])) {
            $values = $dbo->fetchArray('SELECT DISTINCT serial FROM mg_prodotti WHERE dir=\''.$dir.'\' AND '.$row.' = \''.$r['id'].'\' AND serial IS NOT NULL AND serial NOT IN (SELECT serial FROM mg_prodotti WHERE serial IS NOT NULL AND dir=\''.$dir.'\' AND '.$data[$pos]['condition'].')');

            echo '
            {[ "type": "select", "name": "serial['.$i.']['.$r['id'].']", "id": "serial_'.$i.'", "multiple": 1, "values": "query=SELECT DISTINCT serial AS id, serial AS descrizione FROM mg_prodotti WHERE dir=\''.$dir.'\' AND '.$row.' = \''.$r['id'].'\' AND serial IS NOT NULL AND serial NOT IN (SELECT serial FROM mg_prodotti WHERE serial IS NOT NULL AND dir=\''.$dir.'\' AND '.$data[$pos]['condition'].')", "value": "'.implode(',', array_column($values, 'serial')).'", "extra": "data-maximum=\"'.intval($r['qta_rimanente']).'\"" ]}
                ';
        } else {
            echo '-';
        }
        echo '
        </td>
    </tr>';

        $totale += $subtotale - $sconto + $iva;
    }

    // Totale
    echo '
        <tr>
            <td colspan="4" align="right" class="text-right">
                <b>'.tr('Totale').':</b>
            </td>
            <td class="text-right" colspan="2">
                <big id="totale">'.Translator::numberToLocale($totale).' &euro;</big>
            </td>
        </tr>
    </table>';

    echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" id="submit_btn" class="btn btn-primary pull-right">
                <i class="fa fa-plus"></i> '.$button.'
            </button>
		</div>
    </div>
</form>';
} else {
    echo '
<p>'.tr('Non ci sono articoli da evadere').'...</p>';
}

echo '
    <script src="'.$rootdir.'/lib/init.js"></script>';

?>

<script type="text/javascript">
    function ricalcola_subtotale_riga( r ){
        subtot = $("#subtot_"+r).val();
        sconto = $("#sconto_"+r).val();
        iva = $("#iva_"+r).val();

        qtamax = $("#qtamax_"+r).val() ? $("#qtamax_"+r).val() : 0;

        subtot = parseFloat(subtot);
        sconto = parseFloat(sconto);
        iva = parseFloat(iva);
        qtamax = parseFloat(qtamax);

        subtot = subtot - sconto;

        qta = $("#qta_"+r).val().toEnglish();

        // Se inserisco una quantità da evadere maggiore di quella rimanente, la imposto al massimo possibile
        if(qta > qtamax){
            qta = qtamax;

            $('#qta_'+r).val(qta);
        }

        // Se tolgo la spunta della casella dell'evasione devo azzerare i conteggi
        if(isNaN(qta) || !$('#checked_'+r).is(':checked')){
            qta = 0;
        }

        $("#serial_"+r).selectClear();
        $("#serial_"+r).select2("destroy");
        $("#serial_"+r).data('maximum', qta);
        start_superselect();

        subtotale = (subtot * qta + iva * qta).toLocale();

        $("#subtotale_"+r).html(subtotale+" &euro;");
        $("#subtotaledettagli_"+r).html((subtot * qta).toLocale() + " + " + (iva * qta).toLocale());

        ricalcola_totale();
    }

    function ricalcola_totale(){
        tot_qta = 0;
        r = 0;
        totale = 0.00;
        $('input[id*=qta_]').each( function(){
            qta = $(this).val().toEnglish();

            if( !$('#checked_'+r).is(':checked') || isNaN(qta) ){
                qta = 0;
            }

            subtot = $("#subtot_"+r).val();
            sconto = $("#sconto_"+r).val();
            iva = $("#iva_"+r).val();

            subtot = parseFloat(subtot);
            sconto = parseFloat(sconto);
            iva = parseFloat(iva);

            subtot = subtot-sconto;

            totale += subtot*qta+iva*qta;

            r++;

            tot_qta +=qta;
        });

        $('#totale').html( (totale.toLocale()) + " &euro;" );

        if( tot_qta>0 )
            $('#submit_btn').show();
        else
            $('#submit_btn').hide();
    }
</script>
