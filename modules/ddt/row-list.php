<?php

include_once __DIR__.'/../../core.php';

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <thead>
        <tr>
            <th>'.tr('Descrizione').'</th>
			<th class="text-center tip" width="150" title="'.tr('da evadere').' / '.tr('totale').'">'.tr('Q.tà').' <i class="fa fa-question-circle-o"></i></th>
            <th class="text-center" width="150">'.tr('Prezzo unitario').'</th>
            <th class="text-center" width="150">'.tr('Iva unitaria').'</th>
            <th class="text-center" width="150">'.tr('Importo').'</th>
            <th width="60"></th>
        </tr>
    </thead>

    <tbody class="sortable">';

// Righe documento
$righe = $ddt->getRighe();
foreach ($righe as $riga) {
    $extra = '';
    $mancanti = 0;

    // Individuazione dei seriali
    if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
        $serials = $riga->serials;
        $mancanti = abs($riga->qta) - count($serials);

        if ($mancanti > 0) {
            $extra = 'class="warning"';
        } else {
            $mancanti = 0;
        }
    }

    echo '
    <tr data-id="'.$riga->id.'" '.$extra.'>
        <td>';
    if ($riga->isArticolo()) {
        echo '
            '.Modules::link('Articoli', $riga->idarticolo, $riga->articolo->codice.' - '.$riga->descrizione);
    } else {
        echo nl2br($riga->descrizione);
    }

    if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
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
    if ($riga->hasOriginal()) {
        echo '
            <br>'.reference($riga->getOriginal()->parent);
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
            '.numberFormat($riga->qta_rimanente, 'qta').' / '.numberFormat($riga->qta, 'qta').' '.$riga->um.'
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

    // Possibilità di rimuovere una riga solo se il ddt non è evaso
    echo '
        <td class="text-center">';

    if ($record['flag_completato'] == 0) {
        echo "
            <form action='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record."' method='post' id='delete-form-".$riga->id."' role='form'>
                <input type='hidden' name='backto' value='record-edit'>
                <input type='hidden' name='id_record' value='".$id_record."'>
                <input type='hidden' name='idriga' value='".$riga->id."'>
                <input type='hidden' name='type' value='".get_class($riga)."'>
                <input type='hidden' name='op' value='delete_riga'>

                <div class='input-group-btn'>";

        if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
            echo "
                    <a class='btn btn-primary btn-xs'data-toggle='tooltip' title='Aggiorna SN...' onclick=\"launch_modal( 'Aggiorna SN', '".$rootdir.'/modules/fatture/add_serial.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$riga->id.'&idarticolo='.$riga->idarticolo."');\"><i class='fa fa-barcode' aria-hidden='true'></i></a>";
        }

        echo "
                    <a class='btn btn-xs btn-warning' title='Modifica questa riga...' onclick=\"launch_modal('Modifica riga', '".$rootdir.'/modules/ddt/row-edit.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$riga->id.'&type='.urlencode(get_class($riga))."');\">
                        <i class='fa fa-edit'></i>
                    </a>

                    <a class='btn btn-xs btn-danger' title='Rimuovi questa riga...' onclick=\"if( confirm('Rimuovere questa riga dal ddt?') ){ $('#delete-form-".$riga->id."').submit(); }\">
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

// Calcoli
$imponibile = abs($ddt->imponibile);
$sconto = $ddt->sconto;
$totale_imponibile = abs($ddt->totale_imponibile);
$iva = abs($ddt->iva);
$totale = abs($ddt->totale);

// IMPONIBILE
echo '
    <tr>
        <td colspan="4"  class="text-right">
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
        <td colspan="4"  class="text-right">
            <b><span class="tip" title="'.tr('Un importo positivo indica uno sconto, mentre uno negativo indica una maggiorazione').'"> <i class="fa fa-question-circle-o"></i> '.tr('Sconto/maggiorazione', [], ['upper' => true]).':</span></b>
        </td>

        <td align="right">
            '.moneyFormat($sconto, 2).'
        </td>

        <td></td>
    </tr>';

    // TOTALE IMPONIBILE
    echo '
    <tr>
        <td colspan="4"  class="text-right">
            <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
        </td>

        <td align="right">
            '.moneyFormat($totale_imponibile, 2).'
        </td>

        <td></td>
    </tr>';
}

// IVA
echo '
    <tr>
        <td colspan="4"  class="text-right">
            <b>'.tr('IVA', [], ['upper' => true]).':</b>
        </td>

        <td align="right">
            '.moneyFormat($iva, 2).'
        </td>

        <td></td>
    </tr>';

// TOTALE
echo '
    <tr>
        <td colspan="4"  class="text-right">
            <b>'.tr('Totale', [], ['upper' => true]).':</b>
        </td>

        <td align="right">
            '.moneyFormat($totale, 2).'
        </td>

        <td></td>
    </tr>';

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
