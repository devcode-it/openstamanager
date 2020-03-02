<?php

include_once __DIR__.'/../../core.php';

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <thead>
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th class="text-center" width="150">'.tr('Q.tà').'</th>
            <th class="text-center" width="150">'.tr('Prezzo unitario').'</th>
            <th class="text-center" width="150">'.tr('Iva unitaria').'</th>
            <th class="text-center" width="150">'.tr('Importo').'</th>
            <th width="60"></th>
        </tr>
    </thead>
    <tbody class="sortable">';

// Righe documento
$righe = $fattura->getRighe();
foreach ($righe as $riga) {
    $r = $riga->toArray();

    // Valori assoluti
    $r['qta'] = abs($r['qta']);
    $r['costo_unitario'] = abs($r['costo_unitario']);
    $r['totale_imponibile'] = ($fattura->isNota() ? -$riga->totale_imponibile : $riga->totale_imponibile);
    $r['sconto_unitario'] = abs($r['sconto_unitario']);
    $r['sconto'] = abs($r['sconto']);
    $r['iva'] = abs($r['iva']);

    if (empty($r['is_descrizione'])) {
        $r['descrizione_conto'] = $dbo->fetchOne('SELECT descrizione FROM co_pianodeiconti3 WHERE id = '.prepare($r['idconto']))['descrizione'];
    }

    $r['ritenuta_acconto'] = !empty($r['idritenutaacconto']) ? moneyFormat(abs($r['ritenutaacconto']), 2) : null;
    $r['ritenuta_contributi'] = !empty($r['ritenuta_contributi']) ? moneyFormat(abs($r['ritenuta_contributi']), 2) : null;
    $r['rivalsa'] = !empty($r['idrivalsainps']) ? moneyFormat(abs($r['rivalsainps']), 2) : null;

    $extra = '';

    $delete = 'delete_riga';

    // Articoli
    if ($riga->isArticolo()) {
        $r['descrizione'] = (!empty($riga->articolo) ? $riga->articolo->codice.' - ' : '').$r['descrizione'];

        $extra = '';
        $mancanti = 0;
    }

    // Intervento
    if (!empty($r['idintervento'])) {
        $intervento = $dbo->fetchOne('SELECT num_item,codice_cig,codice_cup,id_documento_fe FROM in_interventi WHERE id = '.prepare($r['idintervento']));
        $r['num_item'] = $intervento['num_item'];
        $r['codice_cig'] = $intervento['codice_cig'];
        $r['codice_cup'] = $intervento['codice_cup'];
        $r['id_documento_fe'] = $intervento['id_documento_fe'];

        $delete = 'unlink_intervento';
    }
    // Preventivi
    elseif (!empty($r['idpreventivo'])) {
        $preventivo = $dbo->fetchOne('SELECT num_item,codice_cig,codice_cup,id_documento_fe FROM co_preventivi WHERE id = '.prepare($r['idpreventivo']));
        $r['num_item'] = $preventivo['num_item'];
        $r['codice_cig'] = $preventivo['codice_cig'];
        $r['codice_cup'] = $preventivo['codice_cup'];
        $r['id_documento_fe'] = $preventivo['id_documento_fe'];
    }
    // Contratti
    elseif (!empty($r['idcontratto'])) {
        $contratto = $dbo->fetchOne('SELECT num_item,codice_cig,codice_cup,id_documento_fe FROM co_contratti WHERE id = '.prepare($r['idcontratto']));
        $r['num_item'] = $contratto['num_item'];
        $r['codice_cig'] = $contratto['codice_cig'];
        $r['codice_cup'] = $contratto['codice_cup'];
        $r['id_documento_fe'] = $contratto['id_documento_fe'];
    }
    // Ordini (IDDOCUMENTO,CIG,CUP)
    elseif (!empty($r['idordine'])) {
        $ordine = $dbo->fetchOne('SELECT num_item,codice_cig,codice_cup,id_documento_fe FROM or_ordini WHERE id = '.prepare($r['idordine']));
        $r['num_item'] = $ordine['num_item'];
        $r['codice_cig'] = $ordine['codice_cig'];
        $r['codice_cup'] = $ordine['codice_cup'];
        $r['id_documento_fe'] = $ordine['id_documento_fe'];
    }

    // Individuazione dei seriali
    if (!empty($r['abilita_serial'])) {
        $serials = $riga->serials;
        $mancanti = $r['qta'] - count($serials);

        if ($mancanti > 0) {
            $extra = 'class="warning"';
        } else {
            $mancanti = 0;
        }
    }

    $extra_riga = '';
    if (!$r['is_descrizione']) {
        $extra_riga = tr('_DESCRIZIONE_CONTO__ID_DOCUMENTO__NUMERO_RIGA__CODICE_CIG__CODICE_CUP__RITENUTA_ACCONTO__RITENUTA_CONTRIBUTI__RIVALSA_', [
            '_RIVALSA_' => $r['rivalsa'] ? '<br>Rivalsa: '.$r['rivalsa'] : null,
            '_RITENUTA_ACCONTO_' => $r['ritenuta_acconto'] ? '<br>Ritenuta acconto: '.$r['ritenuta_acconto'] : null,
            '_RITENUTA_CONTRIBUTI_' => $r['ritenuta_contributi'] ? '<br>Ritenuta contributi: '.$r['ritenuta_contributi'] : null,
            '_DESCRIZIONE_CONTO_' => $r['descrizione_conto'] ?: null,
            '_ID_DOCUMENTO_' => $r['id_documento_fe'] ? ' - DOC: '.$r['id_documento_fe'] : null,
            '_NUMERO_RIGA_' => $r['num_item'] ? ', NRI: '.$r['num_item'] : null,
            '_CODICE_CIG_' => $r['codice_cig'] ? ', CIG: '.$r['codice_cig'] : null,
            '_CODICE_CUP_' => $r['codice_cup'] ? ', CUP: '.$r['codice_cup'] : null,
        ]);
    }

    echo '
    <tr data-id="'.$r['id'].'" '.$extra.'>
        <td>
            '.Modules::link($riga->isArticolo() ? Modules::get('Articoli')['id'] : null, $riga->isArticolo() ? $r['idarticolo'] : null, $r['descrizione']).'
            <small class="pull-right text-right text-muted">'.$extra_riga.'</small>';

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
    if ($fattura->isNota() && !empty($record['ref_documento'])) {
        $data = $dbo->fetchArray("SELECT IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM co_documenti WHERE id = ".prepare($record['ref_documento']));

        $text = tr('Rif. fattura _NUM_ del _DATE_', [
                '_NUM_' => $data[0]['numero'],
                '_DATE_' => Translator::dateToLocale($data[0]['data']),
            ]);

        echo '
            <br>'.Modules::link($id_module, $record['ref_documento'], $text, $text);
    }

    $ref = doc_references($r, $dir, ['iddocumento']);
    if (!empty($ref)) {
        echo '
            <br>'.Modules::link($ref['module'], $ref['id'], $ref['description'], $ref['description']);
    }

    echo '
        </td>';

    if ($riga->isDescrizione()) {
        echo '
            <td></td>
            <td></td>
            <td></td>
            <td></td>';
    } else {
        // Quantità e unità di misura
        echo '
        <td class="text-center">
            '.numberFormat($riga->qta, 'qta').' '.$r['um'].'
        </td>';

        // Prezzi unitari
        echo '
        <td class="text-right">
            '.moneyFormat($riga->prezzo_unitario_corrente);

        if ($dir == 'entrata' && $riga->costo_unitario != 0) {
            echo '
            <br><small>
                '.tr('Acquisto').': '.moneyFormat($riga->costo_unitario).'
            </small>';
        }

        if (abs($riga->sconto_unitario) > 0) {
            $text = discountInfo($riga);

            echo '
            <br><small class="label label-danger">'.$text.'</small>';
        }

        echo '
        </td>';

        // Iva
        echo '
        <td class="text-right">
            '.moneyFormat($riga->iva_unitaria).'
            <br><small class="'.(($riga->aliquota->deleted_at) ? 'text-red' : '').' help-block">'.$riga->aliquota->descrizione.(($riga->aliquota->esente) ? ' ('.$riga->aliquota->codice_natura_fe.')' : null).'</small>
        </td>';

        // Importo
        echo '
        <td class="text-right">
            '.moneyFormat($riga->importo).'
        </td>';
    }

    // Possibilità di rimuovere una riga solo se la fattura non è pagata
    echo '
        <td class="text-center">';

    if ($record['stato'] != 'Pagato' && $record['stato'] != 'Emessa' && $r['id'] != $fattura->rigaBollo->id) {
        echo "
            <form action='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record."' method='post' id='delete-form-".$r['id']."' role='form'>
                <input type='hidden' name='backto' value='record-edit'>
                <input type='hidden' name='idriga' value='".$r['id']."'>
                <input type='hidden' name='type' value='".get_class($riga)."'>
                <input type='hidden' name='op' value='".$delete."'>";

        if ($riga->isArticolo()) {
            echo "
                <input type='hidden' name='idarticolo' value='".$r['idarticolo']."'>";
        }

        echo "
                <div class='input-group-btn'>";

        if ($riga->isArticolo() && $r['abilita_serial'] && (empty($r['idddt']) || empty($r['idintervento']))) {
            echo "
                    <a class='btn btn-primary btn-xs'data-toggle='tooltip' title='Aggiorna SN...' onclick=\"launch_modal( 'Aggiorna SN', '".$structure->fileurl('add_serial.php').'?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id'].'&idarticolo='.$r['idarticolo']."');\"><i class='fa fa-barcode' aria-hidden='true'></i></a>";
        }

        echo "
                    <a class='btn btn-xs btn-info'  title='".tr('Aggiungi informazioni FE per questa riga...')."' data-toggle='modal' data-title='".tr('Dati Fattura Elettronica')."' data-href='".$structure->fileurl('fe/row-fe.php').'?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id'].'&type='.urlencode(get_class($riga))."'>
                        <i class='fa fa-file-code-o '></i>
                    </a>

                    <a class='btn btn-xs btn-warning' title='".tr('Modifica questa riga...')."' onclick=\"launch_modal( 'Modifica riga', '".$structure->fileurl('row-edit.php').'?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id'].'&type='.urlencode(get_class($riga))."');\">
                        <i class='fa fa-edit'></i>
                    </a>

                    <a class='btn btn-xs btn-danger' title='".tr('Rimuovi questa riga...')."' onclick=\"if( confirm('".tr('Rimuovere questa riga dalla fattura?')."') ){ $('#delete-form-".$r['id']."').submit(); }\">
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

// IMPONIBILE
echo '
    <tr>
        <td colspan="4" class="text-right">
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
        <td colspan="4" class="text-right">
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
        <td colspan="4" class="text-right">
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
        <td colspan="4" class="text-right">';

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
        <td colspan="4" class="text-right">';

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
        <td colspan="4" class="text-right">
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
        <td colspan="4" class="text-right">
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
        <td colspan="4" class="text-right">
            <b>'.tr('Ritenuta contributi', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.moneyFormat(abs($fattura->totale_ritenuta_contributi), 2).'
        </td>
        <td></td>
    </tr>';
}

// NETTO A PAGARE
if ($totale != $netto_a_pagare) {
    echo '
    <tr>
        <td colspan="4" class="text-right">
            <b>'.tr('Netto a pagare', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.moneyFormat($netto_a_pagare, 2).'
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
