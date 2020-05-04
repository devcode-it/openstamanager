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
$righe = $preventivo->getRighe();
foreach ($righe as $riga) {
    echo '
        <tr data-id="'.$riga->id.'">';

    // Descrizione
    $descrizione = nl2br($riga->descrizione);
    if ($riga->isArticolo()) {
        $descrizione = Modules::link('Articoli', $riga->idarticolo, $riga->codice.' - '.$descrizione);
    }
    echo '
            <td>
                '.$descrizione.'
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
            '.numberFormat($riga->qta_rimanente, 'qta').' / '.numberFormat($riga->qta, 'qta').' '.$r['um'].'
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

    // Possibilità di rimuovere una riga solo se il preventivo non è stato pagato
    echo '
            <td class="text-center">';

    if (empty($record['is_completato'])) {
        echo '
                <div class="btn-group">
                    <a class="btn btn-xs btn-warning" onclick="editRow(\''.addslashes(get_class($riga)).'\', '.$riga->id.')">
                        <i class="fa fa-edit"></i>
                    </a>

                    <a class="btn btn-xs btn-danger" onclick="deleteRow(\''.addslashes(get_class($riga)).'\', '.$riga->id.')">
                        <i class="fa fa-trash"></i>
                    </a>
                </div>';
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

echo '
<script>
function editRow(type, id){
    launch_modal("'.tr('Modifica riga').'", "'.$module->fileurl('row-edit.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&idriga=" + id + "&type=" + encodeURIComponent(type));
}

function deleteRow(type, id){
    if(confirm("'.tr('Rimuovere questa riga dal documento?').'")){
        redirect("", {
            backto: "record-edit",
            op: "delete_riga",
            idriga: id,
            type: type,
        }, "post");
    }
}
</script>';

// Calcoli
$imponibile = abs($preventivo->imponibile);
$sconto = $preventivo->sconto;
$totale_imponibile = abs($preventivo->totale_imponibile);
$iva = abs($preventivo->iva);
$totale = abs($preventivo->totale);

// Totale imponibile scontato
echo '
    <tr>
        <td colspan="4"  class="text-right">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($preventivo->imponibile, 2).'
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
        <td class="text-right">
            '.moneyFormat($preventivo->sconto, 2).'
        </td>
        <td></td>
    </tr>';

    // Totale imponibile scontato
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

// Totale iva
echo '
    <tr>
        <td colspan="4"  class="text-right">
            <b>'.tr('Iva', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($preventivo->iva, 2).'
        </td>
        <td></td>
    </tr>';

// Totale
echo '
    <tr>
        <td colspan="4"  class="text-right">
            <b>'.tr('Totale', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($preventivo->totale, 2).'
        </td>
        <td></td>
    </tr>';

// Margine
$margine = $preventivo->margine;
$margine_class = ($margine <= 0 and $preventivo->totale > 0) ? 'danger' : 'success';
$margine_icon = ($margine <= 0 and $preventivo->totale > 0) ? 'warning' : 'check';

echo '
<tr>
    <td colspan="4"  class="text-right">
        '.tr('Costi').':
    </td>
    <td align="right">
        '.moneyFormat($preventivo->spesa).'
    </td>
    <td></td>
</tr>

<tr>
    <td colspan="4"  class="text-right">
        '.tr('Margine (_PRC_%)', [
            '_PRC_' => numberFormat($preventivo->margine_percentuale),
    ]).':
    </td>
    <td align="right" class="'.$margine_class.'">
        <i class="fa fa-'.$margine_icon.' text-'.$margine_class.'"></i> '.moneyFormat($preventivo->margine).'
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
