<?php

include_once __DIR__.'/../../core.php';

if ($_GET['dir'] == 'entrata') {
    $dir = 'entrata';
    $module_name = 'Fatture di vendita';
} else {
    $dir = 'uscita';
    $module_name = 'Fatture di acquisto';
}

$idddt = $get['idddt'];

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
<p>'.str_replace('_NUM_', $numero, tr('Ddt numero _NUM_')).'</p>';

//Selezione articoli del ddt da portare nella fattura, escludo quelli completamente evasi
$query = 'SELECT *, (qta - qta_evasa) AS qta_rimanente FROM dt_ddt INNER JOIN dt_righe_ddt ON dt_ddt.id=dt_righe_ddt.idddt WHERE dt_ddt.id='.prepare($idddt).' HAVING qta_rimanente > 0';
$rs = $dbo->fetchArray($query);

if (!empty($rs)) {
    echo '
<p>'.tr('Seleziona le righe che vuoi inserire nella fattura e la relativa quantità').'.</p><br/><br/>';

    echo '
<form id="link_form" action="'.$rootdir.'/editor.php?id_module='.Modules::getModule($module_name)['id'].'&id_record='.$idddt.'" method="post">
    <input type="hidden" name="idddt" value="'.$idddt.'">
    <input type="hidden" name="idanagrafica" value="'.$idanagrafica.'">
    <input type="hidden" name="idconto" value="'.$idconto.'">
    <input type="hidden" name="idpagamento" value="'.$idpagamento.'">

    <input type="hidden" name="op" value="fattura_da_ddt">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="dir" value="'.$dir.'">';

    echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "date", "label": "'.tr('Data fattura').'", "name": "data", "required": 1, "value": "-now-" ]}
        </div>
    </div>';

    echo '
    <div class="clearfix"></div>
    <br>

    <table class="table table-striped table-hover table-condensed">
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th width="10%%">'.tr('Q.tà').'</th>
            <th width="15%">'.tr('Q.tà da evadere').'</th>
            <th width="20%">'.tr('Subtot.').'</th>
            <th width="10%">'.tr('Da evadere').'</th>
            <th width="2%"></th>
        </tr>';
    $totale = 0.00;

    foreach ($rs as $i => $r) {
        // Descrizione
    echo '
        <tr>
            <td>
                <input type="hidden" name="idrigaddt[]" value="'.$r['id'].'" />
                <input type="hidden" id="idarticolo_'.$i.'" name="idarticolo[]" value="'.$r['idarticolo'].'" />
                <input type="hidden" id="descrizione_'.$i.'" name="descrizione[]"" value="'.$r['descrizione'].'" />

                '.nl2br($r['descrizione']).'
                <small>';
        if ($r['lotto'] != '') {
            echo '<br/>'.tr('Lotto').': '.$r['lotto'];
        }
        if ($r['serial'] != '') {
            echo '<br/>'.tr('SN').': '.$r['serial'];
        }
        if ($r['altro'] != '') {
            echo '<br/>'.$r['altro'];
        }
        echo '
                </small>
            </td>';

    // Q.tà rimanente
    echo '
            <td id=truerimanente_'.$i.'">
                <input type="hidden" id="qtamax_'.$i.'" value="'.($r['qta'] - $r['qta_evasa']).'" />
                <input type="hidden" id="um_'.$i.'" name="um[]" value="'.$r['um'].'" />
                <p class="text-center">'.$r['qta_rimanente'].'</p>
            </td>';

    // Q.tà da evadere
    echo '
            <td>
                <input class="form-control inputmask-decimal" type="text"  id="qta_'.$i.'" name="qta_da_evadere[]" value="'.($r['qta'] - $r['qta_evasa']).'" onkeyup="ricalcola_subtotale_riga('.$i.');" />
            </td>';

    // Subtotale
    $subtotale = $r['subtotale'] / $r['qta'] * ($r['qta'] - $r['qta_evasa']);
    $sconto = $r['sconto'] / $r['qta'] * ($r['qta'] - $r['qta_evasa']);
    $iva = $r['iva'] / $r['qta'] * ($r['qta'] - $r['qta_evasa']);

    echo '
            <td>
                <input type="hidden" id="subtot_'.$i.'" name="subtot[]" value="'.Translator::numberToLocale($r['subtotale'] / $r['qta']).'" />
                <input type="hidden" id="sconto_'.$i.'" name="sconto[]" value="'.Translator::numberToLocale($r['sconto'] / $r['qta']).'" />
                <input type="hidden" id="idiva_'.$i.'" name="idiva[]" value="'.$r['idiva'].'" />
                <input type="hidden" id="iva_'.$i.'" name="iva[]" value="'.Translator::numberToLocale($r['iva'] / $r['qta']).'" />

                <big id="subtotale_'.$i.'">'.Translator::numberToLocale($subtotale - $sconto + $iva).' &euro;</big><br/>

                <small style="color:#777;" id="subtotaledettagli_'.$i.'">'.Translator::numberToLocale($subtotale - $sconto).' + '.Translator::numberToLocale($iva).'</small>
            </td>';

    // Checkbox - da evadere?
    echo '
            <td>
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
                <i class="fa fa-plus"></i> '.($dir == 'entrata' ? tr('Crea fattura di vendita') : tr('Crea fattura di acquisto')).'
            </button>
		</div>
    </div>';

    echo '
</form>';
} else {
    echo '
    <b>'.tr('Non ci sono articoli da evadere in questo ddt').'...</b>';
}

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';

?>

<script type="text/javascript">
	function ricalcola_subtotale_riga( r ){
		subtot = $("#subtot_"+r).val().toEnglish();

		sconto = $("#sconto_"+r).val().toEnglish();

		subtot = subtot-sconto;

		qta = $("#qta_"+r).val().toEnglish();
		if( isNaN(qta) )
			qta = 0;

		qtamax = $("#qtamax_"+r).val().toEnglish();
		if( isNaN(qtamax) )
			qtamax = 0;

		iva = $("#iva_"+r).val().toEnglish();

		// Se inserisco una quantità da evadere maggiore di quella rimanente, la imposto al massimo possibile
		if( qta>qtamax ){
			qta = qtamax;
			$('#qta_'+r).val( qta );
		}

		// Se tolgo la spunta della casella dell'evasione devo azzerare i conteggi
		if( !$('#checked_'+r).is(':checked') )
			qta = 0;

		subtotale = (subtot*qta+iva*qta).toFixedLocale();

		$("#subtotale_"+r).html(subtotale+" &euro;");
		$("#subtotaledettagli_"+r).html( (subtot*qta).toFixed(2)+" + " + (iva*qta).toFixed(2) );

		ricalcola_totale();
	}


	function ricalcola_totale(){
		r = 0;
		totale = 0.00;
		$('input[id*=qta_]').each( function(){
			qta = $(this).val().toEnglish();

			if( !$('#checked_'+r).is(':checked') || isNaN(qta) )
				qta = 0;

			subtot = $("#subtot_"+r).val().toEnglish();

            sconto = $("#sconto_"+r).val().toEnglish();

			subtot = subtot-sconto;

			iva = $("#iva_"+r).val().toEnglish();

			totale += subtot*qta+iva*qta;

			r++;
		});

		$('#totale').html( (totale.toFixedLocale()) + " &euro;" );

		if( totale==0 )
			$('#submit_btn').hide();
		else
			$('#submit_btn').show();
	}
</script>
