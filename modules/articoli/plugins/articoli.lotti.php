<?php

include_once __DIR__.'/../../../core.php';

// Gestione dei lotti degli articoli

echo '
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">'.tr('Produzione').'</h3>
    </div>
    <div class="panel-body">';

$search_lotto = $get['search_lotto'];
$search_serial = $get['search_serial'];
$search_altro = $get['search_altro'];

// Calcolo prossimo lotto e serial number
$rs = $dbo->fetchArray('SELECT MAX(lotto) AS max_lotto, MAX(serial) AS max_serial, MAX(altro) AS max_altro FROM mg_prodotti WHERE idarticolo='.prepare($id_record));
$max_lotto = $rs[0]['max_lotto'];
$max_serial = $rs[0]['max_serial'];
$max_altro = $rs[0]['max_altro'];

//$next_lotto = get_next_code($max_lotto);
$next_serial = get_next_code($max_serial);
//$next_altro = get_next_code($max_altro);

echo '
        <form action="" method="post" role="form">
            <input type="hidden" name="backto" value="record-edit">
            <input type="hidden" name="op" value="addprodotto">
            <input type="hidden" name="id_record" value="'.$id_record.'">';

// Campi di inserimento lotti
echo '
            <div class="row form-group">
                <div class="col-md-12">
                    <h4>'.tr('Inserimento nuovi prodotti').'</h4>
                </div>
            </div>';

/*
// Lotto
echo '
            <div class="row form-group">
                <label class="col-md-2 control-label" for="lotto_start">'.tr('Lotto da').':</label>
                <div class="col-md-2">
                    <input type="text" class="form-control input-md" name="lotto_start" onkeyup="$(\'input[name=lotto_end]\').val( $(\'input[name=lotto_start]\').val() ); $(\'#warn_lotto\').hide(); ricalcola_totale_prodotti();" value="'.$next_lotto.'">
                </div>

                <label class="col-md-1 control-label text-center" for="lotto_end"> <i class="fa fa-arrow-circle-right fa-2x"></i> </label>
                <div class="col-md-2">
                    <input type="text" class="form-control input-md" name="lotto_end" onkeyup="check_progressivo( $(\'input[name=lotto_start]\'), $(\'input[name=lotto_end]\'), $(\'#warn_lotto\'), $(\'#inserisci\') );" value="'.$next_lotto.'">
                </div>';
if (!empty($max_lotto)) {
    echo '
                <div class="col-md-3">
                    <p id="warn_lotto" class="text-danger"><b>'.tr('Ultimo lotto inserito').': </b> '.$max_lotto.'</p>
                </div>';
}
echo '
            </div>';
*/
// Serial
echo '
            <div class="row form-group">
                <label class="col-md-2 control-label" for="serial_start">'.tr('Serial number da').':</label>
                <div class="col-md-2">
                    <input type="text" class="form-control input-md" name="serial_start" onkeyup="$(\'input[name=serial_end]\').val( $(\'input[name=serial_start]\').val() ); $(\'#warn_serial\').hide(); ricalcola_totale_prodotti();" value="'.$next_serial.'" />
                </div>

                <label class="col-md-1 control-label text-center" for="serial_end"> <i class="fa fa-arrow-circle-right fa-2x"></i> </label>
                <div class="col-md-2">
                    <input type="text" class="form-control input-md" name="serial_end" onkeyup="check_progressivo( $(\'input[name=serial_start]\'), $(\'input[name=serial_end]\'), $(\'#warn_serial\'), $(\'#inserisci\') );" value="'.$next_serial.'" />
                </div>';

if (!empty($max_serial)) {
    echo '
                <div class="col-md-3">
                    <p id="warn_serial" class="text-danger"><b>'.tr('Ultimo serial number inserito').': </b> '.$max_serial.'</p>
                </div>';
}
echo '
            </div>';

/*
// Altro
echo '
            <div class="row form-group">
                <label class="col-md-2 control-label" for="altro_start">'.tr('Altro codice da').':</label>
                <div class="col-md-2">
                    <input type="text" class="form-control input-md" name="altro_start" onkeyup="$(\'input[name=altro_end]\').val( $(\'input[name=altro_start]\').val() ); $(\'#warn_altro\').hide(); ricalcola_totale_prodotti();" value="'.$next_altro.'" />
                </div>

                <label class="col-md-1 control-label text-center" for="altro_end"> <i class="fa fa-arrow-circle-right fa-2x"></i> </label>
                <div class="col-md-2">
                    <input type="text" class="form-control input-md" name="altro_end" onkeyup="check_progressivo( $(\'input[name=altro_start]\'), $(\'input[name=altro_end]\'), $(\'#warn_altro\'), $(\'#inserisci\') );" value="'.$next_altro.'" />
                </div>';
if (!empty($max_altro)) {
    echo '
                <div class="col-md-3">
                    <p id="warn_altro" class="text-danger"><b>'.tr('Ultimo codice aggiuntivo inserito').': </b> '.$max_altro.'</p>
                </div>';
}
echo '
            </div>';
*/

// Totale prodotti da inserire
echo '
            <div class="row">
                <div class="col-md-12">
                    <p class="text-danger text-center">'.tr('Totale prodotti da inserire').': <span id="totale_prodotti">0</span></p>
                    <button type="submit" id="inserisci" class="btn btn-success" onclick="if( confirm(\'Confermi l\\\'inserimento di \' + globalsp.n_prodotti + \' prodotti?\') ){ $(\'#insert_form\').submit(); }"><i class="fa fa-check"></i> '.tr('Salva modifiche').'</button>';

// Visualizzo, in base alle impostazioni scelte, se il magazzino verrà movimentato
if (get_var("Movimenta il magazzino durante l'inserimento o eliminazione dei lotti/serial number") == true) {
    echo '
                    <small>'.tr("L'inserimento incrementerà la quantità dell'articolo!").'</small>';
} else {
    echo '
                    <small>'.tr("L'inserimento non movimenterà la quantità dell'articolo!").'</small>';
}

echo '
                </div>
            </div>
        </form>
    </div>
</div>';

// Ricerca
echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Ricerca prodotti').'</h3>
    </div>
    <div class="box-body">
        <div class="text-right">
            <small style="color:#f00;">';
// Visualizzo, in base alle impostazioni scelte, se il magazzino verrà movimentato
if (get_var("Movimenta il magazzino durante l'inserimento o eliminazione dei lotti/serial number")) {
    echo tr("La cancellazione decrementerà la quantità dell'articolo!");
} else {
    echo tr("L'inserimento decrementerà la quantità dell'articolo!");
}
echo '
            </small>
        </div>';

// Conteggio totale prodotti
$rs = $dbo->fetchArray('SELECT COUNT(id) AS tot FROM mg_prodotti WHERE idarticolo='.prepare($id_record));
$tot_prodotti = $rs[0]['tot'];

// Visualizzazione di tutti i prodotti
$query = 'SELECT * FROM mg_prodotti WHERE idarticolo='.prepare($id_record).' AND lotto LIKE '.prepare('%'.$search_lotto.'%').' AND serial LIKE '.prepare('%'.$search_serial.'%').' AND altro LIKE '.prepare('%'.$search_altro.'%').' ORDER BY created_at DESC, lotto DESC, serial DESC, altro DESC';

if (!empty($get['show_all3']) && $search_lotto == '' && $search_serial == '' && $search_altro == '') {
    $query .= ' LIMIT 0, 20';
}

$rs2 = $dbo->fetchArray($query);
    echo '
    <table class="table table-striped table-hover table-condensed table-bordered text-center datatables">
        <thead>
            <tr>
                <th id="th_Serial">'.tr('Serial').'</th>
                <th id="th_Data di creazione">'.tr('Data di creazione').'</th>
                <th id="th_Documento di vendita">'.tr('Documento di vendita').'</th>
                <th id="th_Totale">'.tr('Totale').'</th>
                <th></th>
            </tr>
        </thead>
        <tbody>';

    for ($i = 0; $i < count($rs2); ++$i) {
        echo '
            <tr>

                <td>'.$rs2[$i]['serial'].'</td>';

        echo '
                <td>'.Translator::timestampToLocale($rs2[$i]['created_at']).'</td>';

        // Ricerca vendite su ddt
        $query3 = 'SELECT *, ( SELECT descrizione FROM dt_tipiddt WHERE id=(SELECT idtipoddt FROM dt_ddt WHERE id=idddt) ) AS tipo_documento, ( SELECT `dir` FROM dt_tipiddt WHERE id=(SELECT idtipoddt FROM dt_ddt WHERE id=idddt) ) AS `dir`, ( SELECT numero FROM dt_ddt WHERE id=idddt ) AS numero, ( SELECT numero_esterno FROM dt_ddt WHERE id=idddt ) AS numero_esterno, ( SELECT data FROM dt_ddt WHERE id=idddt ) AS data FROM dt_righe_ddt WHERE idarticolo='.prepare($id_record).' AND lotto='.prepare($rs2[$i]['lotto']).' AND serial='.prepare($rs2[$i]['serial']).' AND altro='.prepare($rs2[$i]['altro']);
        $rs3 = $dbo->fetchArray($query3);

        // Ricerca vendite su fatture
        $query4 = 'SELECT *, ( SELECT descrizione FROM co_tipidocumento WHERE id=(SELECT idtipodocumento FROM co_documenti WHERE id=iddocumento) ) AS tipo_documento, ( SELECT `dir` FROM co_tipidocumento WHERE id=(SELECT idtipodocumento FROM co_documenti WHERE id=iddocumento) ) AS `dir`, ( SELECT numero FROM co_documenti WHERE id=iddocumento ) AS numero, ( SELECT numero_esterno FROM co_documenti WHERE id=iddocumento ) AS numero_esterno, ( SELECT data FROM co_documenti WHERE id=iddocumento ) AS data FROM co_righe_documenti WHERE idarticolo='.prepare($id_record).' AND lotto='.prepare($rs2[$i]['lotto']).' AND serial='.prepare($rs2[$i]['serial']).' AND altro='.prepare($rs2[$i]['altro']);
        $rs4 = $dbo->fetchArray($query4);

        // Ricerca inserimenti su ordini
        $query5 = 'SELECT *, ( SELECT descrizione FROM or_tipiordine WHERE id=(SELECT idtipoordine FROM or_ordini WHERE id=idordine) ) AS tipo_documento, ( SELECT `dir` FROM or_tipiordine WHERE id=(SELECT idtipoordine FROM or_ordini WHERE id=idordine) ) AS `dir`, ( SELECT numero FROM or_ordini WHERE id=idordine ) AS numero, ( SELECT numero_esterno FROM or_ordini WHERE id=idordine ) AS numero_esterno, ( SELECT data FROM or_ordini WHERE id=idordine ) AS data FROM or_righe_ordini WHERE idarticolo='.prepare($id_record).' AND lotto='.prepare($rs2[$i]['lotto']).' AND serial='.prepare($rs2[$i]['serial']).' AND altro='.prepare($rs2[$i]['altro']);
        $rs5 = $dbo->fetchArray($query5);

        // Ricerca inserimenti su interventi
        $query6 = 'SELECT mg_articoli_interventi.*, in_interventi.codice, ( SELECT orario_inizio FROM in_interventi_tecnici WHERE idintervento=mg_articoli_interventi.idintervento LIMIT 0,1 ) AS data FROM mg_articoli_interventi JOIN in_interventi ON in_interventi.id = mg_articoli_interventi.idintervento WHERE idarticolo='.prepare($id_record).' AND lotto='.prepare($rs2[$i]['lotto']).' AND serial='.prepare($rs2[$i]['serial']).' AND altro='.prepare($rs2[$i]['altro']);
        $rs6 = $dbo->fetchArray($query6);

        if (!empty($rs3) || !empty($rs4) || !empty($rs5) || !empty($rs6)) {
            // Venduto su fatture
            if (!empty($rs4)) {
                $numero = ($rs4[0]['numero_esterno'] != '') ? $rs4[0]['numero_esterno'] : $rs4[0]['numero'];
                $module_id = Modules::getModule('Fatture di vendita')['id'];
                $id = $rs4[0]['iddocumento'];
                $documento = $rs4[0]['tipo_documento'];
                $data = $rs4[0]['data'];

                $subtotale = $rs4[0]['subtotale'];
                $iva = $rs4[0]['iva'];
            }

            // Venduto su ddt
            elseif (!empty($rs3)) {
                $numero = ($rs3[0]['numero_esterno'] != '') ? $rs3[0]['numero_esterno'] : $rs3[0]['numero'];
                $module_id = Modules::getModule('Ddt di vendita')['id'];
                $id = $rs3[0]['idddt'];
                $documento = $rs3[0]['tipo_documento'];
                $data = $rs3[0]['data'];

                $subtotale = $rs3[0]['subtotale'];
                $iva = $rs3[0]['iva'];
            }

            // Inserito su ordini
            elseif (!empty($rs5)) {
                $numero = ($rs5[0]['numero_esterno'] != '') ? $rs5[0]['numero_esterno'] : $rs5[0]['numero'];
                $module_id = Modules::getModule('Ordini cliente')['id'];
                $id = $rs5[0]['idordine'];
                $documento = $rs5[0]['tipo_documento'];
                $data = $rs5[0]['data'];

                $subtotale = $rs5[0]['subtotale'];
                $iva = $rs5[0]['iva'];
            }

            // Inserito su intervento
            elseif (!empty($rs6)) {
                $numero = ($rs6[0]['numero_esterno'] != '') ? $rs6[0]['numero_esterno'] : $rs6[0]['numero'];
                $module_id = Modules::getModule('Interventi')['id'];
                $id = $rs6[0]['idintervento'];
                $documento = tr('Intervento').' '.$rs6[0]['codice'];
                $data = $rs6[0]['data'];
                $extra = str_replace('_QTA_', $rs6[0]['qta'], tr('(q.tà _QTA_)'));

                $totale = $rs6[0]['prezzo_vendita'] * $rs6[0]['qta'];
            }

            if (empty($totale) && !empty($subtotale) && !empty($iva)) {
                $totale = $subtotale + $iva;
            }

            $text = str_replace(['_DOC_', '_NUM_', '_DATE_'], [$documento, $numero, Translator::dateToLocale($data)], tr('_DOC_ n<sup>o</sup> _NUM_ del _DATE_')).(!empty($extra) ? ' '.$extra : '');

            echo '
                <td>
                    '.Modules::link($module_id, $id, $text).'
                </td>

                <td class="text-center">
                    <span>&euro; '.Translator::numberToLocale($totale).'</span>';
            if (!empty($subtotale) && !empty($iva)) {
                echo '
                    <br/>
                    <small style="color:#555;">'.Translator::numberToLocale($subtotale).' + '.Translator::numberToLocale($iva).'</small>';
            }
            echo '
                </td>

                <td></td>';
        }
        // Non venduto
        else {
            // Documento di vendita
            echo '
                <td></td>';

            // Totale
            echo '
                <td></td>';

            echo '
                <td>
                    <a class="btn btn-danger btn-sm ask" data-backto="record-edit" data-op="delprodotto" data-idprodotto="'.$rs2[$i]['id'].'">
                        <i class="fa fa-trash"></i>
                    </a>
                </td>';
        }
        echo '
                </tr>';
    }
    echo '
            </tbody>
        </table>
    </div>
</div>';

?>

<script type="text/javascript">

globalsp = { n_prodotti: 0 };

$(document).ready( function(){
	setInterval("ricalcola_totale_prodotti()", 1000);
});

/*
	Queste funzioni servono a verificare se i codici di lotti, serial number e "altro" sono progressivi
*/
function check_progressivo( start, end, warn, submit_btn ){
	digits_start 	= get_last_numeric_part( start.val().toString() );
	digits_end 		= get_last_numeric_part( end.val().toString() );

	// Nessun codice numerico trovato
	if( digits_start=="" || digits_end=="" ){
		warn.show();
		submit_btn.hide();
	}
	else{
		warn.hide();
		submit_btn.show();
	}

	ricalcola_totale_prodotti();
}

function ricalcola_totale_prodotti(){
	if( $("input[name=serial_start]").val() != undefined ){
        var lotti, altro, serial;
        /*
		lotto_start 	= get_last_numeric_part( $("input[name=lotto_start]").val().toString() );
		lotto_end 		= get_last_numeric_part( $("input[name=lotto_end]").val().toString() );
        lotti = Math.abs( parseInt(lotto_end,10) - parseInt(lotto_start,10) )+1;

		altro_start 	= get_last_numeric_part( $("input[name=altro_start]").val().toString() );
		altro_end 		= get_last_numeric_part( $("input[name=altro_end]").val().toString() );
		altro = Math.abs( parseInt(altro_end,10) - parseInt(altro_start,10) )+1;
        */

		serial_start 	= get_last_numeric_part( $("input[name=serial_start]").val().toString() );
		serial_end 		= get_last_numeric_part( $("input[name=serial_end]").val().toString() );
		serial = Math.abs( parseInt(serial_end,10) - parseInt(serial_start,10) )+1;

		// Se tutti i campi sono vuoti, il numero di prodotti è zero!
		if(isNaN(serial) /*&& isNaN(lotti) && isNaN(altro)*/ ){
			globalsp.n_prodotti = 0;
		}

		else{
			if( isNaN(lotti) )
				lotti = 1;

			if( isNaN(serial) )
				serial = 1;

			if( isNaN(altro) )
				altro = 1;

			globalsp.n_prodotti = serial /* * lotti * altro */;
		}

		$("#totale_prodotti").text( globalsp.n_prodotti );

		if( globalsp.n_prodotti==0 )
			$("#inserisci").hide();
		else
			$("#inserisci").show();
	}
}

/*
	Questa funzione restituisce la parte numerica di una stringa
*/
function get_last_numeric_part( str ){
	var matches = str.match(/(.*?)([\d]*$)/);
	return matches[2];
}
</script>
