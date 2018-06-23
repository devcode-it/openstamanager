<?php

include_once __DIR__.'/../../../core.php';

// Gestione dei lotti degli articoli

echo '
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">'.tr('Produzione').'</h3>
    </div>
    <div class="panel-body">';

$search_lotto = get('search_lotto');
$search_serial = get('search_serial');
$search_altro = get('search_altro');

// Calcolo prossimo lotto e serial number
$rs = $dbo->fetchArray('SELECT MAX(lotto) AS max_lotto, MAX(serial) AS max_serial, MAX(altro) AS max_altro FROM mg_prodotti WHERE id_articolo='.prepare($id_record));
//$max_lotto = $rs[0]['max_lotto'];
$max_serial = $rs[0]['max_serial'];
//$max_altro = $rs[0]['max_altro'];

//$next_lotto = get_next_code($max_lotto);
$next_serial = get_next_code($max_serial);
//$next_altro = get_next_code($max_altro);

echo '
        <form action="" method="post" role="form">
            <input type="hidden" name="backto" value="record-edit">
            <input type="hidden" name="op" value="addprodotto">';

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
$rs = $dbo->fetchArray('SELECT COUNT(id) AS tot FROM mg_prodotti WHERE id_articolo='.prepare($id_record));
$tot_prodotti = $rs[0]['tot'];

// Visualizzazione di tutti i prodotti
$query = 'SELECT id, serial, created_at FROM mg_prodotti WHERE serial IS NOT NULL AND id_articolo='.prepare($id_record).(!empty($search_serial) ? ' AND serial LIKE '.prepare('%'.$search_serial.'%') : '').' GROUP BY serial ORDER BY created_at DESC, serial DESC, lotto DESC, altro DESC';
$rs2 = $dbo->fetchArray($query);

echo '
    <table class="table table-striped table-hover table-condensed table-bordered text-center datatables">
        <thead>
            <tr>
                <th id="th_Serial">'.tr('Serial').'</th>
                <th id="th_Data di creazione">'.tr('Data di creazione').'</th>
                <th id="th_Documento di vendita">'.tr('Documento di vendita').'</th>
                <th id="th_Totale">'.tr('Totale').'</th>
                <th class="text-center">#</th>
            </tr>
        </thead>
        <tbody>';

for ($i = 0; $i < count($rs2); ++$i) {
    echo '
        <tr>

            <td>'.$rs2[$i]['serial'].'</td>';

    echo '
            <td>'.Translator::timestampToLocale($rs2[$i]['created_at']).'</td>';

    // Ricerca vendite
    $vendite = $dbo->fetchArray('SELECT * FROM mg_prodotti WHERE dir=\'entrata\' AND id_articolo='.prepare($id_record).' AND serial='.prepare($rs2[$i]['serial']));

    if (!empty($vendite)) {
        echo '
            <td>';

        $totali = [];

        foreach ($vendite as $vendita) {
            // Venduto su fatture
            if (!empty($vendita['id_riga_documento'])) {
                $module_id = Modules::get('Fatture di vendita')['id'];

                // Ricerca vendite su fatture
                $query = 'SELECT *, ( SELECT descrizione FROM co_tipidocumento WHERE id=(SELECT idtipodocumento FROM co_documenti WHERE id=iddocumento) ) AS tipo_documento, ( SELECT `dir` FROM co_tipidocumento WHERE id=(SELECT idtipodocumento FROM co_documenti WHERE id=iddocumento) ) AS `dir`, ( SELECT numero FROM co_documenti WHERE id=iddocumento ) AS numero, ( SELECT numero_esterno FROM co_documenti WHERE id=iddocumento ) AS numero_esterno, ( SELECT data FROM co_documenti WHERE id=iddocumento ) AS data FROM co_righe_documenti WHERE co_righe_documenti.id='.prepare($vendita['id_riga_documento']);
                $data = $dbo->fetchArray($query);

                $id = $data[0]['iddocumento'];
            }

            // Venduto su ddt
            elseif (!empty($vendita['id_riga_ddt'])) {
                $numero = ($rs3[0]['numero_esterno'] != '') ? $rs3[0]['numero_esterno'] : $rs3[0]['numero'];
                $module_id = Modules::get('Ddt di vendita')['id'];

                $query = 'SELECT *, ( SELECT descrizione FROM dt_tipiddt WHERE id=(SELECT idtipoddt FROM dt_ddt WHERE id=idddt) ) AS tipo_documento, ( SELECT `dir` FROM dt_tipiddt WHERE id=(SELECT idtipoddt FROM dt_ddt WHERE id=idddt) ) AS `dir`, ( SELECT numero FROM dt_ddt WHERE id=idddt ) AS numero, ( SELECT numero_esterno FROM dt_ddt WHERE id=idddt ) AS numero_esterno, ( SELECT data FROM dt_ddt WHERE id=idddt ) AS data FROM dt_righe_ddt WHERE dt_righe_ddt.id='.prepare($vendita['id_riga_ddt']);
                $data = $dbo->fetchArray($query);

                $id = $data[0]['idddt'];
            }

            // Inserito su ordini
            elseif (!empty($vendita['id_riga_ordine'])) {
                $module_id = Modules::get('Ordini cliente')['id'];

                // Ricerca inserimenti su ordini
                $query = 'SELECT *, ( SELECT descrizione FROM or_tipiordine WHERE id=(SELECT idtipoordine FROM or_ordini WHERE id=idordine) ) AS tipo_documento, ( SELECT `dir` FROM or_tipiordine WHERE id=(SELECT idtipoordine FROM or_ordini WHERE id=idordine) ) AS `dir`, ( SELECT numero FROM or_ordini WHERE id=idordine ) AS numero, ( SELECT numero_esterno FROM or_ordini WHERE id=idordine ) AS numero_esterno, ( SELECT data FROM or_ordini WHERE id=idordine ) AS data FROM or_righe_ordini WHERE  or_righe_ordini.id='.prepare($vendita['id_riga_ordine']);
                $data = $dbo->fetchArray($query);

                $id = $data[0]['idordine'];
            }

            // Inserito su intervento
            elseif (!empty($vendita['id_riga_intervento'])) {
                $module_id = Modules::get('Interventi')['id'];

                // Ricerca inserimenti su interventi
                $query = 'SELECT mg_articoli_interventi.*, in_interventi.codice, ( SELECT orario_inizio FROM in_interventi_tecnici WHERE idintervento=mg_articoli_interventi.idintervento LIMIT 0,1 ) AS data FROM mg_articoli_interventi JOIN in_interventi ON in_interventi.id = mg_articoli_interventi.idintervento WHERE mg_articoli_interventi.id='.prepare($vendita['id_riga_intervento']);
                $data = $dbo->fetchArray($query);

                $id = $data[0]['idintervento'];

                $data[0]['tipo_documento'] = tr('Intervento').' '.$data[0]['codice'];
                $data[0]['subtotale'] = $data[0]['prezzo_vendita'] * $data[0]['qta'];
                $data[0]['iva'] = 0;

                $extra = tr('(q.tà _QTA_)', [
                    '_QTA_' => $data[0]['qta'],
                ]);
            }

            $totali[] = [$data[0]['subtotale'], $data[0]['iva']];

            $numero = !empty($data[0]['numero_esterno']) ? $data[0]['numero_esterno'] : $data[0]['numero'];

            $text = tr('_DOC_ num. _NUM_ del _DATE_', [
                '_DOC_' => $data[0]['tipo_documento'],
                '_NUM_' => $numero,
                '_DATE_' => Translator::dateToLocale($data[0]['data']),
            ]).(!empty($extra) ? ' '.$extra : '');

            echo '
            '.Modules::link($module_id, $id, $text).'<br>';
        }

        echo '
            </td>

            <td class="text-center">';
        foreach ($totali as $value) {
            $subtotale = $value[0];
            $iva = $value[1];

            echo '
                <span>&euro; '.Translator::numberToLocale($subtotale + $iva).'</span>';
            if (!empty($subtotale) && !empty($iva)) {
                echo '
                <small style="color:#555;">('.Translator::numberToLocale($subtotale).' + '.Translator::numberToLocale($iva).')</small>';
            }
            echo '
                <br>';
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
            <td class="text-center">
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
