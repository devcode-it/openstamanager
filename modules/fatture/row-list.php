<?php

include_once __DIR__.'/../../core.php';

use Modules\Fatture\Components\Articolo;
use Modules\Fatture\Components\Descrizione;
use Modules\Fatture\Components\Riga;

// Righe fattura
$righe = $fattura->getRighe();

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

foreach ($righe as $riga) {
    // Valori assoluti
    $riga['qta'] = abs($riga['qta']);
    $riga['prezzo_unitario_acquisto'] = abs($riga['prezzo_unitario_acquisto']);
    $riga['subtotale'] = abs($riga['subtotale']);
    $riga['sconto_unitario'] = abs($riga['sconto_unitario']);
    $riga['sconto'] = abs($riga['sconto']);
    $riga['iva'] = abs($riga['iva']);
	
	$riga['descrizione_conto'] = $dbo->fetchOne("SELECT descrizione FROM co_pianodeiconti3 WHERE id = ".prepare($riga['idconto']))['descrizione'];

    $extra = '';

    $ref_modulo = null;
    $ref_id = null;
	
    // Preventivi
    if (!empty($riga['idpreventivo'])) {
		$ref_modulo = Modules::get('Preventivi')['id'];
		$ref_id = $riga['idpreventivo'];
        $delete = 'unlink_preventivo';
    }
    // Contratti
    elseif (!empty($riga['idcontratto'])) {
		
		$ref_modulo = Modules::get('Contratti')['id'];
		$ref_id = $riga['idcontratto'];
		
		$contratto = $dbo->fetchOne("SELECT codice_cig,codice_cup,id_documento_fe FROM co_contratti WHERE id = ".prepare($riga['idcontratto']));
		$riga['codice_cig'] = $contratto['codice_cig'];
		$riga['codice_cup'] = $contratto['codice_cup'];
		$riga['id_documento_fe'] = $contratto['id_documento_fe'];
		
		$delete = 'unlink_contratto';
		
    }
    // Intervento
    elseif (!empty($riga['idintervento'])) {
		$ref_modulo = Modules::get('Interventi')['id'];
		$ref_id = $riga['idintervento'];
        $delete = 'unlink_intervento';
    }
    // Articoli
    elseif ($riga instanceof Articolo) {
        $ref_modulo = Modules::get('Articoli')['id'];
        $ref_id = $riga['idarticolo'];

        $riga['descrizione'] = (!empty($riga->articolo) ? $riga->articolo->codice.' - ' : '').$riga['descrizione'];

        $delete = 'unlink_articolo';

        $extra = '';
        $mancanti = 0;
		
    }
    // Righe generiche
    else {
        $delete = 'unlink_riga';
    }

    // Individuazione dei seriali
    if (!empty($riga['abilita_serial'])) {
        $serials = $riga->serials;
        $mancanti = $riga['qta'] - count($serials);

        if ($mancanti > 0) {
            $extra = 'class="warning"';
        } else {
            $mancanti = 0;
        }
    }
	
	$extra_riga = '';
	$extra_riga = tr('_DESCRIZIONE_CONTO_ _CODICE_CIG_ _CODICE_CUP_ _ID_DOCUMENTO_', [
		'_DESCRIZIONE_CONTO_' => $riga['descrizione_conto'] ?: null,
		'_CODICE_CIG_' =>  $riga['codice_cig'] ? '<br>CIG: '.$riga['codice_cig'] : null,
		'_CODICE_CUP_' =>  $riga['codice_cup'] ? '<br>CUP: '.$riga['codice_cup'] : null,
		'_ID_DOCUMENTO_' =>  $riga['id_documento_fe'] ? '<br>DOC: '.$riga['id_documento_fe'] : null,
	]);
	
    echo '
    <tr data-id="'.$riga['id'].'" '.$extra.'>
        <td>
            '.Modules::link($ref_modulo, $ref_id, $riga['descrizione']).'
            <small class="pull-right text-muted">'.$extra_riga.'</small>';

    if (!empty($riga['abilita_serial'])) {
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
    if ($fattura->isNotaDiAccredito()) {
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
        <td class="text-center">';

    if (!$riga instanceof Descrizione) {
        echo '
            '.Translator::numberToLocale($riga->qta, 'qta');
    }

    echo '
        </td>';

    // Unità di misura
    echo '
        <td class="text-center">';

    if (!$riga instanceof Descrizione) {
        echo '
            '.$riga['um'];
    }

    echo '
        </td>';

    // Prezzi unitari
    echo '
        <td class="text-right">';

    if (!$riga instanceof Descrizione) {
        echo '
            '.Translator::numberToLocale($riga->prezzo_unitario_vendita).' &euro;';

        if ($dir == 'entrata') {
            echo '
            <br><small>
                '.tr('Acquisto').': '.Translator::numberToLocale($riga->prezzo_unitario_acquisto).' &euro;
            </small>';
        }

        if ($riga->sconto_unitario > 0) {
            echo '
            <br><small class="label label-danger">'.tr('sconto _TOT_ _TYPE_', [
                '_TOT_' => Translator::numberToLocale($riga->sconto_unitario),
                '_TYPE_' => ($riga->tipo_sconto == 'PRC' ? '%' : '&euro;'),
            ]).'</small>';
        }
    }

    echo '
        </td>';

    // Iva
    echo '
        <td class="text-right">';

    if (!$riga instanceof Descrizione) {
        echo '
            '.Translator::numberToLocale($riga->iva).' &euro;
            <br><small class="help-block">'.$riga->desc_iva.'</small>';
    }

    echo '
        </td>';

    // Importo
    echo '
        <td class="text-right">';
    if (!$riga instanceof Descrizione) {
        echo '
            '.Translator::numberToLocale($riga->imponibile_scontato).' &euro;';
        /*
        <br><small class="text-'.($riga->guadagno > 0 ? 'success' : 'danger').'">
            '.tr('Guadagno').': '.Translator::numberToLocale($riga->guadagno).' &euro;
        </small>';
        */
    }
    echo '
        </td>';

    // Possibilità di rimuovere una riga solo se la fattura non è pagata
    echo '
        <td class="text-center">';

    if ($record['stato'] != 'Pagato' && $record['stato'] != 'Emessa') {
        echo "
            <form action='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record."' method='post' id='delete-form-".$riga['id']."' role='form'>
                <input type='hidden' name='backto' value='record-edit'>
                <input type='hidden' name='idriga' value='".$riga['id']."'>
                <input type='hidden' name='op' value='".$delete."'>";

        if ($riga instanceof Articolo) {
            echo "
                <input type='hidden' name='idarticolo' value='".$riga['idarticolo']."'>";
        }

        echo "
                <div class='input-group-btn'>";

        if (!$fattura->isNotaDiAccredito() && $riga instanceof Articolo && $riga['abilita_serial'] && (empty($riga['idddt']) || empty($riga['idintervento']))) {
            echo "
                    <a class='btn btn-primary btn-xs'data-toggle='tooltip' title='Aggiorna SN...' onclick=\"launch_modal( 'Aggiorna SN', '".$rootdir.'/modules/fatture/add_serial.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$riga['id'].'&idarticolo='.$riga['idarticolo']."', 1 );\"><i class='fa fa-barcode' aria-hidden='true'></i></a>";
        }

        echo "
                    <a class='btn btn-xs btn-warning' title='Modifica questa riga...' onclick=\"launch_modal( 'Modifica riga', '".$rootdir.'/modules/fatture/row-edit.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$riga['id']."', 1 );\"><i class='fa fa-edit'></i></a>

                    <a class='btn btn-xs btn-danger' title='Rimuovi questa riga...' onclick=\"if( confirm('Rimuovere questa riga dalla fattura?') ){ $('#delete-form-".$riga['id']."').submit(); }\"><i class='fa fa-trash'></i></a>
                </div>
            </form>";
    }

    echo '
            <div class="handle clickable" style="padding:10px">
                <i class="fa fa-sort"></i>
            </div>';

    echo '
        </td>

    </tr>';
}

$sconto_globale = $fattura->scontoGlobale;
if (!empty($sconto_globale)) {
    echo '
    <tr>
        <td>'.$sconto_globale->descrizione.'</td>
        <td class="text-center">'.Translator::numberToLocale(1, 'qta').'</td>
        <td></td>
        <td class="text-right">'.Translator::numberToLocale($sconto_globale->totale).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($sconto_globale->iva).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($sconto_globale->totale).' &euro;</td>
    </tr>';
}

echo '
    </tbody>';

$imponibile = abs($fattura->imponibile);
$sconto = abs($fattura->sconto);
$imponibile_scontato = abs($fattura->imponibile_scontato);
$iva = abs($fattura->iva);
$totale = abs($fattura->totale);
$netto_a_pagare = abs($fattura->netto);
$guadagno = $fattura->guadagno;

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
if (!empty($sconto)) {
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
if (!empty($fattura->rivalsa_inps)) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Rivalsa INPS', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($fattura->rivalsa_inps).' &euro;
        </td>
        <td></td>
    </tr>';
}

// IVA
if (!empty($iva)) {
    echo '
    <tr>
        <td colspan="5" class="text-right">';

    if ($records[0]['split_payment']) {
        echo '<b>'.tr('Iva a carico del destinatario', [], ['upper' => true]).':</b>';
    } else {
        echo '<b>'.tr('Iva', [], ['upper' => true]).':</b>';
    }
    echo '
        </td>
        <td align="right">
            '.Translator::numberToLocale($iva).' &euro;
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
if (!empty($fattura->bollo)) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Marca da bollo', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($fattura->bollo).' &euro;
        </td>
        <td></td>
    </tr>';
}

// RITENUTA D'ACCONTO
if (!empty($fattura->ritenuta_acconto)) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr("Ritenuta d'acconto", [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($fattura->ritenuta_acconto).' &euro;
        </td>
        <td></td>
    </tr>';
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

// GUADAGNO TOTALE
if ($dir == 'entrata') {
    $guadagno_style = $guadagno < 0 ? 'background-color: #FFC6C6; border: 3px solid red' : '';

    /*
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Guadagno', [], ['upper' => true]).':</b>
        </td>
        <td align="right" style="'.$guadagno_style.'">
            '.Translator::numberToLocale($guadagno).' &euro;
        </td>
        <td></td>
    </tr>';
    */
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
			update: function(event, ui) {
                var order = "";
                $(".table tr[data-id]").each( function(){
                    order += ","+$(this).data("id");
                });
                order = order.replace(/^,/, "");

				$.post("'.$rootdir.'/actions.php", {
					id: ui.item.data("id"),
					id_module: '.$id_module.',
					id_record: '.$id_record.',
					op: "update_position",
                    order: order,
				});
			}
		});
	});
});
</script>';
