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
$righe = $contratto->getRighe();
foreach ($righe as $riga) {
    echo '
        <tr data-id="'.$riga->id.'">';

    // Descrizione
    $descrizione = nl2br($riga->descrizione);
    if ($riga->isArticolo()) {
        $descrizione = Modules::link('Articoli', $riga->idarticolo, $riga->articolo->codice.' - '.$descrizione);
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
            <td></td>
            <td></td>';
    } else {
        // Q.tà
        echo '
            <td class="text-center">
                '.Translator::numberToLocale($riga->qta_rimanente, 'qta').' / '.Translator::numberToLocale($riga->qta, 'qta').'
            </td>';

        // Unità di misura
        echo '
            <td class="text-center">
                '.$riga->um.'
            </td>';

        // Costo unitario
        echo '
            <td class="text-right">
                '.moneyFormat($riga->prezzo_unitario_vendita);

        if (abs($riga->sconto_unitario) > 0) {
            $text = $riga->sconto_unitario > 0 ? tr('sconto _TOT_ _TYPE_') : tr('maggiorazione _TOT_ _TYPE_');

            echo '
                <br><small class="label label-danger">'.replace($text, [
                    '_TOT_' => Translator::numberToLocale(abs($riga->sconto_unitario)),
                    '_TYPE_' => ($riga->tipo_sconto == 'PRC' ? '%' : currency()),
                ]).'</small>';
        }

        echo'
            </td>';

        // IVA
        echo '
            <td class="text-right">
                '.moneyFormat($riga->iva).'<br>
                <small class="help-block">'.$riga->aliquota->descrizione.(($riga->aliquota->esente) ? ' ('.$riga->aliquota->codice_natura_fe.')' : null).'</small>
            </td>';

        // Imponibile
        echo '
            <td class="text-right">
            '.moneyFormat($riga->totale_imponibile).'
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

echo '
    </tbody>';

// Calcoli
$imponibile = abs($contratto->imponibile);
$sconto = $contratto->sconto;
$totale_imponibile = abs($contratto->totale_imponibile);
$iva = abs($contratto->iva);
$totale = abs($contratto->totale);

// Totale totale imponibile
echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($contratto->imponibile, 2).'
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
        <td class="text-right">
            '.moneyFormat($contratto->sconto, 2).'
        </td>
        <td></td>
    </tr>';

    // Totale totale imponibile
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($totale_imponibile, 2).'
        </td>
        <td></td>
    </tr>';
}

// Totale iva
echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Iva', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($contratto->iva, 2).'
        </td>
        <td></td>
    </tr>';

// Totale contratto
echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Totale', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($contratto->totale, 2).'
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
