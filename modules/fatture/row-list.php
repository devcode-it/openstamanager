<?php

include_once __DIR__.'/../../core.php';

include_once Modules::filepath('Fatture di vendita', 'modutil.php');

/*
    Righe fattura
*/
$rs = $dbo->fetchArray('SELECT *, round(sconto_unitario,'.setting('Cifre decimali per importi').') AS sconto_unitario, round(sconto,'.setting('Cifre decimali per importi').') AS sconto, round(subtotale,'.setting('Cifre decimali per importi').') AS subtotale, IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo),"") AS codice, (SELECT descrizione FROM co_pianodeiconti3 WHERE co_pianodeiconti3.id=IF(co_righe_documenti.idconto = 0, (SELECT idconto FROM co_documenti WHERE iddocumento='.prepare($id_record).' LIMIT 1), co_righe_documenti.idconto)) AS descrizione_conto FROM `co_righe_documenti` WHERE iddocumento='.prepare($id_record).' ORDER BY `order`');

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <thead>
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th width="120">'.tr('Q.tà').'</th>
            <th width="80">'.tr('U.m.').'</th>
            <th width="120">'.tr('Prezzo unitario').'</th>
            <th width="120">'.tr('Iva').'</th>
            <th width="120">'.tr('Importo').'</th>
            <th width="60"></th>
        </tr>
    </thead>
    <tbody class="sortable">';

if (!empty($rs)) {
    foreach ($rs as $r) {
        // Valori assoluti
        $r['qta'] = abs($r['qta']);
        $r['subtotale'] = abs($r['subtotale']);
        $r['sconto_unitario'] = abs($r['sconto_unitario']);
        $r['sconto'] = abs($r['sconto']);
        $r['iva'] = abs($r['iva']);

        $extra = '';

        $ref_modulo = null;
        $ref_id = null;

        // Preventivi
        if (!empty($r['idpreventivo'])) {
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
        // Articoli
        elseif (!empty($r['idarticolo'])) {
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

        // Aggiunta dei riferimenti ai documenti
        if (!empty($record['ref_documento'])) {
            $data = $dbo->fetchArray("SELECT IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM co_documenti WHERE id = ".prepare($record['ref_documento']));

            $text = tr('Rif. fattura _NUM_ del _DATE_', [
                '_NUM_' => $data[0]['numero'],
                '_DATE_' => Translator::dateToLocale($data[0]['data']),
            ]);

            echo '
            <br>'.Modules::link('Fatture di vendita', $record['ref_documento'], $text, $text);
        }

        $ref = doc_references($r, $dir, ['iddocumento']);
        if (!empty($ref)) {
            echo '
            <br>'.Modules::link($ref['module'], $ref['id'], $ref['description'], $ref['description']);
        }

        echo '
        </td>';

        echo '
        <td class="text-right">';

        if (empty($r['is_descrizione'])) {
            echo '
            '.Translator::numberToLocale($r['qta'], 'qta');
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

        // Prezzo unitario
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
            <br><small class="help-block">'.$r['desc_iva'].'</small>';
        }

        echo '
        </td>';

        // Importo
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

        if ($record['stato'] != 'Pagato' && $record['stato'] != 'Emessa' && empty($r['sconto_globale'])) {
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

            if (empty($record['is_reversed']) && !empty($r['idarticolo']) && $r['abilita_serial'] && (empty($r['idddt']) || empty($r['idintervento']))) {
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

$totale_iva = sum($iva, $record['iva_rivalsainps']);

$totale = sum([
    $imponibile_scontato,
    $record['rivalsainps'],
    $totale_iva,
]);

$netto_a_pagare = sum([
    $totale,
    $record['bollo'],
    -$record['ritenutaacconto'],
]);

$imponibile = abs($imponibile);
$sconto = abs($sconto);
$iva = abs($iva);
$imponibile_scontato = abs($imponibile_scontato);
$totale_iva = abs($totale_iva);
$totale = abs($totale);
$netto_a_pagare = abs($netto_a_pagare);

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
if (abs($record['rivalsainps']) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Rivalsa INPS', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($record['rivalsainps']).' &euro;
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
if (abs($record['bollo']) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Marca da bollo', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($record['bollo']).' &euro;
        </td>
        <td></td>
    </tr>';
}

// RITENUTA D'ACCONTO
if (abs($record['ritenutaacconto']) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr("Ritenuta d'acconto", [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($record['ritenutaacconto']).' &euro;
        </td>
        <td></td>
    </tr>';

    //$netto_a_pagare -= $record['ritenutaacconto'];
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
