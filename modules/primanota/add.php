<?php

include_once __DIR__.'/../../core.php';

use Modules\Fatture\Fattura;

$module = Modules::get('Prima nota');

$variables = Modules::get('Fatture di vendita')->getPlaceholders($id_documento);
$righe = [];

$singola_scadenza = get('single') != null;

// Registrazione da remoto
$id_records = get('id_records');
if (!empty($id_records)) {
    $id_records = str_replace(';', ',', $id_records);
    if (get('origine') == 'fatture') {
        $id_documenti = $id_records;
    } else {
        $id_scadenze = $id_records;
    }
}

// Fatture
$id_documenti = $id_documenti ?: get('id_documenti');
$id_documenti = $id_documenti ? explode(',', $id_documenti) : [];
$numeri = [];
foreach ($id_documenti as $id_documento) {
    $fattura = Fattura::find($id_documento);
    $tipo = $fattura->tipo;
    $dir = $fattura->direzione;

    $numeri[] = !empty($fattura['numero_esterno']) ? $fattura['numero_esterno'] : $fattura['numero'];

    $nota_credito = $tipo->descrizione == 'Nota di credito';
    $is_insoluto = (!empty($fattura['riba']) && in_array($tipo->descrizione, ['Emessa', 'Parzialmente pagato', 'Pagato']) && $dir == 'entrata');

    // Predisposizione prima riga
    $conto_field = 'idconto_'.($dir == 'entrata' ? 'vendite' : 'acquisti');
    $id_conto_aziendale = $fattura->pagamento[$conto_field] ?: setting('Conto aziendale predefinito');

    // Predisposizione conto crediti clienti
    $conto_field = 'idconto_'.($dir == 'entrata' ? 'cliente' : 'fornitore');
    $id_conto_controparte = $fattura->anagrafica[$conto_field];
    //$_SESSION['superselect']['idconto_controparte'] = $id_conto_controparte;

    // Lettura delle scadenza della fattura
    $scadenze = $dbo->fetchArray('SELECT id, ABS(da_pagare - pagato) AS rata FROM co_scadenziario WHERE iddocumento='.prepare($id_documento).' AND ABS(da_pagare) > ABS(pagato) ORDER BY YEAR(scadenza) ASC, MONTH(scadenza) ASC');

    // Selezione prima scadenza
    if ($singola_scadenza && !empty($scadenze)) {
        $scadenze = [$scadenze[0]];
    }

    $righe_documento = [];

    // Riga aziendale
    $totale = sum(array_column($scadenze, 'rata'));
    $ids = implode(',', array_column($scadenze, 'id'));
    if ($totale != 0) {
        $righe_documento[] = [
            'id_scadenza' => $ids,
            'insoluto' => $is_insoluto,
            'conto' => $id_conto_aziendale,
            'dare' => ($dir == 'entrata') ? 0 : $totale,
            'avere' => ($dir == 'entrata') ? $totale : 0,
        ];
    }

    // Riga controparte
    foreach ($scadenze as $scadenza) {
        $righe_documento[] = [
            'id_scadenza' => $scadenza['id'],
            'insoluto' => $is_insoluto,
            'conto' => $id_conto_controparte,
            'dare' => ($dir == 'entrata') ? $scadenza['rata'] : 0,
            'avere' => ($dir == 'entrata') ? 0 : $scadenza['rata'],
        ];
    }

    // Se è una nota di credito, inverto i valori
    if ($nota_credito || $is_insoluto) {
        foreach ($righe_documento as $key => $value) {
            $tmp = $value['avere'];
            $righe_documento[$key]['avere'] = $righe_documento[$key]['dare'];
            $righe_documento[$key]['dare'] = $tmp;
        }
    }

    $righe = array_merge($righe, $righe_documento);
}

$dir = get('dir');

// Scadenze
$id_scadenze = $id_scadenze ?: get('id_scadenze');
$id_scadenze = $id_scadenze ? explode(',', $id_scadenze) : [];
foreach ($id_scadenze as $id_scadenza) {
    $scadenza = $dbo->fetchOne('SELECT descrizione, scadenza, SUM(da_pagare - pagato) AS rata FROM co_scadenziario WHERE id='.prepare($id_scadenza));

    $descrizione_conto = ($dir == 'entrata') ? 'Riepilogativo clienti' : 'Riepilogativo fornitori';
    $conto = $dbo->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE descrizione = '.prepare($descrizione_conto));
    $id_conto_controparte = $conto['id'];

    $righe_documento = [];

    $righe_documento[] = [
        'id_scadenza' => $scadenza['id'],
        'conto' => $id_conto_controparte,
        'dare' => ($dir == 'entrata') ? $scadenza['rata'] : 0,
        'avere' => ($dir == 'entrata') ? 0 : $scadenza['rata'],
    ];

    $righe = array_merge($righe, $righe_documento);
}

// Descrizione
$numero_scadenze = count($id_scadenze);
$numero_documenti = count($id_documenti);
if ($numero_documenti + $numero_scadenze > 1) {
    $descrizione = 'Pag. fatture num. '.implode(', ', $numeri);
} elseif ($numero_documenti == 1) {
    $numero_fattura = !empty($fattura['numero_esterno']) ? $fattura['numero_esterno'] : $fattura['numero'];

    $tipo_fattura = $fattura->isNota() ? $tipo->descrizione : tr('Fattura');

    if (!empty($is_insoluto)) {
        $operation = tr('Registrazione insoluto');
    } else {
        $operation = tr('Pag.');
    }

    $descrizione = tr('_OP_ _DOC_ num. _NUM_ del _DATE_ (_NAME_)', [
        '_OP_' => $operation,
        '_DOC_' => strtolower($tipo_fattura),
        '_NUM_' => $numero_fattura,
        '_DATE_' => Translator::dateToLocale($fattura['data']),
        '_NAME_' => $fattura->anagrafica['ragione_sociale'],
    ]);
} elseif ($numero_scadenze == 1) {
    $descrizione = tr('Pag. _OP_ del _DATE_', [
        '_OP_' => $scadenza['descrizione'],
        '_DATE_' => Translator::dateToLocale($scadenza['scadenza']),
    ]);
}

echo '
<form action="'.ROOTDIR.'/controller.php?id_module='.$module->id.'" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="crea_modello" id="crea_modello" value="0">
	<input type="hidden" name="idmastrino" id="idmastrino" value="0">';

    echo '
	<div class="row">
		<div class="col-md-12">
			{[ "type": "select", "label": "'.tr('Modello primanota').'", "id": "modello_primanota", "values": "query=SELECT idmastrino AS id, nome AS descrizione, descrizione as causale FROM co_movimenti_modelli GROUP BY idmastrino" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data movimento').'", "name": "data", "required": 1, "value": "-now-" ]}
		</div>

		<div class="col-md-8">
			{[ "type": "text", "label": "'.tr('Causale').'", "name": "descrizione", "id": "desc", "required": 1, "value": '.json_encode($descrizione).' ]}
		</div>
	</div>';

    echo '
    <table class="table table-striped table-condensed table-hover table-bordered"
        <tr>
            <th>'.tr('Conto').'</th>
            <th width="20%">'.tr('Dare').'</th>
            <th width="20%">'.tr('Avere').'</th>
        </tr>';

    $max = max(count($righe), 10);
    for ($i = 0; $i < $max; ++$i) {
        $required = ($i <= 1);
        $riga = $righe[$i];

        // Conto
        echo '
			<tr>
                <input type="hidden" name="id_scadenza[]" value="'.$riga['id_scadenza'].'">
                <input type="hidden" name="insoluto[]" value="'.$riga['insoluto'].'">
                
				<td>
					{[ "type": "select", "name": "idconto[]", "id": "conto'.$i.'", "value": "'.($riga['conto'] ?: '').'", "ajax-source": "conti", "required": "'.$required.'" ]}
				</td>';

        // Dare
        echo '
				<td>
					{[ "type": "number", "name": "dare[]", "id": "dare'.$i.'", "value": "'.($riga['dare'] ?: 0).'" ]}
				</td>';

        // Avere
        echo '
				<td>
					{[ "type": "number", "name": "avere[]", "id": "avere'.$i.'", "value": "'.($riga['avere'] ?: 0).'" ]}
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
                    <span><span id="totale_dare"></span> '.currency().'</span>
                </td>';

    // Totale avere
    echo '
                <td align="right">
                    <span><span id="totale_avere"></span> '.currency().'</span>
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

    echo '
	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="button" class="btn btn-default" id="btn_crea_modello">
			    <i class="fa fa-plus"></i> '.tr('Aggiungi e crea modello').'
            </button>
			<button type="submit" class="btn btn-primary" id="add-submit">
			    <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
		</div>
	</div>
</form>';
?>

<script type="text/javascript">
    var variables = <?php echo json_encode($variables); ?>;
    var formatted_zero = "<?php echo Translator::numberToLocale(0); ?>";
    var nuovo_modello = "<?php echo tr('Aggiungi e crea modello'); ?>";
    var modifica_modello = "<?php echo tr('Aggiungi e modifica modello'); ?>";
    var sbilancio = "<?php echo tr('sbilancio di _NUM_', [
        '_NUM_' => '|value| '.currency(),
    ]); ?>";

    $("#bs-popup #add-form").submit(function() {
        return calcolaBilancio();
    });
    
    // Ad ogni modifica dell'importo verifica che siano stati selezionati: il conto, la causale, la data. Inoltre aggiorna lo sbilancio
    function calcolaBilancio() {
        bilancio = 0.00;
        totale_dare = 0.00;
        totale_avere = 0.00;

        // Calcolo il totale dare e totale avere
        $('#bs-popup input[id*=dare]').each(function() {
            valore = $(this).val() ? $(this).val().toEnglish() : 0;

            totale_dare += Math.round(valore * 100) / 100;
        });

        $('#bs-popup input[id*=avere]').each(function() {
            valore = $(this).val() ? $(this).val().toEnglish() : 0;

            totale_avere += Math.round(valore * 100) / 100;
        });

        $('#bs-popup #totale_dare').text(totale_dare.toLocale());
        $('#bs-popup #totale_avere').text(totale_avere.toLocale());

        bilancio = Math.round(totale_dare * 100) / 100 - Math.round(totale_avere * 100) / 100;

        if (bilancio == 0) {
            $('#bs-popup #testo_aggiuntivo').removeClass('text-danger').html("");
            $('#bs-popup #add-submit').removeClass('hide');
            $('#bs-popup #btn_crea_modello').removeClass('hide');
        } else {
            $('#bs-popup #testo_aggiuntivo').addClass('text-danger').html(sbilancio.replace('|value|', bilancio.toLocale()));
            $('#bs-popup #add-submit').addClass('hide');
            $('#bs-popup #btn_crea_modello').addClass('hide');
        }

        return bilancio == 0;
    }

    function bloccaZeri(){
        $('#bs-popup input[id*=dare], #bs-popup input[id*=avere]').each(function() {
            if ($(this).val() == formatted_zero) {
                $(this).prop("disabled", true);
            } else {
                $(this).prop("disabled", false);
            }
        });
    }

    $(document).ready(function() {
        calcolaBilancio();
        bloccaZeri();

        $("#bs-popup #add-form").submit(function() {
            var result = calcolaBilancio();

            if(!result) bloccaZeri();

            return result;
        });

        $('select').on('change', function() {
            if ($(this).parent().parent().find('input[disabled]').length != 1) {
                if ($(this).val()) {
                    $(this).parent().parent().find('input').prop("disabled", false);
                } else {
                    $(this).parent().parent().find('input').prop("disabled", true);
                    $(this).parent().parent().find('input').val("0.00");
                }
            }
        });

        $('#bs-popup input[id*=dare]').on('keyup change', function() {
            if (!$(this).prop('disabled')) {
                if ($(this).val()) {
                    $(this).parent().parent().find('input[id*=avere]').prop("disabled", true);
                } else {
                    $(this).parent().parent().find('input[id*=avere]').prop("disabled", false);
                }

                calcolaBilancio();
            }
        });

        $('#bs-popup input[id*=avere]').on('keyup change', function() {
            if (!$(this).prop('disabled')) {
                if ($(this).val()) {
                    $(this).parent().parent().find('input[id*=dare]').prop("disabled", true);
                } else {
                    $(this).parent().parent().find('input[id*=dare]').prop("disabled", false);
                }

                calcolaBilancio();
            }
        });

        // Trigger dell'evento keyup() per la prima volta, per eseguire i dovuti controlli nel caso siano predisposte delle righe in prima nota
        $("#bs-popup input[id*=dare][value!=''], #bs-popup input[id*=avere][value!='']").keyup();

        $("#bs-popup select[id*=idconto]").click(function() {
            $("#bs-popup input[id*=dare][value!=''], #bs-popup input[id*=avere][value!='']").keyup();
        });

        $('#bs-popup #modello_primanota').change(function() {
            if ($(this).val() != '') {
                $('#btn_crea_modello').html('<i class="fa fa-edit"></i> ' + modifica_modello);
                $('#bs-popup #idmastrino').val($(this).val());
            } else {
                $('#btn_crea_modello').html('<i class="fa fa-plus"></i> ' + nuovo_modello);
                $('#bs-popup #idmastrino').val(0);
            }

            var idmastrino = $(this).val();
            var replaced = 0;

            if (idmastrino != '') {
                var causale = $(this).find('option:selected').data('causale');

                if ($('#iddocumento').val() != '') {
                    for (i in variables) {
                        if (causale.includes('{' + i + '}')) {
                            replaced++;
                            causale = causale.replace('{' + i + '}', variables[i]);
                        }
                    }
                } else {
                    for (i in variables) {
                        causale = causale.replace('{' + i + '}', '_');
                    }
                }

                // aggiornava erroneamente anche la causale ed eventuale numero di fattura e data
                if (replaced > 0 || $('#iddocumento').val() == '') {
                    $('#bs-popup #desc').val(causale);
                }

                $.get(globals.rootdir + '/ajax_complete.php?op=get_conti&idmastrino=' + idmastrino, function(data) {
                    var conti = data.split(',');
                    for (i = 0; i < conti.length; i++) {
                        var conto = conti[i].split(';');
                        // Sostituzione conto cliente/fornitore
                        if (conto[0] == -1) {
                            if ($('#iddocumento').val() != '') {
                                var option = $("<option selected></option>").val(variables['conto']).text(variables['conto_descrizione']);
                                $('#bs-popup #conto' + i).selectReset();
                                $('#bs-popup #conto' + i).append(option).trigger('change');
                            }
                        } else {
                            var option = $("<option selected></option>").val(conto[0]).text(conto[1]);
                            $('#bs-popup #conto' + i).selectReset();
                            $('#bs-popup #conto' + i).append(option).trigger('change');
                        }
                    }
                    for (i = 9; i >= conti.length; i--) {
                        $('#bs-popup #conto' + i).selectReset();
                        console.log('#bs-popup #conto' + i);
                    }
                });
            }
        });

        $('#bs-popup #btn_crea_modello').click(function() {
            $('#bs-popup #crea_modello').val("1");
            $('#bs-popup #add-form').submit();
        });
    });
</script>
