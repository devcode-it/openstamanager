<?php

include_once __DIR__.'/../../core.php';

$module = Modules::getModule($id_module);

if ($module['name'] == 'Ordini cliente') {
    $dir = 'entrata';
    $module_name = 'Fatture di vendita';
} else {
    $dir = 'uscita';
    $module_name = 'Fatture di acquisto';
}

// Info documento
$rs = $dbo->fetchArray('SELECT * FROM or_ordini WHERE id='.prepare($id_record));
(!empty($rs[0]['numero_esterno'])) ? $numero = $rs[0]['numero_esterno'] : $numero = $rs[0]['numero'];
$idanagrafica = $rs[0]['idanagrafica'];
$idpagamento = $rs[0]['idpagamento'];
$idconto = $rs[0]['idconto'];

/*
    Form di inserimento riga documento
*/

echo "<h4>Ordine numero $numero</h4>\n";
echo "Seleziona le righe che vuoi inserire nella fattura e la quantità:<br><br>\n";

echo '<form id="link_form" action="'.$rootdir.'/editor.php?id_module='.Modules::getModule($module_name)['id'].'&id_record='.$id_record."\" method=\"post\">\n";

// Altri id utili
echo "	<input type='hidden' name='idordine' value='".$id_record."' />\n";
echo "	<input type='hidden' name='idanagrafica' value='".$idanagrafica."' />\n";
echo "	<input type='hidden' name='idconto' value='".$idconto."' />\n";
echo "	<input type='hidden' name='idpagamento' value='".$idpagamento."' />\n";

echo "	<input type='hidden' name='op' value='fattura_da_ordine'>\n";
echo "	<input type='hidden' name='backto' value='record-edit'>\n";
echo "	<input type='hidden' name='dir' value='".$dir."'>\n";

// Selezione articoli dell'ordine da portare nella fattura
$query = "SELECT * FROM or_ordini INNER JOIN or_righe_ordini ON or_ordini.id=or_righe_ordini.idordine WHERE or_ordini.id='".$id_record."' GROUP BY idgruppo";
$rs = $dbo->fetchArray($query);
$n = sizeof($rs);

if ($n > 0) {
    $show_btn = true;

    echo "	<div class='form'>\n";
    echo "		<div class='col-md-4'>\n";
    echo "			<label>Data fattura</label>\n";
    echo "			<input type='text' class='form-control text-center datepicker ' name='data' id='data2' value='".date('d/m/Y')."' />\n";
    echo "		</div>\n"; ?>

    <div class="clearfix"></div>
    <br>

    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped table-hover table-condensed">

            <tr>
                <th>Descrizione</th>
                <th width="10%">Q.tà</th>
                <th width="15%">Q.tà da evadere</th>
                <th width="20%">Subtot.</th>
                <th width="10%">Da evadere</th>
            </tr>

            <?php
            $totale = 0.00;

    for ($i = 0; $i < $n; ++$i) {
        // Descrizione
                echo "		<tr>\n";
        echo "		<td class='text-left' >\n";

        echo "		<input type='hidden' name='idrigaordine[]' value=\"".$rs[$i]['id']."\" />\n";
        echo "			<input type='hidden' id='idarticolo_".$i."' name='idarticolo[]' value=\"".$rs[$i]['idarticolo']."\" />\n";
        echo "			<input type='hidden' id='descrizione_".$i."' name='descrizione[]' value=\"".$rs[$i]['descrizione']."\" />\n";

        echo nl2br($rs[$i]['descrizione']).'<small>';
        if ($rs[$i]['lotto'] != '') {
            echo '<br>Lotto: '.$rs[$i]['lotto'];
        }
        if ($rs[$i]['serial'] != '') {
            echo '<br>SN: '.$rs[$i]['serial'];
        }
        if ($rs[$i]['altro'] != '') {
            echo '<br>'.$rs[$i]['altro'];
        }
        echo "			</small>\n";
        echo "		</td>\n";

                // Q.tà rimanente
                echo "		<td class='text-left' id='rimanente_".$i."'>\n";
        echo "			<input type='hidden' id='qtamax_".$i."' value='".($rs[$i]['qta'] - $rs[$i]['qta_evasa'])."' />\n";
        echo "			<input type='hidden' id='um_".$i."' name='um[]' value='".$rs[$i]['um']."' />\n";
        echo($rs[$i]['qta'] - $rs[$i]['qta_evasa'])."\n";
        echo "		</td>\n";

                // Q.tà da evadere
                echo "		<td class='text-left' >\n";
        echo "			<input class='form-control inputmask-decimal' type='text'  id='qta_".$i."' name='qta_da_evadere[]' value='".($rs[$i]['qta'] - $rs[$i]['qta_evasa'])."' onkeyup=\"ricalcola_subtotale_riga(".$i.");\" />\n";
        echo "		</td>\n";

                // Subtotale
                $subtotale = $rs[$i]['subtotale'] / $rs[$i]['qta'] * ($rs[$i]['qta'] - $rs[$i]['qta_evasa']);
        $sconto = $rs[$i]['sconto'] / $rs[$i]['qta'] * ($rs[$i]['qta'] - $rs[$i]['qta_evasa']);
        $iva = $rs[$i]['iva'] / $rs[$i]['qta'] * ($rs[$i]['qta'] - $rs[$i]['qta_evasa']);
        echo "		<td class='text-right'>\n";
        echo "			<input type='hidden' id='subtot_".$i."' name='subtot[]' value=\"".Translator::numberToLocale($rs[$i]['subtotale'] / $rs[$i]['qta'])."\" />\n";
        echo "			<input type='hidden' id='sconto_".$i."' name='sconto[]' value=\"".Translator::numberToLocale($rs[$i]['sconto'] / $rs[$i]['qta'])."\" />\n";
        echo "			<input type='hidden' id='idiva_".$i."' name='idiva[]' value=\"".$rs[$i]['idiva']."\" />\n";
        echo "			<input type='hidden' id='iva_".$i."' name='iva[]' value=\"".Translator::numberToLocale($rs[$i]['iva'] / $rs[$i]['qta'])."\" />\n";
        echo "			<big id='subtotale_".$i."'>".Translator::numberToLocale($subtotale - $sconto + $iva)." &euro;</big><br><small class='help-block' id='subtotaledettagli_".$i."'>".Translator::numberToLocale($subtotale - $sconto).' + '.Translator::numberToLocale($iva)."</small>\n";
        echo "		</td>\n";

                // Checkbox - da evadere?
                echo "		<td class='text-left'>\n";
        echo "			<input type='checkbox' checked='checked' id='checked_".$i."' name='evadere[]' value='on' onclick=\"ricalcola_subtotale_riga(".$i.");\" />\n";
        echo "		</td></tr>\n";

        $totale += $subtotale - $sconto + $iva;
    }

            // Totale
            echo "		<tr><td colspan='3' align='right' class='text-right'>\n";
    echo "			<b>Totale:</b>\n";
    echo "		</td>\n";

    echo "		<td class='text-right'>\n";
    echo "			<big id='totale'>".Translator::numberToLocale($totale)." &euro;</big>\n";
    echo "		</td><td></td></tr>\n";
    echo "		</table>\n";
    echo "	</div>\n";
    echo "</div>\n";
} else {
    $show_btn = false;
    echo '<b>'._('Non ci sono articoli da evadere in questo ordine')."...</b><br>\n";
}
?>



<?php
if ($show_btn) {
    if ($dir == 'entrata') {
        echo "<a onmouseover=\"this.style.cursor='pointer';\" id='submit_btn' onclick=\"creafattura_vendita();\"  class=\"btn btn-primary pull-right\" ><i class=\"fa fa-plus\"></i> Crea fattura di vendita</a>\n";
    } else {
        echo "<a onmouseover=\"this.style.cursor='pointer';\" id='submit_btn' onclick=\"creafattura_acquisto();\"  class=\"btn btn-primary pull-right\" ><i class=\"fa fa-plus\"></i> Crea fattura di acquisto</a>\n";
    }
}

echo "</form>\n";
?>
<div class="clearfix"></div>

<script type="text/javascript">
$(document).ready( function(){
    start_superselect();
    start_inputmask();
    $('.datepicker').datepicker();

});

function creafattura_vendita(){
    $("#link_form").submit();
}

function creafattura_acquisto(){
    $("#link_form").submit();
}

function ricalcola_subtotale_riga( r ){
    subtot = force_decimal( $("#subtot_"+r).val() );

    sconto = force_decimal( $("#sconto_"+r).val() );
    subtot = subtot-sconto;

    qta = force_decimal( $("#qta_"+r).val() );

    if( isNaN(qta) ){
        qta = 0;
    }

    qtamax = force_decimal( $("#qtamax_"+r).val() );

    if( isNaN(qtamax) ){
        qtamax = 0;
    }

    iva = force_decimal( $("#iva_"+r).val() );


    // Se inserisco una quantità da evadere maggiore di quella rimanente, la imposto al massimo possibile
    if( qta>qtamax ){
        qta = qtamax.toFixed(2).toString().replace('.', ',');
        $('#qta_'+r).val( qta );
    }

    // Se tolgo la spunta della casella dell'evasione devo azzerare i conteggi
    if( !$('#checked_'+r).is(':checked') ){
        qta = 0;
    }

    subtotale = (subtot*qta+iva*qta).toFixed(2).toString();
    subtotale = subtotale.replace( '.', ',' );

    $("#subtotale_"+r).html(subtotale+" &euro;");
    $("#subtotaledettagli_"+r).html( (subtot*qta).toFixed(2)+" + " + (iva*qta).toFixed(2) );

    ricalcola_totale();
}


function ricalcola_totale(){
    r = 0;
    totale = 0.00;
    $('input[id*=qta_]').each( function(){
        qta = force_decimal( $(this).val() );

        if( !$('#checked_'+r).is(':checked') || isNaN(qta) ){
            qta = 0;
        }

        subtot = force_decimal( $("#subtot_"+r).val() );

        sconto = force_decimal( $("#sconto_"+r).val() );
        subtot = subtot-sconto;

        iva = force_decimal( $("#iva_"+r).val() );

        totale += subtot*qta+iva*qta;

        r++;
    });

    $('#totale').html( (totale.toFixed(2).replace( '.', ',' )) + " &euro;" );

    if( totale==0 )
        $('#submit_btn').hide();
    else
        $('#submit_btn').show();
}
</script>
