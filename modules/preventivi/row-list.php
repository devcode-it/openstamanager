<?php

include_once __DIR__.'/../../core.php';

/*
ARTICOLI + RIGHE GENERICHE
*/
$q_art = 'SELECT *, round(sconto_unitario,'.Settings::get('Cifre decimali per importi').') AS sconto_unitario, round(sconto,'.Settings::get('Cifre decimali per importi').') AS sconto, round(subtotale,'.Settings::get('Cifre decimali per importi').') AS subtotale,  IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo), "") AS codice FROM co_righe_preventivi WHERE idpreventivo='.prepare($id_record).' ORDER BY `order`';
$rs = $dbo->fetchArray($q_art);

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
            <td class="text-right">';
    if (empty($r['is_descrizione'])) {
        echo '
                '.Translator::numberToLocale($r['qta'], 'qta');
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

    // costo unitario
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

    // iva
    echo '
            <td class="text-right">';
    if (empty($r['is_descrizione'])) {
        echo '
                '.Translator::numberToLocale($r['iva']).' &euro;
                <br><small class="help-block">'.$r['desc_iva'].'</small>';
    }
    echo'
            </td>';

    // Imponibile
    echo '
            <td class="text-right">';
    if (empty($r['is_descrizione'])) {
        echo '
                '.Translator::numberToLocale($r['subtotale'] - $r['sconto']).' &euro;';
    }
    echo'
            </td>';

    // Possibilità di rimuovere una riga solo se il preventivo non è stato pagato
    echo '
            <td class="text-center">';

    if ($records[0]['stato'] != 'Pagato' && empty($r['sconto_globale'])) {
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

// Calcoli
$imponibile = sum(array_column($rs, 'subtotale'));
$sconto = sum(array_column($rs, 'sconto'));
$iva = sum(array_column($rs, 'iva'));

$imponibile_scontato = sum($imponibile, -$sconto);

$totale = sum([
    $imponibile_scontato,
    $iva,
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
            '.Translator::numberToLocale($imponibile).' &euro;
        </td>
        <td></td>
    </tr>';

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

    // Totale imponibile
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
} else {
    // Totale imponibile
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
}

// Totale iva
echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.tr('IVA', [], ['upper' => true]).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($iva).' &euro;
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
            '.Translator::numberToLocale($totale).' &euro;
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
