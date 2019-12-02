<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Fattura;

$module = Modules::get('Prima nota');

$variables = Modules::get('Fatture di vendita')->getPlaceholders($id_documento);
$righe = [];

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

// ID predefiniti
$dir = 'uscita'; // Le scadenze normali hanno solo direzione in uscita
$singola_scadenza = get('single') != null;
$is_insoluto = get('is_insoluto') != null;

$id_documenti = $id_documenti ?: get('id_documenti');
$id_documenti = $id_documenti ? explode(',', $id_documenti) : [];

$id_scadenze = $id_scadenze ?: get('id_scadenze');
$id_scadenze = $id_scadenze ? explode(',', $id_scadenze) : [];

// Scadenze
foreach ($id_scadenze as $id_scadenza) {
    $scadenza = $dbo->fetchOne('SELECT *, SUM(da_pagare - pagato) AS rata FROM co_scadenziario WHERE id='.prepare($id_scadenza));
    if (!empty($scadenza['iddocumento'])) {
        $id_documenti[] = $scadenza['iddocumento'];
        continue;
    }

    $scadenza['rata'] = abs($scadenza['rata']);

    $descrizione_conto = ($dir == 'entrata') ? 'Riepilogativo clienti' : 'Riepilogativo fornitori';
    $conto = $dbo->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE descrizione = '.prepare($descrizione_conto));
    $id_conto_controparte = $conto['id'];

    $righe_documento = [];
    $righe_documento[] = [
        'iddocumento' => null,
        'id_scadenza' => $scadenza['id'],
        'id_conto' => null,
        'dare' => ($dir == 'entrata') ? 0 : $scadenza['rata'],
        'avere' => ($dir == 'entrata') ? $scadenza['rata'] : 0,
    ];

    $righe_documento[] = [
        'iddocumento' => null,
        'id_scadenza' => $scadenza['id'],
        'id_conto' => $id_conto_controparte,
        'dare' => ($dir == 'entrata') ? $scadenza['rata'] : 0,
        'avere' => ($dir == 'entrata') ? 0 : $scadenza['rata'],
    ];

    // Se è un insoluto, inverto i valori
    if ($is_insoluto) {
        foreach ($righe_documento as $key => $value) {
            $tmp = $value['avere'];
            $righe_documento[$key]['avere'] = $righe_documento[$key]['dare'];
            $righe_documento[$key]['dare'] = $tmp;
        }
    }

    $righe = array_merge($righe, $righe_documento);
}

// Fatture
$numeri = [];
$counter = 0;

$id_documenti = array_unique($id_documenti);
$id_anagrafica_movimenti = null;
foreach ($id_documenti as $id_documento) {
    $fattura = Fattura::find($id_documento);
    $tipo = $fattura->tipo;
    $dir = $fattura->direzione;

    // Inclusione delle sole fatture in stato Emessa, Parzialmente pagato o Pagato
    if (!in_array($fattura->stato->descrizione, ['Emessa', 'Parzialmente pagato', 'Pagato'])) {
        ++$counter;
        continue;
    }

    if (empty($id_anagrafica_movimenti)) {
        $id_anagrafica_movimenti = $fattura->idanagrafica;
    } elseif ($fattura->idanagrafica != $id_anagrafica_movimenti) {
        $id_anagrafica_movimenti = null;
    }

    $numeri[] = !empty($fattura['numero_esterno']) ? $fattura['numero_esterno'] : $fattura['numero'];

    $nota_credito = $tipo->descrizione == 'Nota di credito';

    // Predisposizione prima riga
    $conto_field = 'idconto_'.($dir == 'entrata' ? 'vendite' : 'acquisti');
    $id_conto_aziendale = $fattura->pagamento[$conto_field] ?: get_var('Conto aziendale predefinito');

    // Predisposizione conto crediti clienti
    $conto_field = 'idconto_'.($dir == 'entrata' ? 'cliente' : 'fornitore');
    $id_conto_controparte = $fattura->anagrafica[$conto_field];

    // Lettura delle scadenza della fattura
    $scadenze = $dbo->fetchArray('SELECT id, ABS(da_pagare - pagato) AS rata, iddocumento FROM co_scadenziario WHERE iddocumento='.prepare($id_documento).' AND ABS(da_pagare) > ABS(pagato) ORDER BY YEAR(scadenza) ASC, MONTH(scadenza) ASC');

    // Selezione prima scadenza
    if ($singola_scadenza && !empty($scadenze)) {
        $scadenze = [$scadenze[0]];
    }

    $righe_documento = [];

    // Riga controparte
    foreach ($scadenze as $scadenza) {
        $righe_documento[] = [
            'iddocumento' => $scadenza['iddocumento'],
            'id_scadenza' => $scadenza['id'],
            'id_conto' => $id_conto_controparte,
            'dare' => ($dir == 'entrata' && !$nota_credito && !$is_insoluto) ? 0 : $scadenza['rata'],
            'avere' => ($dir == 'entrata' && !$nota_credito && !$is_insoluto) ? $scadenza['rata'] : 0,
        ];
    }

    // Riga aziendale
    $totale = sum(array_column($scadenze, 'rata'));

    if ($totale != 0) {
        if ($nota_credito) {
            $totale_rata = -$totale;
        } else {
            $totale_rata = $totale;
        }

        $righe_documento[] = [
            'iddocumento' => $scadenze[0]['iddocumento'],
            'id_scadenza' => $scadenze[0]['id'],
            'id_conto' => $id_conto_aziendale,
            'dare' => ($dir == 'entrata') ? $totale_rata : 0,
            'avere' => ($dir == 'entrata') ? 0 : $totale_rata,
        ];
    }

    $righe = array_merge($righe, $righe_documento);
}
/*
$k = 0;
foreach ($righe_azienda as $key => $riga_azienda) {
    if ($righe_azienda[$key]['id_conto'] != $righe_azienda[$key - 1]['id_conto']) {
        ++$k;
    }

    $riga_documento[$k]['iddocumento'] = $riga_azienda['iddocumento'];
    $riga_documento[$k]['id_scadenza'] = $riga_azienda['id_scadenza'];
    $riga_documento[$k]['id_conto'] = $riga_azienda['id_conto'];
    $riga_documento[$k]['dare'] += $riga_azienda['dare'];
    $riga_documento[$k]['avere'] += $riga_azienda['avere'];
$righe = array_merge($righe, $righe_azienda);
}*/

// Inverto dare e avere per importi negativi
foreach ($righe as $key => $value) {
    if ($righe[$key]['dare'] < 0 || $righe[$key]['avere'] < 0) {
        $tmp = abs($righe[$key]['dare']);
        $righe[$key]['dare'] = abs($righe[$key]['avere']);
        $righe[$key]['avere'] = $tmp;
    }
}

// Descrizione
$numero_scadenze = count($id_scadenze);
$numero_documenti = count($id_documenti);
if ($numero_documenti + $numero_scadenze > 1) {
    if (!empty($id_anagrafica_movimenti)) {
        $an = Anagrafica::find($id_anagrafica_movimenti);

        $descrizione = 'Pag. fatture '.$an->ragione_sociale.' num. '.implode(', ', $numeri);
    } else {
        $descrizione = 'Pag. fatture num. '.implode(', ', $numeri);
    }
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

if (!empty($id_records) && get('origine') == 'fatture' && !empty($counter)) {
    $descrizione_stati = [];
    $stati = $database->fetchArray("SELECT * FROM `co_statidocumento` WHERE descrizione IN ('Emessa', 'Parzialmente pagato', 'Pagato') ORDER BY descrizione");
    foreach ($stati as $stato) {
        $descrizione_stati[] = '<i class="'.$stato['icona'].'"></i> <small>'.$stato['descrizione'].'</small>';
    }

    echo '
<div class="alert alert-info">
<p>'.tr('Solo le fatture in stato _STATE_ possono essere registrate contabilmente ignorate', [
            '_STATE_' => implode(', ', $descrizione_stati),
        ]).'.</p>
<p><b>'.tr('Sono state ignorate _NUM_ fatture', [
            '_NUM_' => $counter,
        ]).'.</b></p>
</div>';
}

echo '
<form action="'.ROOTDIR.'/controller.php?id_module='.$module->id.'" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="crea_modello" id="crea_modello" value="0">
	<input type="hidden" name="idmastrino" id="idmastrino" value="0">
    <input type="hidden" name="is_insoluto" value="'.$is_insoluto.'">';

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

include $structure->filepath('movimenti.php');

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
    var nuovo_modello = "<?php echo tr('Aggiungi e crea modello'); ?>";
    var modifica_modello = "<?php echo tr('Aggiungi e modifica modello'); ?>";

    $(document).ready(function(e) {
        $("#bs-popup #add-form").on("submit", function(e) {
            return controllaConti();
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
