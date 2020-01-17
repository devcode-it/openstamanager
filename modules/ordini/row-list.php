<?php

include_once __DIR__.'/../../core.php';

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <thead>
		<tr>
			<th>'.tr('Descrizione').'</th>
			<th width="120">'.tr('Q.tà').' <i title="'.tr('da evadere').' / '.tr('totale').'" class="tip fa fa-question-circle-o"></i></th>
			<th width="80">'.tr('U.m.').'</th>
			<th width="120">'.tr('Costo unitario').'</th>
			<th width="120">'.tr('Iva').'</th>
			<th width="120">'.tr('Imponibile').'</th>
			<th width="60"></th>
		</tr>
	</thead>

    <tbody class="sortable">';

// Righe documento
$righe = $ordine->getRighe();
foreach ($righe as $riga) {
    $r = $riga->toArray();

    $extra = '';
    $mancanti = 0;

    // Individuazione dei seriali
    if (!empty($r['idarticolo']) && !empty($r['abilita_serial'])) {
        $serials = array_column($dbo->fetchArray('SELECT serial FROM mg_prodotti WHERE serial IS NOT NULL AND id_riga_ordine='.prepare($r['id'])), 'serial');
            $mancanti = $r['qta'] - count($serials);

            if ($mancanti > 0) {
                $extra = 'class="warning"';
            } else {
                $mancanti = 0;
            }
        }

        echo '
    <tr data-id="'.$r['id'].'" '.$extra.'>
        <td align="left">';

        if (!empty($r['idarticolo'])) {
            echo '
            '.Modules::link('Articoli', $r['idarticolo'], $riga->articolo->codice.' - '.$r['descrizione']);

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
        } else {
            echo nl2br($r['descrizione']);
        }

        // Aggiunta dei riferimenti ai documenti
        $ref = doc_references($r, $dir, ['idordine']);

        if (!empty($ref)) {
            echo '
            <br>'.Modules::link($ref['module'], $ref['id'], $ref['description'], $ref['description']);
        }

        echo '
        </td>';

        echo '
        <td class="text-center">';
        if (empty($r['is_descrizione'])) {
            echo '
                <span >'.Translator::numberToLocale($r['qta'] - $r['qta_evasa'], 'qta').' / '.Translator::numberToLocale($r['qta'], 'qta').'</span>';
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
            '.moneyFormat($r['subtotale'] / $r['qta']);

            if (abs($r['sconto_unitario']) > 0) {
                $text = $r['sconto_unitario'] > 0 ? tr('sconto _TOT_ _TYPE_') : tr('maggiorazione _TOT_ _TYPE_');

                echo '
            <br><small class="label label-danger">'.replace($text, [
                '_TOT_' => Translator::numberToLocale(abs($r['sconto_unitario'])),
                '_TYPE_' => ($r['tipo_sconto'] == 'PRC' ? '%' : currency()),
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
            '.moneyFormat($r['iva']).'
            <br><small class="help-block">'.$r['desc_iva'].'</small>';
        }
        echo '
        </td>';

        // Imponibile
        echo '
        <td class="text-right">';
        if (empty($r['is_descrizione'])) {
            echo '
            '.moneyFormat($r['subtotale'] - $r['sconto']);
        }
        echo '
        </td>';

        // Possibilità di rimuovere una riga solo se l'ordine non è evaso
        echo '
        <td class="text-center">';

        if ($record['flag_completato'] == 0) {
            echo "
            <form action='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record."' method='post' id='delete-form-".$r['id']."' role='form'>
                <input type='hidden' name='backto' value='record-edit'>
                <input type='hidden' name='id_record' value='".$id_record."'>
                <input type='hidden' name='idriga' value='".$r['id']."'>
                <input type='hidden' name='type' value='".get_class($riga)."'>
                <input type='hidden' name='op' value='delete_riga'>";

            echo "

                <div class='input-group-btn'>";

            if (!empty($r['idarticolo']) && $r['abilita_serial']) {
                echo "
                    <a class='btn btn-primary btn-xs'data-toggle='tooltip' title='Aggiorna SN...' onclick=\"launch_modal( 'Aggiorna SN', '".$rootdir.'/modules/fatture/add_serial.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id'].'&idarticolo='.$r['idarticolo']."');\"><i class='fa fa-barcode' aria-hidden='true'></i></a>";
            }

            echo "
                    <a class='btn btn-xs btn-warning' title='Modifica questa riga...' onclick=\"launch_modal( 'Modifica riga', '".$rootdir.'/modules/ordini/row-edit.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id'].'&type='.urlencode(get_class($riga))."');\"><i class='fa fa-edit'></i></a>

                    <a class='btn btn-xs btn-danger' title='Rimuovi questa riga...' onclick=\"if( confirm('Rimuovere questa riga dall\\'ordine?') ){ $('#delete-form-".$r['id']."').submit(); }\"><i class='fa fa-trash'></i></a>
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
$imponibile = abs($ordine->imponibile);
$sconto = $ordine->sconto;
$totale_imponibile = abs($ordine->totale_imponibile);
$iva = abs($ordine->iva);
$totale = abs($ordine->totale);

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
        <td colspan="5" class="text-right">
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
        <td colspan="5" class="text-right">
            <b>'.tr('Iva', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.moneyFormat($iva, 2).'
        </td>
        <td></td>
    </tr>';

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
