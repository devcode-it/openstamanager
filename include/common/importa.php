<?php

$id_record = $result['id_record'];
$id_documento_finale = $result['id_documento'];
$final_module = Modules::get($options['final_module']);
$original_module = Modules::get($options['original_module']);

$dir = $options['dir'];
$op = $options['op'];

$table = $options['sql']['table'];
$rows = $options['sql']['rows'];
$id_rows = $options['sql']['id_rows'];

// Info documento
$documento = $dbo->fetchOne('SELECT * FROM '.$table.' WHERE id = '.prepare($id_record));
$numero = !empty($documento['numero_esterno']) ? $documento['numero_esterno'] : $documento['numero'];
$id_anagrafica = $documento['idanagrafica'];
$id_pagamento = $documento['idpagamento'];
$id_conto = $documento['idconto'];

if (empty($documento)) {
    return;
}

$id_iva = $id_iva ?: setting('Iva predefinita');
if (empty($id_conto)) {
    $id_conto = ($dir == 'entrata') ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');
}

// Selezione articoli dell'ordine da portare nel ddt
$righe = $dbo->fetchArray('SELECT *, IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo),"") AS codice, (qta - qta_evasa) AS qta_rimanente FROM '.$table.' INNER JOIN '.$rows.' ON '.$table.'.id='.$rows.'.'.$id_rows.' WHERE '.$table.'.id='.prepare($id_record).' HAVING qta_rimanente > 0 OR is_descrizione = 1 ORDER BY `order`');

if (!empty($righe)) {
    echo '
    
<form action="'.ROOTDIR.'/controller.php?id_module='.$final_module['id'].(!empty($id_documento_finale) ? '&id_record='.$id_documento_finale : '').'" method="post">
    <input type="hidden" name="'.$options['id_importazione'].'" value="'.$id_record.'">
    
    <input type="hidden" name="idanagrafica" value="'.$id_anagrafica.'">
    <input type="hidden" name="idconto" value="'.$id_conto.'">
    <input type="hidden" name="idpagamento" value="'.$id_pagamento.'">
    <input type="hidden" name="iddocumento" value="'.$id_record.'">

    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="dir" value="'.$dir.'">';

    // Creazione fattura dal documento
    if (!empty($options['create_document'])) {
        echo '
    <div class="row">
        <input type="hidden" name="create_document" value="on"/>

        <div class="col-md-6">
            {[ "type": "date", "label": "'.tr('Data del documento').'", "name": "data", "required": 1, "value": "-now-" ]}
        </div>';

        if ($final_module['name'] == 'Fatture di vendita' || $final_module['name'] == 'Fatture di acquisto') {
            if ($op == 'nota_accredito' && !empty($segmenti)) {
                $segmento = $dbo->fetchOne("SELECT * FROM zz_segments WHERE predefined_accredito='1'");

                $id_segment = $segmento['id'];
            } else {
                $id_segment = $_SESSION['module_'.$final_module['id']]['id_segment'];
            }

            echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='.prepare($final_module['id']).' ORDER BY name", "value": "'.$id_segment.'" ]}
        </div>';
        }

        echo '
    </div>';
    }

    // Conto
    if (($final_module['name'] == 'Fatture di vendita' || $final_module['name'] == 'Fatture di acquisto') && !($original_module['name'] == 'Fatture di vendita' || $original_module['name'] == 'Fatture di acquisto')) {
        echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Conto').'", "name": "id_conto", "required": 1, "value": "'.$id_conto.'", "ajax-source": "'.($dir == 'entrata' ? 'conti-vendite' : 'conti-acquisti').'" ]}
        </div>
    </div>';
    }

    echo '
    <div class="clearfix"></div>
    <br>
    
    <p>'.tr('Seleziona le righe e le relative quantità da inserire nel documento').'.</p>

    <table class="table table-striped table-hover table-condensed">
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th width="10%">'.tr('Q.tà').'</th>
            <th width="15%">'.tr('Q.tà da evadere').'</th>
            <th width="20%">'.tr('Subtot.').'</th>';

    if (!empty($options['serials'])) {
        echo '
            <th width="20%">'.tr('Seriali').'</th>';
    }

    echo '
        </tr>';

    $totale = 0.00;

    foreach ($righe as $i => $r) {
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

        $descrizione = (!empty($r['codice']) ? $r['codice'].' - ' : '').$r['descrizione'];

        echo '&nbsp;'.nl2br($descrizione);

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
                {[ "type": "number", "name": "qta_da_evadere['.$r['id'].']", "id": "qta_'.$i.'", "required": 1, "value": "'.$r['qta_rimanente'].'", "decimals": "qta", "min-value": "0", "extra": "'.(($r['is_descrizione']) ? 'readonly' : '').' onkeyup=\"ricalcola_subtotale_riga('.$i.');\"" ]}
            </td>';

        // Subtotale
        $subtotale = $r['subtotale'] / $r['qta'] * ($r['qta'] - $r['qta_evasa']);
        $sconto = $r['sconto'] / $r['qta'] * ($r['qta'] - $r['qta_evasa']);
        $iva = $r['iva'] / $r['qta'] * ($r['qta'] - $r['qta_evasa']);

        echo '
            <td>
                <input type="hidden" id="subtot_'.$i.'" name="subtot['.$r['id'].']" value="'.str_replace('.', ',', ($r['subtotale'] / $r['qta'])).'" />
                <input type="hidden" id="sconto_'.$i.'" name="sconto['.$r['id'].']" value="'.str_replace('.', ',', ($r['sconto'] / $r['qta'])).'" />
                <input type="hidden" id="idiva_'.$i.'" name="idiva['.$r['id'].']" value="'.$r['idiva'].'" />
                <input type="hidden" id="iva_'.$i.'" name="iva['.$r['id'].']" value="'.str_replace('.', ',', ($r['iva'] / $r['qta'])).'" />

                <big id="subtotale_'.$i.'">'.Translator::numberToLocale($subtotale - $sconto + $iva).' &euro;</big><br/>

                <small style="color:#777;" id="subtotaledettagli_'.$i.'">'.Translator::numberToLocale($subtotale - $sconto).' + '.Translator::numberToLocale($iva).'</small>
            </td>';

        // Seriali
        if (!empty($options['serials'])) {
            echo '
            <td>';

            if (!empty($r['abilita_serial'])) {
                $query = 'SELECT DISTINCT serial AS id, serial AS descrizione FROM mg_prodotti WHERE dir='.prepare($dir).' AND '.$options['serials']['id_riga'].' = '.prepare($r['id']).' AND serial IS NOT NULL AND serial NOT IN (SELECT serial FROM mg_prodotti AS t WHERE serial IS NOT NULL AND dir='.prepare($dir).' AND '.$options['serials']['condition'].')';

                $values = $dbo->fetchArray($query);
                if (!empty($values)) {
                    echo '
                {[ "type": "select", "name": "serial['.$r['id'].'][]", "id": "serial_'.$i.'", "multiple": 1, "values": "query='.$query.'", "value": "'.implode(',', array_column($values, 'id')).'", "extra": "data-maximum=\"'.intval($r['qta_rimanente']).'\"" ]}';
                }
            }

            if (empty($r['abilita_serial']) || empty($values)) {
                echo '-';
            }

            echo '
            </td>';
        }

        echo '
        </tr>';

        $totale += $subtotale - $sconto + $iva;
    }

    // Totale
    echo '
        <tr>
            <td colspan="'.(!empty($options['serials']) ? 4 : 3).'" align="right" class="text-right">
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
                <i class="fa fa-plus"></i> '.$options['button'].'
            </button>
        </div>
    </div>
</form>';
} else {
    echo '
<p>'.tr('Non ci sono elementi da evadere').'...</p>';
}

echo '
<script src="'.ROOTDIR.'/lib/init.js"></script>';

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

        if (empty($options['sql']['allow-empty'])) {
            echo '
        if (tot_qta > 0)
            $("#submit_btn").show();
        else
            $("#submit_btn").hide();';
        }

        ?>
    }
</script>
