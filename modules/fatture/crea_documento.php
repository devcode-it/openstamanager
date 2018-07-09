<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

$data = [
    'ddt' => [
        'table' => 'dt_ddt', // Tabella del documento
        'rows' => 'dt_righe_ddt', // Tabella delle righe
        'id' => 'idddt', // ID nella tabella delle righe
        'condition' => '(id_riga_documento IS NOT NULL)', // Condizione per i seriali
    ],
    'ord' => [
        'table' => 'or_ordini',
        'rows' => 'or_righe_ordini',
        'id' => 'idordine',
        'condition' => '(id_riga_ddt IS NOT NULL OR id_riga_documento IS NOT NULL)',
    ],
    'fat' => [
        'table' => 'co_documenti',
        'rows' => 'co_righe_documenti',
        'id' => 'iddocumento',
        'condition' => '(1 = 2)',
        'allow-empty' => true,
    ],
];

$documento = get('documento');

if ($module['name'] == 'Ordini cliente' || $module['name'] == 'Ordini fornitore') {
    $pos = 'ord';
    $op = ($documento == 'ddt') ? 'ddt_da_ordine' : 'fattura_da_ordine';

    $head = tr('Ordine numero _NUM_');

    $dir = ($module['name'] == 'Ordini cliente') ? 'entrata' : 'uscita';
} elseif ($module['name'] == 'Ddt di vendita' || $module['name'] == 'Ddt di acquisto') {
    $pos = 'ddt';
    $op = 'fattura_da_ddt';

    $head = tr('Ddt numero _NUM_');

    $dir = ($module['name'] == 'Ddt di vendita') ? 'entrata' : 'uscita';
} else {
    $pos = 'fat';
    $op = 'nota_credito';

    $head = tr('Fattura numero _NUM_');

    $dir = 'entrata';
}

$table = $data[$pos]['table'];
$rows = $data[$pos]['rows'];
$id = $data[$pos]['id'];
$row = str_replace('id', 'id_riga_', $id);

if ($module['name'] == 'Ordini cliente') {
    $module_name = ($documento == 'ddt') ? 'Ddt di vendita' : 'Fatture di vendita';
} elseif ($module['name'] == 'Ordini fornitore') {
    $module_name = ($documento == 'ddt') ? 'Ddt di acquisto' : 'Fatture di acquisto';
} elseif ($module['name'] == 'Ddt di acquisto') {
    $module_name = 'Fatture di acquisto';
} else {
    $module_name = 'Fatture di vendita';
}

$op = !empty($get['op']) ? $get['op'] : $op;

$button = ($documento == 'ddt') ? tr('Crea ddt') : tr('Crea fattura');
$button = !empty($get['op']) ? tr('Aggiungi') : $button;

// Info documento
$rs = $dbo->fetchArray('SELECT * FROM '.$table.' WHERE id='.prepare($id_record));
$numero = !empty($rs[0]['numero_esterno']) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];
$idanagrafica = $rs[0]['idanagrafica'];
$idpagamento = $rs[0]['idpagamento'];
$idconto = $rs[0]['idconto'];

if (empty($idconto)) {
    $idconto = ($dir == 'entrata') ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');
}

/*
    Form di inserimento riga documento
*/
echo '
<p>'.str_replace('_NUM_', $numero, $head).'.</p>';

// Selezione articoli dell'ordine da portare nel ddt
$rs = $dbo->fetchArray('SELECT *, (qta - qta_evasa) AS qta_rimanente FROM '.$table.' INNER JOIN '.$rows.' ON '.$table.'.id='.$rows.'.'.$id.' WHERE '.$table.'.id='.prepare($id_record).' HAVING qta_rimanente > 0 ORDER BY `order`');

if (!empty($rs)) {
    echo '
<p>'.tr('Seleziona le righe e le relative quantità da inserire nel documento').'.</p>

<form action="'.$rootdir.'/editor.php?id_module='.Modules::get($module_name)['id'].(!empty($get['iddocumento']) ? '&id_record='.$get['iddocumento'] : '').'" method="post">
    <input type="hidden" name="'.$id.'" value="'.$id_record.'">
    <input type="hidden" name="idanagrafica" value="'.$idanagrafica.'">
    <input type="hidden" name="idconto" value="'.$idconto.'">
    <input type="hidden" name="idpagamento" value="'.$idpagamento.'">
    <input type="hidden" name="iddocumento" value="'.$id_record.'">

    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="dir" value="'.$dir.'">';

    if (empty($get['op'])) {
        echo '
    <div class="row">

        <div class="col-md-6">
            {[ "type": "date", "label": "'.tr('Data del documento').'", "name": "data", "required": 1, "value": "-now-" ]}
        </div>';

        if ($module_name == 'Fatture di vendita' || $module_name == 'Fatture di acquisto') {
            echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='.prepare(Modules::get($module_name)['id']).' ORDER BY name", "value": "'.$_SESSION['m'.Modules::get($module_name)['id']]['id_segment'].'" ]}
        </div>';
        }

        echo
    '</div>';
    }

    echo '
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
            <td>

                <input type="hidden" name="abilita_serial['.$r['id'].']" value="'.$r['abilita_serial'].'" />
                <input type="hidden" id="idarticolo_'.$i.'" name="idarticolo['.$r['id'].']" value="'.$r['idarticolo'].'" />
                <input type="hidden" id="descrizione_'.$i.'" name="descrizione['.$r['id'].']" value="'.$r['descrizione'].'" />';

        // Checkbox - da evadere?
        echo '
                <input type="checkbox" checked="checked" id="checked_'.$i.'" name="evadere['.$r['id'].']" value="on" onclick="ricalcola_subtotale_riga('.$i.');" />';

        echo nl2br($r['descrizione']);

        echo '
            </td>';

        // Q.tà rimanente
        echo '
        <td>
            <input type="hidden" id="qtamax_'.$i.'" value="'.($r['qta'] - $r['qta_evasa']).'" />
            <input type="hidden" id="um_'.$i.'" name="um['.$r['id'].']" value="'.$r['um'].'" />
            <p class="text-center">'.Translator::numberToLocale($r['qta_rimanente']).'</p>
        </td>';

        // Q.tà da evadere
        echo '
        <td>
            {[ "type": "number", "name": "qta_da_evadere['.$r['id'].']", "id": "qta_'.$i.'", "required": 1, "value": "'.$r['qta_rimanente'].'", "extra" : "onkeyup=\"ricalcola_subtotale_riga('.$i.');\"", "decimals": "qta", "min-value": "0" ]}
        </td>';

        // Subtotale
        $subtotale = $r['subtotale'] / $r['qta'] * ($r['qta'] - $r['qta_evasa']);
        $sconto = $r['sconto'] / $r['qta'] * ($r['qta'] - $r['qta_evasa']);
        $iva = $r['iva'] / $r['qta'] * ($r['qta'] - $r['qta_evasa']);

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
            $query = 'SELECT DISTINCT serial AS id, serial AS descrizione FROM mg_prodotti WHERE dir='.prepare($dir).' AND '.$row.' = '.prepare($r['id']).' AND serial IS NOT NULL AND serial NOT IN (SELECT serial FROM mg_prodotti AS t WHERE serial IS NOT NULL AND dir='.prepare($dir).' AND '.$data[$pos]['condition'].')';

            $values = $dbo->fetchArray($query);
            if (!empty($values)) {
                echo '
            {[ "type": "select", "name": "serial['.$r['id'].'][]", "id": "serial_'.$i.'", "multiple": 1, "values": "query='.$query.'", "value": "'.implode(',', array_column($values, 'id')).'", "extra": "data-maximum=\"'.intval($r['qta_rimanente']).'\"" ]}';
            } else {
                echo '-';
            }
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
    function ricalcola_subtotale_riga(r) {
        subtot = $("#subtot_" + r).val();
        sconto = $("#sconto_" + r).val();
        iva = $("#iva_" + r).val();

        qtamax = $("#qtamax_" + r).val() ? $("#qtamax_" + r).val() : 0;

        subtot = parseFloat(subtot);
        sconto = parseFloat(sconto);
        iva = parseFloat(iva);
        qtamax = parseFloat(qtamax);

        subtot = subtot - sconto;

        qta = $("#qta_" + r).val().toEnglish();

        // Se inserisco una quantità da evadere maggiore di quella rimanente, la imposto al massimo possibile
        if (qta > qtamax) {
            qta = qtamax;

            $('#qta_' + r).val(qta);
        }

        // Se tolgo la spunta della casella dell'evasione devo azzerare i conteggi
        if (isNaN(qta) || !$('#checked_' + r).is(':checked')) {
            qta = 0;
        }

        $("#serial_" + r).selectClear();
        $("#serial_" + r).select2("destroy");
        $("#serial_" + r).data('maximum', qta);
        start_superselect();

        subtotale = (subtot * qta + iva * qta).toLocale();

        $("#subtotale_" + r).html(subtotale + " &euro;");
        $("#subtotaledettagli_" + r).html((subtot * qta).toLocale() + " + " + (iva * qta).toLocale());

        ricalcola_totale();
    }

    function ricalcola_totale() {
        tot_qta = 0;
        r = 0;
        totale = 0.00;
        $('input[id*=qta_]').each(function() {
            qta = $(this).val().toEnglish();

            if (!$('#checked_' + r).is(':checked') || isNaN(qta)) {
                qta = 0;
            }

            subtot = $("#subtot_" + r).val();
            sconto = $("#sconto_" + r).val();
            iva = $("#iva_" + r).val();

            subtot = parseFloat(subtot);
            sconto = parseFloat(sconto);
            iva = parseFloat(iva);

            subtot = subtot - sconto;

            totale += subtot * qta + iva * qta;

            r++;

            tot_qta += qta;
        });

        $('#totale').html((totale.toLocale()) + " &euro;");

<?php

if (empty($data[$pos]['allow-empty'])) {
    echo '
        if (tot_qta > 0)
            $("#submit_btn").show();
        else
            $("#submit_btn").hide();';
}

?>
    }
</script>
