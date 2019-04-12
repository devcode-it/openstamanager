<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="editriga">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
	<input type="hidden" name="idmastrino" value="<?php echo $record['idmastrino']; ?>">
	<input type="hidden" name="iddocumento" value="<?php echo $record['iddocumento']; ?>">


    <div class="row">
	<?php

    $rs_doc = $dbo->fetchArray("SELECT DISTINCT iddocumento, (SELECT IFNULL(numero_esterno, numero) FROM co_documenti WHERE id=co_movimenti.iddocumento) AS numero FROM co_movimenti WHERE idmastrino=".prepare($record['idmastrino']));

    if(sizeof($rs_doc)==1){

        if (!empty($record['iddocumento'])) {
            $rs = $dbo->fetchArray('SELECT dir FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento WHERE co_documenti.id='.prepare($record['iddocumento']));
            $modulo = ($rs[0]['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto'; ?>
        <div class=" col-md-2">
            <br>
            <a href="<?php echo $rootdir; ?>/editor.php?id_module=<?php echo Modules::get($modulo)['id']; ?>&id_record=<?php echo $record['iddocumento']; ?>" class="btn btn-info"><i class="fa fa-chevron-left"></i> <?php echo tr('Vai alla fattura'); ?></a>
        </div>
	<?php
        }
    }else{
    ?>
        <div class=" col-md-2">
            <br>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown" style="width:100%;">Fatture collegate
                <span class="caret"></span></button>
                <ul class="dropdown-menu">
    <?php
        for($i=0;$i<sizeof($rs_doc);$i++){
            $rs = $dbo->fetchArray('SELECT dir FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento WHERE co_documenti.id='.prepare($rs_doc[$i]['iddocumento']));
            $modulo = ($rs[0]['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto';
    ?>
                    <li><a href="<?php echo $rootdir; ?>/editor.php?id_module=<?php echo Modules::get($modulo)['id']; ?>&id_record=<?php echo $rs_doc[$i]['iddocumento']; ?>" class="dropdown-item"><?php echo tr('Vai alla fattura n. '.$rs_doc[$i]['numero']); ?></a></li>
    <?php
        }
    ?>
                </ul>
            </div>
        </div>
    <?php
    }
    ?>

		<div class="col-md-3">
			{[ "type": "date", "label": "<?php echo tr('Data movimento'); ?>", "name": "data", "required": 1, "value": "$data$" ]}
		</div>

		<div class="col-md-7">
			{[ "type": "text", "label": "<?php echo tr('Causale'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
		</div>
	</div>


	<?php
    $conti3 = [];    // contenitore conti di terzo livello
    $totale_dare = 0.00;
    $totale_avere = 0.00;
    $idmastrino = $record['idmastrino'];

    // Salvo l'elenco conti in un array (per non fare il ciclo ad ogni riga)
    $query2 = 'SELECT * FROM co_pianodeiconti2';
    $conti2 = $dbo->fetchArray($query2);
    for ($x = 0; $x < sizeof($conti2); ++$x) {
        $query3 = 'SELECT * FROM co_pianodeiconti3 WHERE idpianodeiconti2='.prepare($conti2[$x]['id']);
        $rs3 = $dbo->fetchArray($query3);
        for ($y = 0; $y < sizeof($rs3); ++$y) {
            // Creo un array con le descrizioni dei conti di livello 3 che ha come indice l'id del livello2 e del livello3
            $conti3[$rs3[$y]['idpianodeiconti2']][$y]['id'] = $rs3[$y]['id'];
            $conti3[$rs3[$y]['idpianodeiconti2']][$y]['descrizione'] = $conti2[$x]['numero'].'.'.$rs3[$y]['numero'].' '.$rs3[$y]['descrizione'];
        }
    }

    /*
        Form di modifica riga movimento
    */
    // Lettura movimenti del mastrino selezionato
    $query = 'SELECT * FROM co_movimenti WHERE idmastrino='.prepare($record['idmastrino']).' AND primanota='.prepare($record['primanota']);
    $rs = $dbo->fetchArray($query);
    $n = sizeof($rs);
    $iddocumento = $rs[0]['iddocumento'];

    echo '
    <table class="table table-striped table-condensed table-hover table-bordered"
        <tr>
            <th>'.tr('Conto').'</th>
            <th width="20%">'.tr('Dare').'</th>
            <th width="20%">'.tr('Avere').'</th>
        </tr>';
    
    if(sizeof($rs)>=10){
        $rows = sizeof($rs)+2;
    }else{
        $rows = 10;
    }

    for ($i = 0; $i < $rows; ++$i) {
        ($i <= 1) ? $required = 1 : $required = 0;

        // Conto
        echo '
			<tr>
                <td>
                    <input type="hidden" name="iddocumento['.$i.']" value="'.$rs[$i]['iddocumento'].'">
					{[ "type": "select", "name": "idconto['.$i.']", "value": "'.$rs[$i]['idconto'].'", "ajax-source": "conti", "required": "'.$required.'" ]}
				</td>';

        // Importo dare e avere
        if ($rs[$i]['totale'] > 0) {
            $value_dare = $rs[$i]['totale'];
            $value_avere = '';
        } elseif ($rs[$i]['totale'] < 0) {
            $value_dare = '';
            $value_avere = -$rs[$i]['totale'];
        } else {
            $value_dare = '';
            $value_avere = '';
        }

        // Dare
        echo '
				<td>
					{[ "type": "number", "name": "dare['.$i.']", "value": "'.$value_dare.'", "disabled": 1 ]}
				</td>';

        // Avere
        echo '
				<td>
					{[ "type": "number", "name": "avere['.$i.']", "value": "'.$value_avere.'", "disabled": 1 ]}
				</td>
			</tr>';
    }

    // Totale per controllare sbilancio
    echo '
            <tr>
                <td align="right"><b>Totale:</b></td>';

    if ($totale_dare != $totale_avere) {
        $class = 'text-danger';
        $txt = 'sbilancio di '.Translator::numberToLocale($totale_dare - $totale_avere).' &euro;';
    } else {
        $class = '';
        $txt = '';
    }

    //  Totale dare
    echo '
                <td align="right">
                    <span><span class="'.$class.'" id="totale_dare">'.Translator::numberToLocale($totale_dare).'</span> &euro;</span>
                </td>';

    //  Totale avere
    echo '
                <td align="right">
                    <span><span class="'.$class.'" id="totale_avere">'.Translator::numberToLocale($totale_avere).'</span> &euro;</span>
                </td>
            </tr>';

    //  Verifica sbilancio
    echo '
            <tr>
                <td align="right"></td>
                <td colspan="2" align="center">
                    <span id="testo_aggiuntivo">'.$txt.'</span>
                </td>
            </tr>
        </table>';
    ?>


	<script type="text/javascript">
		$(document).ready( function(){
			$('input[id*=dare], input[id*=avere]').each(function(){
				if($(this).val() != "<?php echo Translator::numberToLocale(0); ?>") $(this).prop("disabled", false);
			});

			$('select').on('change', function(){
                if($(this).parent().parent().find('input[disabled]').length != 1){
                    if($(this).val()) {
                        $(this).parent().parent().find('input').prop("disabled", false);
                    }
                    else{
                        $(this).parent().parent().find('input').prop("disabled", true);
                        $(this).parent().parent().find('input').val("");
                    }
                }
            });

			$('input[id*=dare]').on('keyup change', function(){
                if(!$(this).prop('disabled')){
                    if($(this).val()) {
                        $(this).parent().parent().find('input[id*=avere]').prop("disabled", true);
                    }
                    else {
                        $(this).parent().parent().find('input[id*=avere]').prop("disabled", false);
                    }

                    calcolaBilancio();
                }
			});

			$('input[id*=avere]').on('keyup change', function(){
                if(!$(this).prop('disabled')){
                    if($(this).val()) {
                        $(this).parent().parent().find('input[id*=dare]').prop("disabled", true);
                    }
                    else {
                        $(this).parent().parent().find('input[id*=dare]').prop("disabled", false);
                    }

                    calcolaBilancio();
                }
			});

			// Ad ogni modifica dell'importo verifica che siano stati selezionati: il conto, la causale, la data. Inoltre aggiorna lo sbilancio
			function calcolaBilancio(){
				bilancio = 0.00;
				totale_dare = 0.00;
				totale_avere = 0.00;

				// Calcolo il totale dare e totale avere
				$('input[id*=dare]').each( function(){
					if( $(this).val() == '' ) valore = 0;
					else valore = $(this).val().toEnglish();
					totale_dare += Math.round(valore*100)/100;
				});

				$('input[id*=avere]').each( function(){
					if( $(this).val() == '' ) valore = 0;
					else valore = $(this).val().toEnglish();
					totale_avere += Math.round(valore*100)/100;
				});

				$('#totale_dare').text(totale_dare.toLocale());
				$('#totale_avere').text(totale_avere.toLocale());

				bilancio = Math.round(totale_dare*100)/100 - Math.round(totale_avere*100)/100;

				if( bilancio == 0 ){
					$("#testo_aggiuntivo").removeClass('text-danger').html("");
					//$("button[type=submit]").removeClass('hide');
                    $("#save").removeClass('hide');

				}
				else{
					$("#testo_aggiuntivo").addClass('text-danger').html("sbilancio di " + bilancio.toLocale() + " &euro;" );

					//$("button[type=submit]").addClass('hide');
                    $("#save").addClass('hide');
				}
			}

			// Trigger dell'evento keyup() per la prima volta, per eseguire i dovuti controlli nel caso siano predisposte delle righe in prima nota
			$("input[id*=dare][value!=''], input[id*=avere][value!='']").keyup();

			$("select[id*=idconto]").click( function(){
				$("input[id*=dare][value!=''], input[id*=avere][value!='']").keyup();
			});
		});
	</script>
</form>

<a class="btn btn-danger ask" data-backto="record-list" data-idmastrino="<?php echo $record['idmastrino']; ?>">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
