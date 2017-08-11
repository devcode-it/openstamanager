<?php

include_once __DIR__.'/../../core.php';

$module = Modules::getModule($id_module);

if ($module['name'] == 'Ordini cliente') {
    $dir = 'entrata';
    $module_name = 'Ddt di vendita';
} else {
    $dir = 'uscita';
    $module_name = 'Ddt di acquisto';
}

// Info documento
$rs = $dbo->fetchArray("SELECT * FROM or_ordini WHERE id=".prepare($id_record));
$numero = (!empty($rs[0]['numero_esterno'])) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];
$idanagrafica = $rs[0]['idanagrafica'];
$idpagamento = $rs[0]['idpagamento'];
$idconto = $rs[0]['idconto'];

/*
    Form di inserimento riga documento
*/

echo "<p>Ordine numero $numero</p>";
echo "Seleziona le righe che vuoi inserire nel ddt e la quantità:<br><br>";

echo '<form id="link_form" action="'.$rootdir.'/editor.php?id_module='.Modules::getModule($module_name)['id'].'&id_record='.$id_record."\" method=\"post\">";

// Altri id utili
echo "	<input type='hidden' name='idordine' value='".$id_record."' />";
echo "	<input type='hidden' name='idanagrafica' value='".$idanagrafica."' />";
echo "	<input type='hidden' name='idconto' value='".$idconto."' />";
echo "	<input type='hidden' name='idpagamento' value='".$idpagamento."' />";

echo "	<input type='hidden' name='op' value='ddt_da_ordine'>";
echo "	<input type='hidden' name='backto' value='record-edit'>";
echo "	<input type='hidden' name='dir' value='".$dir."'>";

// Selezione articoli dell'ordine da portare nel ddt
$query = "SELECT * FROM or_ordini INNER JOIN or_righe_ordini ON or_ordini.id=or_righe_ordini.idordine WHERE or_ordini.id='".$id_record."'";
$rs = $dbo->fetchArray($query);
$n = sizeof($rs);

if ($n > 0) {
    $show_btn = true;

    echo "	<div class='form'>";
    echo "		<div class='col-md-4'>";
    echo "			<label>Data ddt</label>";
    echo "			<input type='text' class='form-control text-center datepicker ' name='data' id='data2' value='".date('d/m/Y')."'>";
    echo "		</div>"; ?>

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
                echo "<tr>";
        echo "<td class='text-left' >";

        echo "<input type='hidden' name='idrigaordine[]' value=\"".$rs[$i]['id']."\" />";
        echo "<input type='hidden' id='idarticolo_".$i."' name='idarticolo[]' value=\"".$rs[$i]['idarticolo']."\" />";
        echo "<input type='hidden' id='descrizione_".$i."' name='descrizione[]' value=\"".$rs[$i]['descrizione']."\" />";

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
        echo "</small>";
        echo "</td>";

                // Q.tà rimanente
                echo "<td class='text-left' id='rimanente_".$i."'>";
        echo "<input type='hidden' id='qtamax_".$i."' value='".($rs[$i]['qta'] - $rs[$i]['qta_evasa'])."' />";
        echo "<input type='hidden' id='um_".$i."' name='um[]' value='".$rs[$i]['um']."' />";
        echo($rs[$i]['qta'] - $rs[$i]['qta_evasa'])."";
        echo "</td>";

                // Q.tà da evadere
                echo "<td class='text-left' >";
        echo "<input class='form-control inputmask-decimal' type='text'  id='qta_".$i."' name='qta_da_evadere[]' value='".($rs[$i]['qta'] - $rs[$i]['qta_evasa'])."' onkeyup=\"ricalcola_subtotale_riga(".$i.");\" />";
        echo "</td>";

                // Subtotale
                $subtotale = $rs[$i]['subtotale'] / $rs[$i]['qta'] * ($rs[$i]['qta'] - $rs[$i]['qta_evasa']);
        $sconto = $rs[$i]['sconto'] / $rs[$i]['qta'] * ($rs[$i]['qta'] - $rs[$i]['qta_evasa']);
        $iva = $rs[$i]['iva'] / $rs[$i]['qta'] * ($rs[$i]['qta'] - $rs[$i]['qta_evasa']);
        echo "<td class='text-right'>";
        echo "	<input type='hidden' id='subtot_".$i."' name='subtot[]' value=\"".Translator::numberToLocale($rs[$i]['subtotale'] / $rs[$i]['qta'])."\" />";
        echo "	<input type='hidden' id='sconto_".$i."' name='sconto[]' value=\"".Translator::numberToLocale($rs[$i]['sconto'] / $rs[$i]['qta'])."\" />";
        echo "	<input type='hidden' id='idiva_".$i."' name='idiva[]' value=\"".$rs[$i]['idiva']."\" />";
        echo "	<input type='hidden' id='iva_".$i."' name='iva[]' value=\"".Translator::numberToLocale($rs[$i]['iva'] / $rs[$i]['qta'])."\" />";
        echo "	<big id='subtotale_".$i."'>".Translator::numberToLocale($subtotale - $sconto + $iva)." &euro;</big><br><small class='help-block' id='subtotaledettagli_".$i."'>".Translator::numberToLocale($subtotale - $sconto).' + '.Translator::numberToLocale($iva)."</small>";
        echo "</td>";

                // Checkbox - da evadere?
                echo "<td class='text-left'>";
        echo "	<input type='checkbox' checked='checked' id='checked_".$i."' name='evadere[]' value='on' onclick=\"ricalcola_subtotale_riga(".$i.");\" />";
        echo "</td></tr>";

        $totale += $subtotale - $sconto + $iva;
    }

            // Totale
            echo "<tr><td colspan='3' align='right' class='text-right'>";
    echo "	<b>Totale:</b>";
    echo "</td>";

    echo "<td class='text-right'>";
    echo "	<big id='totale'>".Translator::numberToLocale($totale)." &euro;</big>";
    echo "</td><td></td></tr>";
    echo "</table>";
    echo "</div>";
} else {
    $show_btn = false;
    echo '		<b>'._('Non ci sono articoli da evadere in questo ordine')."...</b><br>";
}

echo "		<div class=\"clearfix\"></div>";
echo "	</div>";
echo '
<!-- PULSANTI -->
<div class="row">
    <div class="col-md-12 text-right">';

if ($show_btn) {
    if ($dir == 'entrata') {
        echo '
        <a onclick="creaddt_vendita();" class="btn btn-primary"><i class="fa fa-plus"></i> '._('Crea ddt di vendita').'</a>';
    } else {
        echo '
        <a onclick="creaddt_acquisto();" class="btn btn-primary"><i class="fa fa-plus"></i> '._('Crea ddt di acquisto').'</a>';
    }
}
echo '
    </div>
</div>';

echo "</form>";
?>


<script type="text/javascript">
$(document).ready( function(){
    start_superselect();
    start_inputmask();
    $('.datepicker').datepicker();

});

function creaddt_vendita(){
    $("#link_form").submit();
}

function creaddt_acquisto(){
    $("#link_form").submit();
}

function ricalcola_subtotale_riga( r ){
    subtot = $("#subtot_"+r).val().toEnglish();

    sconto = $("#sconto_"+r).val().toEnglish();
    subtot = subtot-sconto;

    qta = $("#qta_"+r).val().toEnglish();

    if( isNaN(qta) ){
        qta = 0;
    }

    qtamax = $("#qtamax_"+r).val().toEnglish();

    if( isNaN(qtamax) ){
        qtamax = 0;
    }

    iva = $("#iva_"+r).val().toEnglish();


    // Se inserisco una quantità da evadere maggiore di quella rimanente, la imposto al massimo possibile
    if( qta>qtamax ){
        qta = qtamax.toFixedLocale(2);
        $('#qta_'+r).val( qta );
    }

    // Se tolgo la spunta della casella dell'evasione devo azzerare i conteggi
    if( !$('#checked_'+r).is(':checked') ){
        qta = 0;
    }

    subtotale = (subtot*qta+iva*qta).toFixedLocale(2);

    $("#subtotale_"+r).html(subtotale+" &euro;");
    $("#subtotaledettagli_"+r).html( (subtot*qta).toFixed(2)+" + " + (iva*qta).toFixed(2) );

    ricalcola_totale();
}


function ricalcola_totale(){
    r = 0;
    totale = 0.00;
    $('input[id*=qta_]').each( function(){
        qta = $(this).val().toEnglish();

        if( !$('#checked_'+r).is(':checked') || isNaN(qta) ){
            qta = 0;
        }

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
