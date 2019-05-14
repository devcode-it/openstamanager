<?php

include_once __DIR__.'/../../core.php';

/*
ARTICOLI + RIGHE GENERICHE
*/
$q_art = 'SELECT *, round(sconto_unitario,'.setting('Cifre decimali per importi').') AS sconto_unitario, round(sconto,'.setting('Cifre decimali per importi').') AS sconto, round(subtotale,'.setting('Cifre decimali per importi').') AS subtotale, IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo), "") AS codice FROM co_righe_preventivi WHERE idpreventivo='.prepare($id_record).' ORDER BY `order`';
$rs = $dbo->fetchArray($q_art);

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <thead>
		<tr>
			<th>'.tr('Descrizione').'</th>
			<th width="120">'.tr('Q.tà').' <i title="'.tr('da evadere').' / '.tr('totale').'" class="tip fa fa-question-circle-o"></i></th>
			<th width="80">'.tr('U.m.').'</th>
			<th width="160">'.tr('Prezzo unitario').'</th>
			<th width="120">'.tr('Iva').'</th>
			<th width="120">'.tr('Imponibile').'</th>
			<th width="60"></th>
		</tr>
	</thead>
    <tbody class="sortable">';

// se ho almeno un articolo caricato mostro la riga
foreach ($rs as $r) {
    echo '
        <tr data-id="'.$r['id'].'">
            <td>';

    if (!empty($r['idarticolo'])) {
        echo Modules::link('Articoli', $r['idarticolo'], $r['codice'].' - '.$r['descrizione']);
    } else {
        echo nl2br($r['descrizione']);
    }

    echo '
            </td>';

    // q.tà
    echo '
            <td class="text-center">';
    if (empty($r['is_descrizione'])) {
        echo '
                <span >'.Translator::numberToLocale($r['qta'] - $r['qta_evasa'], 'qta').' / '.Translator::numberToLocale($r['qta'], 'qta').'</span>';
    }
    echo '
            </td>';

    // um
    echo '
            <td class="text-center">';
    if (empty($r['is_descrizione'])) {
        echo '
                '.$r['um'];
    }
    echo '
            </td>';

    // prezzo di vendita unitario
    echo '
            <td class="text-right">';
    if (empty($r['is_descrizione'])) {
        echo '
                '.moneyFormat($r['subtotale'] / $r['qta']);

        if ($r['sconto_unitario'] > 0) {
            echo '
                <br><small class="label label-danger">'.tr('sconto _TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($r['sconto_unitario']),
                    '_TYPE_' => ($r['tipo_sconto'] == 'PRC' ? '%' : currency()),
                ]).'</small>';
        }
    }

    echo '
            </td>';

    // iva
    echo '
            <td class="text-right">';
    if (empty($r['is_descrizione'])) {
        echo '
                '.moneyFormat($r['iva']).'
                <br><small class="help-block">'.$r['desc_iva'].'</small>';
    }
    echo'
            </td>';

    // Imponibile
    echo '
            <td class="text-right">';
    if (empty($r['is_descrizione'])) {
        echo '
                '.moneyFormat($r['subtotale'] - $r['sconto']);
    }

    // Possibilità di rimuovere una riga solo se il preventivo non è stato pagato
    echo '
            <td class="text-center">';

    if ($record['stato'] != 'Pagato') {
        echo "
                <form action='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record."' method='post' id='delete-form-".$r['id']."' role='form'>
                    <input type='hidden' name='backto' value='record-edit'>
                    <input type='hidden' name='op' value='unlink_articolo'>
                    <input type='hidden' name='idriga' value='".$r['id']."'>
                    <input type='hidden' name='idarticolo' value='".$r['idarticolo']."'>

                    <div class='btn-group'>
                        <a class='btn btn-xs btn-warning' title='Modifica riga' onclick=\"launch_modal( 'Modifica riga', '".$rootdir.'/modules/preventivi/row-edit.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id']."', 1 );\"><i class='fa fa-edit'></i></a>

                        <a href='javascript:;' class='btn btn-xs btn-danger' title='Rimuovi questa riga' onclick=\"if( confirm('Rimuovere questa riga dal preventivo?') ){ $('#delete-form-".$r['id']."').submit(); }\"><i class='fa fa-trash'></i></a>
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

// Calcoli
$totale_acquisto = 0;
foreach ($rs as $r) {
    $totale_acquisto += ($r['prezzo_unitario_acquisto'] * $r['qta']);
}
$imponibile = sum(array_column($rs, 'subtotale'));
$sconto = sum(array_column($rs, 'sconto'));
$iva = sum(array_column($rs, 'iva'));

$imponibile_scontato = sum($imponibile, -$sconto);

$totale = sum([
    $imponibile_scontato,
    $iva,
]);
$totale_guadagno = sum([
    $imponibile_scontato
    - $totale_acquisto,
]);

echo '
    </tbody>';

// SCONTO
if (abs($sconto) > 0) {
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

    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.moneyFormat($sconto, 2).'
        </td>
        <td></td>
    </tr>';

    // Totale imponibile
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('Imponibile scontato', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.moneyFormat($imponibile_scontato, 2).'
        </td>
        <td></td>
    </tr>';
} else {
    // Totale imponibile
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
}

// Totale iva
echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('IVA', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.moneyFormat($iva, 2).'
        </td>
        <td></td>
    </tr>';

// Totale preventivo
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
