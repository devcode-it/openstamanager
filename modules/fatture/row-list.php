<?php

include_once __DIR__.'/../../core.php';

// Righe documento
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

foreach ($righe as $row) {
    $riga = $row->toArray();

    // Valori assoluti
    $riga['qta'] = abs($riga['qta']);
    $riga['prezzo_unitario_acquisto'] = abs($riga['prezzo_unitario_acquisto']);
    $riga['totale_imponibile'] = ($fattura->isNota() ? -$row->totale_imponibile : $row->totale_imponibile);
    $riga['sconto_unitario'] = abs($riga['sconto_unitario']);
    $riga['sconto'] = abs($riga['sconto']);
    $riga['iva'] = abs($riga['iva']);

    if (empty($riga['is_descrizione'])) {
        $riga['descrizione_conto'] = $dbo->fetchOne('SELECT descrizione FROM co_pianodeiconti3 WHERE id = '.prepare($riga['idconto']))['descrizione'];
    }

    $extra = '';

    $delete = 'unlink_riga';

    // Articoli
    if ($row->isArticolo()) {
        $riga['descrizione'] = (!empty($row->articolo) ? $row->articolo->codice.' - ' : '').$riga['descrizione'];

        $extra = '';
        $mancanti = 0;
    }

    // Intervento
    if (!empty($riga['idintervento'])) {
        $intervento = $dbo->fetchOne('SELECT num_item,codice_cig,codice_cup,id_documento_fe FROM in_interventi WHERE id = '.prepare($riga['idintervento']));
        $riga['num_item'] = $intervento['num_item'];
        $riga['codice_cig'] = $intervento['codice_cig'];
        $riga['codice_cup'] = $intervento['codice_cup'];
        $riga['id_documento_fe'] = $intervento['id_documento_fe'];

        $delete = 'unlink_intervento';
    }
    // Preventivi
    elseif (!empty($riga['idpreventivo'])) {
        $preventivo = $dbo->fetchOne('SELECT num_item,codice_cig,codice_cup,id_documento_fe FROM co_preventivi WHERE id = '.prepare($riga['idpreventivo']));
        $riga['num_item'] = $preventivo['num_item'];
        $riga['codice_cig'] = $preventivo['codice_cig'];
        $riga['codice_cup'] = $preventivo['codice_cup'];
        $riga['id_documento_fe'] = $preventivo['id_documento_fe'];
    }
    // Contratti
    elseif (!empty($riga['idcontratto'])) {
        $contratto = $dbo->fetchOne('SELECT num_item,codice_cig,codice_cup,id_documento_fe FROM co_contratti WHERE id = '.prepare($riga['idcontratto']));
        $riga['num_item'] = $contratto['num_item'];
        $riga['codice_cig'] = $contratto['codice_cig'];
        $riga['codice_cup'] = $contratto['codice_cup'];
        $riga['id_documento_fe'] = $contratto['id_documento_fe'];
    }
    // Ordini (IDDOCUMENTO,CIG,CUP)
    elseif (!empty($riga['idordine'])) {
        $ordine = $dbo->fetchOne('SELECT num_item,codice_cig,codice_cup,id_documento_fe FROM or_ordini WHERE id = '.prepare($riga['idordine']));
        $riga['num_item'] = $ordine['num_item'];
        $riga['codice_cig'] = $ordine['codice_cig'];
        $riga['codice_cup'] = $ordine['codice_cup'];
        $riga['id_documento_fe'] = $ordine['id_documento_fe'];
    }

    // Individuazione dei seriali
    if (!empty($riga['abilita_serial'])) {
        $serials = $row->serials;
        $mancanti = $riga['qta'] - count($serials);

        if ($mancanti > 0) {
            $extra = 'class="warning"';
        } else {
            $mancanti = 0;
        }
    }

    $extra_riga = '';
    if (!$riga['is_descrizione']) {
        $extra_riga = tr('_DESCRIZIONE_CONTO__ID_DOCUMENTO__NUMERO_RIGA__CODICE_CIG__CODICE_CUP_', [
            '_DESCRIZIONE_CONTO_' => $riga['descrizione_conto'] ?: null,
            '_ID_DOCUMENTO_' => $riga['id_documento_fe'] ? ' - DOC: '.$riga['id_documento_fe'] : null,
            '_NUMERO_RIGA_' => $riga['num_item'] ? ', NRI: '.$riga['num_item'] : null,
            '_CODICE_CIG_' => $riga['codice_cig'] ? ', CIG: '.$riga['codice_cig'] : null,
            '_CODICE_CUP_' => $riga['codice_cup'] ? ', CUP: '.$riga['codice_cup'] : null,
        ]);
    }

    echo '
    <tr data-id="'.$riga['id'].'" '.$extra.'>
        <td>
            '.Modules::link($row->isArticolo() ? Modules::get('Articoli')['id'] : null, $row->isArticolo() ? $riga['idarticolo'] : null, $riga['descrizione']).'
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
    if ($fattura->isNota() && !empty($record['ref_documento'])) {
        $data = $dbo->fetchArray("SELECT IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM co_documenti WHERE id = ".prepare($record['ref_documento']));

        $text = tr('Rif. fattura _NUM_ del _DATE_', [
                '_NUM_' => $data[0]['numero'],
                '_DATE_' => Translator::dateToLocale($data[0]['data']),
            ]);

        echo '
            <br>'.Modules::link($id_module, $record['ref_documento'], $text, $text);
    }

    $ref = doc_references($riga, $dir, ['iddocumento']);
    if (!empty($ref)) {
        echo '
            <br>'.Modules::link($ref['module'], $ref['id'], $ref['description'], $ref['description']);
    }

    echo '
        </td>';

    echo '
        <td class="text-center">';

    if (!$row->isDescrizione()) {
        echo '
            '.Translator::numberToLocale($riga['qta'], 'qta');
    }

    echo '
        </td>';

    // Unità di misura
    echo '
        <td class="text-center">';

    if (!$row->isDescrizione()) {
        echo '
            '.$riga['um'];
    }

    echo '
        </td>';

    // Prezzi unitari
    echo '
        <td class="text-right">';

    if (!$row->isDescrizione()) {
        echo '
            '.moneyFormat($row->prezzo_unitario_vendita);

        if ($dir == 'entrata' && $row->prezzo_unitario_acquisto != 0) {
            echo '
            <br><small>
                '.tr('Acquisto').': '.moneyFormat($row->prezzo_unitario_acquisto).'
            </small>';
        }

        if (abs($row->sconto_unitario) > 0) {
            $text = $row->sconto_unitario > 0 ? tr('sconto _TOT_ _TYPE_') : tr('maggiorazione _TOT_ _TYPE_');

            echo '
            <br><small class="label label-danger">'.replace($text, [
                '_TOT_' => Translator::numberToLocale(abs($row->sconto_unitario)),
                '_TYPE_' => ($row->tipo_sconto == 'PRC' ? '%' : currency()),
            ]).'</small>';
        }
    }

    echo '
        </td>';

    // Iva
    echo '
        <td class="text-right">';

    if (!$row->isDescrizione()) {
        echo '
            '.moneyFormat($riga['iva']).'
            <br><small class="'.(($row->aliquota->deleted_at) ? 'text-red' : '').' help-block">'.$row->aliquota->descrizione.(($row->aliquota->esente) ? ' ('.$row->aliquota->codice_natura_fe.')' : null).'</small>';
    }

    echo '
        </td>';

    // Importo
    echo '
        <td class="text-right">';
    if (!$row->isDescrizione()) {
        echo '
            '.moneyFormat($riga['totale_imponibile']);
        /*
        <br><small class="text-'.($row->guadagno > 0 ? 'success' : 'danger').'">
            '.tr('Guadagno').': '.moneyFormat($row->guadagno).'
        </small>';
        */
    }
    echo '
        </td>';

    // Possibilità di rimuovere una riga solo se la fattura non è pagata
    echo '
        <td class="text-center">';

    if ($record['stato'] != 'Pagato' && $record['stato'] != 'Emessa' && $riga['id'] != $fattura->rigaBollo->id) {
        echo "
            <form action='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record."' method='post' id='delete-form-".$riga['id']."' role='form'>
                <input type='hidden' name='backto' value='record-edit'>
                <input type='hidden' name='idriga' value='".$riga['id']."'>
                <input type='hidden' name='op' value='".$delete."'>";

        if ($row->isArticolo()) {
            echo "
                <input type='hidden' name='idarticolo' value='".$riga['idarticolo']."'>";
        }

        echo "
                <div class='input-group-btn'>";

        if (!$fattura->isNota() && $row->isArticolo() && $riga['abilita_serial'] && (empty($riga['idddt']) || empty($riga['idintervento']))) {
            echo "
                    <a class='btn btn-primary btn-xs'data-toggle='tooltip' title='Aggiorna SN...' onclick=\"launch_modal( 'Aggiorna SN', '".$structure->fileurl('add_serial.php').'?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$riga['id'].'&idarticolo='.$riga['idarticolo']."');\"><i class='fa fa-barcode' aria-hidden='true'></i></a>";
        }

        echo "
                    <a class='btn btn-xs btn-info'  title='".tr('Aggiungi informazioni FE per questa riga...')."' data-toggle='modal' data-title='".tr('Dati Fattura Elettronica')."' data-href='".$structure->fileurl('fe/row-fe.php').'?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$riga['id']."'>
                        <i class='fa fa-file-code-o '></i>
                    </a>

                    <a class='btn btn-xs btn-warning' title='".tr('Modifica questa riga...')."' onclick=\"launch_modal( 'Modifica riga', '".$structure->fileurl('row-edit.php').'?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$riga['id']."');\">
                        <i class='fa fa-edit'></i>
                    </a>

                    <a class='btn btn-xs btn-danger' title='".tr('Rimuovi questa riga...')."' onclick=\"if( confirm('".tr('Rimuovere questa riga dalla fattura?')."') ){ $('#delete-form-".$riga['id']."').submit(); }\">
                        <i class='fa fa-trash'></i>
                    </a>
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

echo '
    </tbody>';

$imponibile = abs($fattura->imponibile);
$sconto = $fattura->sconto;
$totale_imponibile = abs($fattura->totale_imponibile);
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
            '.moneyFormat($imponibile, 2).'
        </td>
        <td></td>
    </tr>';

// SCONTO
if (!empty($sconto)) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b><span class="tip" title="'.tr('Un importo positivo indica uno sconto, mentre uno negativo indica una maggiorazione').'"><i class="fa fa-question-circle-o"></i> '.tr('Sconto/maggiorazione', [], ['upper' => true]).':</span></b>
        </td>
        <td align="right">
            '.moneyFormat($sconto, 2).'
        </td>
        <td></td>
    </tr>';

    // TOTALE IMPONIBILE
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.moneyFormat($totale_imponibile, 2).'
        </td>
        <td></td>
    </tr>';
}

// RIVALSA INPS
if (!empty($fattura->rivalsa_inps)) {
    echo '
    <tr>
        <td colspan="5" class="text-right">';

    if ($dir == 'entrata') {
        echo '
				<span class="tip" title="'.$database->fetchOne('SELECT CONCAT_WS(\' - \', codice, descrizione) AS descrizione FROM fe_tipo_cassa WHERE codice = '.prepare(setting('Tipo Cassa Previdenziale')))['descrizione'].'"  > <i class="fa fa-question-circle-o"></i></span> ';
    }

    echo '
			<b>'.tr('Rivalsa', [], ['upper' => true]).' :</b>
        </td>
        <td align="right">
            '.moneyFormat($fattura->rivalsa_inps, 2).'
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
            '.moneyFormat($iva, 2).'
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
            '.moneyFormat($totale, 2).'
        </td>
        <td></td>
    </tr>';

// RITENUTA D'ACCONTO
if (!empty($fattura->ritenuta_acconto)) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr("Ritenuta d'acconto", [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.moneyFormat(abs($fattura->ritenuta_acconto), 2).'
        </td>
        <td></td>
    </tr>';
}

// RITENUTA CONTRIBUTI
if (!empty($fattura->totale_ritenuta_contributi)) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Ritenuta contributi', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.moneyFormat($fattura->totale_ritenuta_contributi).'
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
            '.moneyFormat($netto_a_pagare, 2).'
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
            '.moneyFormat($guadagno).'
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
