<?php

include_once __DIR__.'/../../core.php';

?><form action="editor.php?id_module=$id_module$" method="post">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="iddocumento" value="<?php echo get('iddocumento'); ?>">

	<?php
    $idconto = get('idconto');
    $iddocumento = get('iddocumento');
    $dir = get('dir');

    if (!empty($iddocumento)) {
        // Lettura numero e tipo di documento
        $query = 'SELECT dir, numero, numero_esterno, data, co_tipidocumento.descrizione AS tdescrizione, idanagrafica AS parent_idanagrafica, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=parent_idanagrafica AND deleted=0) AS ragione_sociale FROM co_documenti LEFT OUTER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id='.prepare($iddocumento);
        $rs = $dbo->fetchArray($query);
        $dir = $rs[0]['dir'];
        $numero_doc = !empty($rs[0]['numero_esterno']) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];
        $tipo_doc = $rs[0]['tdescrizione'];

        $descrizione = tr('_DOC_ numero _NUM_ del _DATE_ (_NAME_)', [
            '_DOC_' => $tipo_doc,
            '_NUM_' => $numero_doc,
            '_DATE_' => Translator::dateToLocale($rs[0]['data']),
            '_NAME_' => $rs[0]['ragione_sociale'],
        ]);

        /*
            Predisposizione prima riga
        */
        $field = 'idconto_'.($dir == 'entrata' ? 'vendite' : 'acquisti');
        $idconto_aziendale = $dbo->fetchArray('SELECT '.$field.' FROM co_pagamenti WHERE id = (SELECT idpagamento FROM co_documenti WHERE id='.prepare($iddocumento).') GROUP BY descrizione')[0][$field];

        // Lettura conto cassa di default
        $idconto_aziendale = !empty($idconto_aziendale) ? $idconto_aziendale : get_var('Conto aziendale predefinito');

        // Generazione causale (incasso fattura)
        $descrizione_conto_aziendale = $descrizione;

        /*
            Calcolo totale per chiudere la fattura
        */
        // Lettura importo da scadenzario (seleziono l'importo di questo mese)
        $query = 'SELECT *, scadenza, ABS(da_pagare-pagato) AS rata FROM co_scadenziario WHERE iddocumento='.prepare($iddocumento)." AND ABS(da_pagare) > ABS(pagato) ORDER BY DATE_FORMAT(scadenza,'%m/%Y') ASC";
        $rs = $dbo->fetchArray($query);
        $importo_conto_aziendale = $rs[0]['rata'];

        if ($dir == 'entrata') {
            $totale_dare = abs($importo_conto_aziendale);
        } else {
            $totale_dare = abs($importo_conto_aziendale);
        }

        // Può essere che voglia inserire un movimento in un mese diverso da quello previsto per l'incasso, perciò devo
        // leggere solo il totale rimanente della fattura rispetto a quello pagato invece di leggere quello da pagare
        // per il mese corrente (viene calcolato sopra)
        if ($totale_dare == 0) {
            // Lettura totale finora pagato
            $query = 'SELECT SUM(pagato) AS tot_pagato, SUM(da_pagare) AS tot_da_pagare FROM co_scadenziario GROUP BY iddocumento HAVING iddocumento='.prepare($iddocumento);
            $rs = $dbo->fetchArray($query);

            $importo_conto_aziendale = abs($rs[0]['tot_da_pagare']) - abs($rs[0]['tot_pagato']);
            $totale_dare = $importo_conto_aziendale;
        }

        /*
            Predisposizione seconda riga
        */
        // conto crediti clienti
        if ($dir == 'entrata') {
            // Se è la prima nota di una fattura leggo il conto del cliente
            if ($iddocumento != '') {
                $query = 'SELECT idconto_cliente FROM an_anagrafiche INNER JOIN co_documenti ON an_anagrafiche.idanagrafica=co_documenti.idanagrafica WHERE co_documenti.id='.prepare($iddocumento);
                $rs = $dbo->fetchArray($query);
                $idconto_controparte = $rs[0]['idconto_cliente'];
            } else {
                $query = "SELECT id FROM co_pianodeiconti3 WHERE descrizione='Riepilogativo clienti'";
                $rs = $dbo->fetchArray($query);
                $idconto_controparte = $rs[0]['id'];
            }
        }

        // conto debiti fornitori
        else {
            // Se è la prima nota di una fattura leggo il conto del fornitore
            if ($iddocumento != '') {
                $query = 'SELECT idconto_fornitore FROM an_anagrafiche INNER JOIN co_documenti ON an_anagrafiche.idanagrafica=co_documenti.idanagrafica WHERE co_documenti.id='.prepare($iddocumento);
                $rs = $dbo->fetchArray($query);
                $idconto_controparte = $rs[0]['idconto_fornitore'];
            } else {
                $query = "SELECT id FROM co_pianodeiconti3 WHERE descrizione='Riepilogativo fornitori'";
                $rs = $dbo->fetchArray($query);
                $idconto_controparte = $rs[0]['id'];
            }
        }
        $_SESSION['superselect']['idconto_controparte'] = $idconto_controparte;

        // Lettura causale movimento (documento e ragione sociale)
        $descrizione_conto_controparte = $descrizione;
        $importo_conto_controparte = $importo_conto_aziendale;

        if ($dir == 'entrata') {
            $totale_avere = $importo_conto_controparte;
        } else {
            $totale_avere = $importo_conto_controparte;
        }
    }
    ?>

	<div class="row">
		<div class="col-md-4">
			{[ "type": "date", "label": "<?php echo tr('Data movimento'); ?>", "name": "data", "required": 1, "value": "-now-" ]}
		</div>

		<div class="col-md-8">
			{[ "type": "text", "label": "<?php echo tr('Causale'); ?>", "name": "descrizione", "required": 1, "value": "<?php echo $descrizione; ?>" ]}
		</div>
	</div>


	<?php
    $totale_dare = 0.00;
    $totale_avere = 0.00;
    $idmastrino = $records[0]['idmastrino'];

    // Salvo l'elenco conti in un array (per non fare il ciclo ad ogni riga)

    /*
        Form di aggiunta riga movimento
    */
    echo '
    <table class="table table-striped table-condensed table-hover table-bordered"
        <tr>
            <th>'.tr('Conto').'</th>
            <th width="20%">'.tr('Dare').'</th>
            <th width="20%">'.tr('Avere').'</th>
        </tr>';

    for ($i = 0; $i < 10; ++$i) {
        // Conto
        echo '
			<tr>
				<td>
					{[ "type": "select", "name": "idconto['.$i.']", "value": "';
        if ($i == 0) {
            echo $idconto_controparte;
        } elseif ($i == 1) {
            echo $idconto_aziendale;
        }
        echo '", "ajax-source": "conti" ]}
				</td>';

        // Importo dare e avere
        if ($i == 0) {
            if ($dir == 'entrata') {
                $value_dare = '';
                $value_avere = $importo_conto_aziendale;
            } else {
                $value_dare = $importo_conto_aziendale;
                $value_avere = '';
            }
        } elseif ($i == 1) {
            if ($dir == 'entrata') {
                $value_dare = $importo_conto_controparte;
                $value_avere = '';
            } else {
                $value_dare = '';
                $value_avere = $importo_conto_controparte;
            }
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
                <td align="right"><b>'.tr('Totale').':</b></td>';

    // Totale dare
    echo '
                <td align="right">
                    <span><span id="totale_dare"></span> &euro;</span>
                </td>';

    // Totale avere
    echo '
                <td align="right">
                    <span><span id="totale_avere"></span> &euro;</span>
                </td>
            </tr>';

    // Verifica sbilancio
    echo '
            <tr>
                <td align="right"></td>
                <td colspan="2" align="center">
                    <span id="testo_aggiuntivo"></span>
                </td>
            </tr>
        </table>';
    ?>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>

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
					totale_dare += valore;
				});

				$('input[id*=avere]').each( function(){
					if( $(this).val() == '' ) valore = 0;
                    else valore = $(this).val().toEnglish();
					totale_avere += valore;
				});

				$('#totale_dare').text(totale_dare.toLocale());
				$('#totale_avere').text(totale_avere.toLocale());

				bilancio = totale_dare - totale_avere;

				if(bilancio == 0){
					$("#testo_aggiuntivo").removeClass('text-danger').html("");
					$("button[type=submit]").removeClass('hide');
				}
				else{
					$("#testo_aggiuntivo").addClass('text-danger').html("sbilancio di " + bilancio.toLocale() + " &euro;" );
					$("button[type=submit]").addClass('hide');
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

