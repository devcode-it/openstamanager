<?php

include_once __DIR__.'/../../core.php';

/*
    Righe fattura
*/
//$rs = $dbo->fetchArray('SELECT *, round(iva,'.Settings::get('Cifre decimali per importi').') AS iva, round(sconto_unitario,'.Settings::get('Cifre decimali per importi').') AS sconto_unitario, round(sconto,'.Settings::get('Cifre decimali per importi').') AS sconto, round(subtotale,'.Settings::get('Cifre decimali per importi').') AS subtotale, IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo),"") AS codice, (SELECT descrizione FROM co_pianodeiconti3 WHERE co_pianodeiconti3.id=IF(co_righe_documenti.idconto = 0, (SELECT idconto FROM co_documenti WHERE iddocumento='.prepare($id_record).' LIMIT 1), co_righe_documenti.idconto)) AS descrizione_conto FROM `co_righe_documenti` WHERE iddocumento='.prepare($id_record).' ORDER BY `order`');
$rs = $dbo->fetchArray('SELECT *, round(sconto_unitario,'.Settings::get('Cifre decimali per importi').') AS sconto_unitario, round(sconto,'.Settings::get('Cifre decimali per importi').') AS sconto, round(subtotale,'.Settings::get('Cifre decimali per importi').') AS subtotale, IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo),"") AS codice, (SELECT descrizione FROM co_pianodeiconti3 WHERE co_pianodeiconti3.id=IF(co_righe_documenti.idconto = 0, (SELECT idconto FROM co_documenti WHERE iddocumento='.prepare($id_record).' LIMIT 1), co_righe_documenti.idconto)) AS descrizione_conto FROM `co_righe_documenti` WHERE iddocumento='.prepare($id_record).' ORDER BY `order`');

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <thead>
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th width="120">'.tr('Q.tà').'</th>
            <th width="80">'.tr('U.m.').'</th>
            <th width="120">'.tr('Costo unitario').'</th>
            <th width="120">'.tr('Iva').'</th>
            <th width="120">'.tr('Imponibile').'</th>
            <th width="60"></th>
        </tr>
    </thead>
    <tbody class="sortable">';

if (!empty($rs)) {
    foreach ($rs as $r) {
        $extra = '';

        $ref_modulo = null;
        $ref_id = null;

        // Articoli
        if (!empty($r['idarticolo'])) {
            $ref_modulo = Modules::get('Articoli')['id'];
            $ref_id = $r['idarticolo'];

            $r['descrizione'] = (!empty($r['codice']) ? $r['codice'].' - ' : '').$r['descrizione'];

            $delete = 'unlink_articolo';

            $extra = '';
            $mancanti = 0;

            // Individuazione dei seriali
            if (!empty($r['abilita_serial'])) {
                $serials = array_column($dbo->fetchArray('SELECT serial FROM mg_prodotti WHERE serial IS NOT NULL AND id_riga_documento='.prepare($r['id'])), 'serial');
                $mancanti = $r['qta'] - count($serials);

                if ($mancanti > 0) {
                    $extra = 'class="warning"';
                } else {
                    $mancanti = 0;
                }
            }
        }
        // Preventivi
        elseif (!empty($r['idpreventivo'])) {
            $delete = 'unlink_preventivo';
        }
        // Contratti
        elseif (!empty($r['idcontratto'])) {
            $delete = 'unlink_contratto';
        }
        // Intervento
        elseif (!empty($r['idintervento'])) {
            $delete = 'unlink_intervento';
        }
        // Righe generiche
        else {
            $delete = 'unlink_riga';
        }

        echo '
    <tr data-id="'.$r['id'].'" '.$extra.'>
        <td>
            '.Modules::link($ref_modulo, $ref_id, $r['descrizione']).'
            <small class="pull-right text-muted">'.$r['descrizione_conto'].'</small>';

        if (!empty($r['abilita_serial'])) {
            if (!empty($mancanti)) {
                echo '
            <br><b><small class="text-danger">'.tr('_NUM_ serial mancanti', [
                '_NUM_' => $mancanti,
            ]).'</small></b>';
            }
            if (!empty($serials)) {
                echo '
            <br>'.tr('SN').': '.implode(', ', $serials);
            }
        }

        $ref_modulo = null;
        $ref_id = null;

        // Aggiunta dei riferimenti ai documenti
        // Ordine
        if (!empty($r['idordine'])) {
            $data = $dbo->fetchArray("SELECT IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM or_ordini WHERE id=".prepare($r['idordine']));

            $ref_modulo = ($dir == 'entrata') ? 'Ordini cliente' : 'Ordini fornitore';
            $ref_id = $r['idordine'];

            $documento = tr('Ordine');
        }
        // DDT
        elseif (!empty($r['idddt'])) {
            $data = $dbo->fetchArray("SELECT IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM dt_ddt WHERE id=".prepare($r['idddt']));

            $ref_modulo = ($dir == 'entrata') ? 'Ddt di vendita' : 'Ddt di acquisto';
            $ref_id = $r['idddt'];

            $documento = tr('Ddt');
        }
        // Preventivo
        elseif (!empty($r['idpreventivo'])) {
            $data = $dbo->fetchArray('SELECT numero, data_bozza AS data FROM co_preventivi WHERE id='.prepare($r['idpreventivo']));

            $ref_modulo = 'Preventivi';
            $ref_id = $r['idpreventivo'];

            $documento = tr('Preventivo');
        }
        // Contratto
        elseif (!empty($r['idcontratto'])) {
            $data = $dbo->fetchArray('SELECT numero, data_bozza AS data FROM co_contratti WHERE id='.prepare($r['idcontratto']));

            $ref_modulo = 'Contratti';
            $ref_id = $r['idcontratto'];

            $documento = tr('Contratto');
        }
        // Intervento
        elseif (!empty($r['idintervento'])) {
            $data = $dbo->fetchArray('SELECT codice AS numero, IFNULL( (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento=in_interventi.id), data_richiesta) AS data FROM in_interventi WHERE id='.prepare($r['idintervento']));

            $ref_modulo = 'Interventi';
            $ref_id = $r['idintervento'];

            $documento = tr('Intervento');
        }

        if (!empty($ref_modulo) && !empty($ref_id)) {
            $documento = Stringy\Stringy::create($documento)->toLowerCase();

            if (!empty($data)) {
                $descrizione = tr('Rif. _DOC_ num. _NUM_ del _DATE_', [
                    '_DOC_' => $documento,
                    '_NUM_' => $data[0]['numero'],
                    '_DATE_' => Translator::dateToLocale($data[0]['data']),
                ]);
            } else {
                $descrizione = tr('_DOC_ di riferimento _ID_ eliminato', [
                    '_DOC_' => $documento->upperCaseFirst(),
                    '_ID_' => $ref_id,
                ]);
            }

            echo '
            <br>'.Modules::link($ref_modulo, $ref_id, $descrizione, $descrizione);
        }

        echo '
        </td>';

        echo '
        <td class="text-right">';

        if (empty($r['is_descrizione'])) {
            echo '
            '.Translator::numberToLocale($r['qta']);
        }

        echo '
        </td>';

        // Unità di misura
        echo '
        <td class="text-center">';

        if (empty($r['is_descrizione'])) {
            echo '
            '.$r['um'];
        }

        echo '
        </td>';

        // Costo unitario
        echo '
        <td class="text-right">';

        if (empty($r['is_descrizione'])) {
            echo '
            '.Translator::numberToLocale($r['subtotale'] / $r['qta']).' &euro;';

            if ($r['sconto_unitario'] > 0) {
                echo '
            <br><small class="label label-danger">'.tr('sconto _TOT_ _TYPE_', [
                '_TOT_' => Translator::numberToLocale($r['sconto_unitario']),
                '_TYPE_' => ($r['tipo_sconto'] == 'PRC' ? '%' : '&euro;'),
            ]).'</small>';
            }
        }

        echo '
        </td>';

        // Iva
        echo '
        <td class="text-right">';

        if (empty($r['is_descrizione'])) {
            echo '
            '.Translator::numberToLocale($r['iva']).' &euro;
            <br><small class="help-block">'.$r['desc_iva'].'</small>
            <small>'.$r['iva'].'</small>';
        }

        echo '
        </td>';

        // Imponibile
        echo '
        <td class="text-right">';
        if (empty($r['is_descrizione'])) {
            echo '
            '.Translator::numberToLocale($r['subtotale'] - $r['sconto']).' &euro;';
        }
        echo '
        </td>';

        // Possibilità di rimuovere una riga solo se la fattura non è pagata
        echo '
        <td class="text-center">';

        if ($records[0]['stato'] != 'Pagato' && $records[0]['stato'] != 'Emessa' && empty($r['sconto_globale'])) {
            echo "
            <form action='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record."' method='post' id='delete-form-".$r['id']."' role='form'>
                <input type='hidden' name='backto' value='record-edit'>
                <input type='hidden' name='idriga' value='".$r['id']."'>
                <input type='hidden' name='op' value='".$delete."'>";

            if (!empty($r['idarticolo'])) {
                echo "
                <input type='hidden' name='idarticolo' value='".$r['idarticolo']."'>";
            }

            echo "
                <div class='input-group-btn'>";

            if (!empty($r['idarticolo']) && $r['abilita_serial'] && (empty($r['idddt']) || empty($r['idintervento']))) {
                echo "
                    <a class='btn btn-primary btn-xs'data-toggle='tooltip' title='Aggiorna SN...' onclick=\"launch_modal( 'Aggiorna SN', '".$rootdir.'/modules/fatture/add_serial.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id'].'&idarticolo='.$r['idarticolo']."', 1 );\"><i class='fa fa-barcode' aria-hidden='true'></i></a>";
            }

            echo "
                    <a class='btn btn-xs btn-warning' title='Modifica questa riga...' onclick=\"launch_modal( 'Modifica riga', '".$rootdir.'/modules/fatture/row-edit.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id']."', 1 );\"><i class='fa fa-edit'></i></a>

                    <a class='btn btn-xs btn-danger' title='Rimuovi questa riga...' onclick=\"if( confirm('Rimuovere questa riga dalla fattura?') ){ $('#delete-form-".$r['id']."').submit(); }\"><i class='fa fa-trash'></i></a>
                </div>
            </form>";
        }

        if (empty($r['sconto_globale'])) {
            echo '
            <div class="handle clickable" style="padding:10px">
                <i class="fa fa-sort"></i>
            </div>';
        }

        echo '
        </td>

    </tr>';
    }
}

echo '
    </tbody>';

// Calcoli
$imponibile = sum(array_column($rs, 'subtotale'));
$sconto = sum(array_column($rs, 'sconto'));
$iva = sum(array_column($rs, 'iva'));

$imponibile_scontato = sum($imponibile, -$sconto);

$totale_iva = sum($iva, $records[0]['iva_rivalsainps']);

$totale = sum([
    $imponibile_scontato,
    $records[0]['rivalsainps'],
    $totale_iva,
]);

$netto_a_pagare = sum([
    $totale,
    $records[0]['bollo'],
    -$records[0]['ritenutaacconto'],
]);

// IMPONIBILE
echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($imponibile).' &euro;
        </td>
        <td></td>
    </tr>';

// SCONTO
if (abs($sconto) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($sconto).' &euro;
        </td>
        <td></td>
    </tr>';

    // IMPONIBILE SCONTATO
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Imponibile scontato', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($imponibile_scontato).' &euro;
        </td>
        <td></td>
    </tr>';
}

// RIVALSA INPS
if (abs($records[0]['rivalsainps']) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Rivalsa INPS', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($records[0]['rivalsainps']).' &euro;
        </td>
        <td></td>
    </tr>';
}

// IVA
if (abs($totale_iva) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Iva', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($totale_iva).' &euro;
        </td>
        <td></td>
    </tr>';
}

// TOTALE
echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Totale', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($totale).' &euro;
        </td>
        <td></td>
    </tr>';

// Mostra marca da bollo se c'è
if (abs($records[0]['bollo']) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Marca da bollo', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($records[0]['bollo']).' &euro;
        </td>
        <td></td>
    </tr>';
}

// RITENUTA D'ACCONTO
if (abs($records[0]['ritenutaacconto']) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr("Ritenuta d'acconto", [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($records[0]['ritenutaacconto']).' &euro;
        </td>
        <td></td>
    </tr>';

    //$netto_a_pagare -= $records[0]['ritenutaacconto'];
}

// NETTO A PAGARE
if ($totale != $netto_a_pagare) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Netto a pagare', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($netto_a_pagare).' &euro;
        </td>
        <td></td>
    </tr>';
}

echo '
</table>';

echo '
<script>
$(document).ready(function(){
	$(".sortable").each(function() {
        $(this).sortable({
            axis: "y",
            handle: ".handle",
			cursor: "move",
			dropOnEmpty: true,
			scroll: true,
			start: function(event, ui) {
				ui.item.data("start", ui.item.index());
			},
			update: function(event, ui) {
				$.post("'.$rootdir.'/actions.php", {
					id: ui.item.data("id"),
					id_module: '.$id_module.',
					id_record: '.$id_record.',
					op: "update_position",
					start: ui.item.data("start"),
					end: ui.item.index()
				});
			}
		});
	});
});
</script>';
