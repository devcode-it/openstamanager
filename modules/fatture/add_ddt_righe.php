<?php

include_once __DIR__.'/../../core.php';

$module = Modules::getModule($id_module);

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

$idddt = get('idddt');

// Info documento
$q = 'SELECT * FROM dt_ddt WHERE id='.prepare($idddt);
$rs = $dbo->fetchArray($q);

$numero = $rs[0]['numero'];
$idanagrafica = $rs[0]['idanagrafica'];
$idpagamento = $rs[0]['idpagamento'];
$idconto = $rs[0]['idconto'];

/*
    Form di inserimento riga documento
*/
echo '
<form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="add_ddt">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="dir" value="'.$dir.'">
    <input type="hidden" name="idddt" value="'.$idddt.'">';

// Selezione righe ddt da portare nella fattura
$query = "SELECT *, IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo),'') AS codice FROM dt_ddt INNER JOIN dt_righe_ddt ON dt_ddt.id=dt_righe_ddt.idddt WHERE dt_ddt.id=".prepare($idddt).' AND (qta - qta_evasa) > 0';
$rs = $dbo->fetchArray($query);

if (!empty($rs)) {
    echo '
    <p>'._('Seleziona le righe che vuoi inserire nella fattura e la quantità').':</p>

    <table class="table table-striped table-hover table-condensed">
        <tr>
            <th>'._('Descrizione').'</th>
            <th width="10%">'._('Q.tà').'</th>
            <th width="20%" class="text-center">'._('Q.tà da evadere').'</th>
            <th width="25%" class="text-right">'._('Subtot.').'</th>
            <th width="15%" class="text-right">'._('Da evadere').'</th>
        </tr>';

    $totale = 0.00;

    foreach ($rs as $i => $r) {
        // Descrizione
        echo '
        <tr>
            <td class="text-left">
                <input type="hidden" name="idrigaddt[]" value="'.$r['id'].'"/>
                <input type="hidden" id="idarticolo_'.$i.'" name="idarticolo[]" value="'.$r['idarticolo'].'"/>
                <input type="hidden" id="descrizione_'.$i.'" name="descrizione[]" value="'.$r['descrizione'].'"/>';

        if ($r['codice'] != '') {
            echo '
                <b>'.$r['codice'].'</b><br/>';
        }

        echo '
                '.nl2br($r['descrizione']).'
                <small>';

        if ($r['lotto'] != '') {
            echo '
                    <br/>Lotto: '.$r['lotto'];
        }
        if ($r['serial'] != '') {
            echo '
                    <br/>SN: '.$r['serial'];
        }
        if ($r['altro'] != '') {
            echo '
                    <br/>'.$r['altro'];
        }

        echo '
                </small>
            </td>';

        // Q.tà rimanente
        echo '
            <td class="text-center" id="rimanente_'.$i.'">
                <input type="hidden" id="qtamax_'.$i.'" value="'.($r['qta'] - $r['qta_evasa']).'"/>
                <input type="hidden" id="um_'.$i.'" name="um[]" value="'.$r['um'].'"/>
                '.Translator::numberToLocale(($r['qta'] - $r['qta_evasa'])).'
            </td>';

        // Q.tà da evadere
        echo '
            <td class="text-left">
                {[ "type": "number", "name": "qta_da_evadere[]", "id": "qta_'.$i.'", "value": "'.($r['qta'] - $r['qta_evasa']).'", "decimals": "qta", "extra": "onkeyup=\"ricalcola_subtotale_riga('.$i.');\"" ]}
            </td>';

        // Subtotale
        $subtotale = $r['subtotale'] / $r['qta'] * ($r['qta'] - $r['qta_evasa']);
        $sconto = $r['sconto'] / $r['qta'] * ($r['qta'] - $r['qta_evasa']);
        $iva = $r['iva'] / $r['qta'] * ($r['qta'] - $r['qta_evasa']);

        echo '
            <td  class="text-right">
                <input type="hidden" id="subtot_'.$i.'" name="subtot[]" value="'.Translator::numberToLocale($r['subtotale'] / $r['qta']).'" />
                <input type="hidden" id="sconto_'.$i.'" name="sconto[]" value="'.Translator::numberToLocale($r['sconto'] / $r['qta']).'" />
                <input type="hidden" id="idiva_'.$i.'" name="idiva[]" value="'.$r['idiva'].'" />
                <input type="hidden" id="iva_'.$i.'" name="iva[]" value="'.Translator::numberToLocale($r['iva'] / $r['qta']).'" />

                <big id="subtotale_'.$i.'">'.Translator::numberToLocale($subtotale - $sconto + $iva).' &euro;</big><br/><small style="color:#777;" id="subtotaledettagli_'.$i.'">'.Translator::numberToLocale($subtotale - $sconto).' + '.Translator::numberToLocale($iva).'</small>
            </td>';

        // Checkbox - da evadere?
        echo '
            <td class="text-right">
                <input type="checkbox" checked="checked" id="checked_'.$i.'" name="evadere[]" value="on" onclick="ricalcola_subtotale_riga('.$i.');" />
            </td>
        </tr>';

        $totale += $subtotale - $sconto + $iva;
    }

    // Totale
    echo '
        <tr>
            <td colspan="4" align="right" class="text-right">
                <b>Totale:</b>
            </td>
            <td  class="text-right" colspan="2">
                <big id="totale">'.Translator::numberToLocale($totale).' &euro;</big>
            </td>
        </tr>';
    echo '
    </table>';
} else {
    echo '
    <p>'._('Non ci sono articoli da evadere in questo ddt').'...</p>';
}

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '._('Aggiungi').'</button>
		</div>
    </div>
</form>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';

?>

<script type="text/javascript">
    function ricalcola_subtotale_riga( r ){
        subtot = $("#subtot_" + r).val().toEnglish();

        sconto = $("#sconto_" + r).val().toEnglish();
        subtot = subtot - sconto;

        qta = $("#qta_" + r).val().toEnglish();
        if(isNaN(qta)) qta = 0;

        qtamax = $("#qtamax_" + r).val().toEnglish();
        if(isNaN(qtamax)) qtamax = 0;

        iva = $("#iva_" + r).val().toEnglish();

        // Se inserisco una quantità da evadere maggiore di quella rimanente, la imposto al massimo possibile
        if(qta > qtamax){
            qta = qtamax;
            $('#qta_' + r).val(qta);
        }

        if(qta == 0) $('#checked_' + r).prop("checked", false);

        // Se tolgo la spunta della casella dell'evasione devo azzerare i conteggi
        if(!$('#checked_' + r).is(':checked')) qta = 0;

        subtotale = (subtot * qta + iva * qta).toFixedLocale();

        $("#subtotale_" + r).html(subtotale + " &euro;");
        $("#subtotaledettagli_" + r).html((subtot * qta).toFixed(2) + " + " + (iva * qta).toFixed(2));

        ricalcola_totale();
    }

    function ricalcola_totale(){
        r = 0;
        totale = 0.00;
        $('input[id*=qta_]').each( function(){
            qta = $(this).val().toEnglish();

            if( !$('#checked_' + r).is(':checked') || isNaN(qta) )
                qta = 0;

            subtot = $("#subtot_" + r).val().toEnglish();

            sconto = $("#sconto_" + r).val().toEnglish();

            subtot = subtot-sconto;

            iva = $("#iva_" + r).val().toEnglish();

            totale += subtot*qta+iva*qta;
            r++;
        });

        $('#totale').html( (totale.toFixedLocale()) + " &euro;" );
    }
</script>
