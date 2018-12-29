<?php

include_once __DIR__ . '/../../core.php';

/*
ARTICOLI + RIGHE GENERICHE
*/
$q_art = 'SELECT *, round(sconto_unitario,' . setting('Cifre decimali per importi') . ') AS sconto_unitario, round(sconto,' . setting('Cifre decimali per importi') . ') AS sconto, round(subtotale,' . setting('Cifre decimali per importi') . ') AS subtotale, IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo), "") AS codice FROM co_righe_preventivi WHERE idpreventivo=' . prepare($id_record) . ' ORDER BY `order`';
$rs = $dbo->fetchArray($q_art);

echo '
<script>
// Verifica se il guadagno è negativo quando si esce dalla pagina e lancia un avviso
    function controlla_guadagno(id_riga) {
        var guadagno;
        if (id_riga === "tot") {
            guadagno = $("#guadagno_totale");
        } else {
            guadagno = $("tr[data-id=\'" + id_riga + "\'] > td[id=\'guadagno_riga\']");
        }
        if (guadagno.length && parseFloat(guadagno.text().replace(".", "").replace(",", ".")) < 0) {
            guadagno.css("border", "2px solid red");
            guadagno.css("background", "#ff00001a");
            var alert = $("#avviso_guadagno_negativo");
            if (alert.length === 0 || alert.text() !== " ' . tr('Attenzione! Il guadagno è negativo!') . '")
            document.write("<div class=\'alert alert-warning push\' style=\'text-align: center\' id=\'avviso_guadagno_negativo\'>" +
             "<i class=\'fa fa-exclamation-triangle\'></i> ' . tr('Attenzione! Il guadagno è negativo!') . '</div>")
        }
    }
</script>
<table class="table table-striped table-hover table-condensed table-bordered">
    <thead>
		<tr>
<<<<<<< HEAD
			<th>' . tr('Descrizione') . '</th>
			<th width="120">' . tr('Q.tà') . '</th>
			<th width="80">' . tr('U.m.') . '</th>
			<th width="120">' . tr('Prezzo di acquisto unitario') . '</th>
			<th width="120">' . tr('Costo unitario') . '</th>
			<th width="120">' . tr('Guadagno') . '</th>
			<th width="120">' . tr('Iva') . '</th>
			<th width="120">' . tr('Imponibile') . '</th>
=======
			<th>'.tr('Descrizione').'</th>
			<th width="120">'.tr('Q.tà').'</th>
			<th width="80">'.tr('U.m.').'</th>
			<th width="150">'.tr('Prezzo acq. unitario').'</th>
			<th width="160">'.tr('Prezzo vend. unitario').'</th>
			<th width="120">'.tr('Iva').'</th>
			<th width="120">'.tr('Imponibile').'</th>
			<th width="120">'.tr('Guadagno').'</th>
>>>>>>> 5987bd43fd89765b2b890a82b0f7d3d81bfe7ab7
			<th width="60"></th>
		</tr>
	</thead>
    <tbody class="sortable">';

// se ho almeno un articolo caricato mostro la riga
foreach ($rs as $r) {
    echo '
        <tr data-id="' . $r['id'] . '">
            <td>';

    if (!empty($r['idarticolo'])) {
        echo Modules::link('Articoli', $r['idarticolo'], $r['codice'] . ' - ' . $r['descrizione']);
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
                ' . Translator::numberToLocale($r['qta'], 'qta');
    }
    echo '
            </td>';

    // um
    echo '
            <td class="text-center">';
    if (empty($r['is_descrizione'])) {
        echo '
                ' . $r['um'];
    }
    echo '
            </td>';

    // Prezzo di acquisto unitario
<<<<<<< HEAD
    $subtotale_acquisto = $r['subtotale_acquisto'] / $r['qta'];
=======
>>>>>>> 5987bd43fd89765b2b890a82b0f7d3d81bfe7ab7
    echo '
        <td class="text-right">';

    if (empty($r['is_descrizione'])) {
        echo '
<<<<<<< HEAD
            ' . Translator::numberToLocale($subtotale_acquisto) . ' &euro;';
    }

    echo '
        </td>';

    // costo unitario
=======
            '.Translator::numberToLocale($r['prezzo_unitario_acquisto']).' &euro;';
    }

    // prezzo di vendita unitario
>>>>>>> 5987bd43fd89765b2b890a82b0f7d3d81bfe7ab7
    echo '
            <td class="text-right">';
    if (empty($r['is_descrizione'])) {
        echo '
                ' . Translator::numberToLocale($r['subtotale'] / $r['qta']) . ' &euro;';

        if ($r['sconto_unitario'] > 0) {
            echo '
                <br><small class="label label-danger">' . tr('sconto _TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($r['sconto_unitario']),
                    '_TYPE_' => ($r['tipo_sconto'] == 'PRC' ? '%' : '&euro;'),
                ]) . '</small>';
        }
    }

    echo '
            </td>';

    // Guadagno
    echo '
        <td class="text-right" id="guadagno_riga">';

    if (empty($r['is_descrizione'])) {
        echo '
            ' . Translator::numberToLocale($r["subtotale"] - $subtotale_acquisto - $r["sconto"]) . ' &euro;';
    }

    echo '</td>
    <script>
    controlla_guadagno(' . $r["id"] . ')
    </script>';

    // iva
    echo '
            <td class="text-right">';
    if (empty($r['is_descrizione'])) {
        echo '
                ' . Translator::numberToLocale($r['iva']) . ' &euro;
                <br><small class="help-block">' . $r['desc_iva'] . '</small>';
    }
    echo '
            </td>';

    // Imponibile
    echo '
            <td class="text-right">';
    if (empty($r['is_descrizione'])) {
        echo '
                ' . Translator::numberToLocale($r['subtotale'] - $r['sconto']) . ' &euro;';
    }
<<<<<<< HEAD
    echo '
            </td>';
=======

    // Guadagno
    $guadagno = $r['subtotale'] - ($r['prezzo_unitario_acquisto'] * $r["qta"]) - ($r["sconto_unitario"] * $r["qta"]);
    if ($guadagno < 0) {
        $guadagno_style = "background-color: #FFC6C6; border: 3px solid red";
    } else {
        $guadagno_style = "";
    }
    echo '
        <td class="text-right" style="' . $guadagno_style . '">';
    if (empty($r['is_descrizione'])) {
        echo '
            '.Translator::numberToLocale($guadagno).' &euro;';
    }
    echo '
        </td>';
>>>>>>> 5987bd43fd89765b2b890a82b0f7d3d81bfe7ab7

    // Possibilità di rimuovere una riga solo se il preventivo non è stato pagato
    echo '
            <td class="text-center">';

    if ($record['stato'] != 'Pagato' && empty($r['sconto_globale'])) {
        echo "
                <form action='" . $rootdir . '/editor.php?id_module=' . $id_module . '&id_record=' . $id_record . "' method='post' id='delete-form-" . $r['id'] . "' role='form'>
                    <input type='hidden' name='backto' value='record-edit'>
                    <input type='hidden' name='op' value='unlink_articolo'>
                    <input type='hidden' name='idriga' value='" . $r['id'] . "'>
                    <input type='hidden' name='idarticolo' value='" . $r['idarticolo'] . "'>

                    <div class='btn-group'>
                        <a class='btn btn-xs btn-warning' title='Modifica riga' onclick=\"launch_modal( 'Modifica riga', '" . $rootdir . '/modules/preventivi/row-edit.php?id_module=' . $id_module . '&id_record=' . $id_record . '&idriga=' . $r['id'] . "', 1 );\"><i class='fa fa-edit'></i></a>

                        <a href='javascript:;' class='btn btn-xs btn-danger' title='Rimuovi questa riga' onclick=\"if( confirm('Rimuovere questa riga dal preventivo?') ){ $('#delete-form-" . $r['id'] . "').submit(); }\"><i class='fa fa-trash'></i></a>
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
$totale_acquisto = 0;
foreach ($rs as $r) {
    $totale_acquisto += ($r["prezzo_unitario_acquisto"] * $r["qta"]);
}
$imponibile = sum(array_column($rs, 'subtotale'));
$sconto = sum(array_column($rs, 'sconto'));
$iva = sum(array_column($rs, 'iva'));
$guadagno = sum(array_column($rs, 'guadagno'));

$imponibile_scontato = sum($imponibile, -$sconto);

$totale = sum([
    $imponibile_scontato,
    $iva,
]);
$totale_guadagno = sum([
    $imponibile_scontato
    -$totale_acquisto
]);


$guadagno_totale = sum([
    $guadagno
    -$sconto
]);

echo '
    </tbody>';

// SCONTO
if (abs($sconto) > 0) {
    echo '
    <tr>
        <td colspan="7" class="text-right">
<<<<<<< HEAD
            <b>' . tr('Imponibile', [], ['upper' => true]) . ':</b>
=======
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
>>>>>>> 5987bd43fd89765b2b890a82b0f7d3d81bfe7ab7
        </td>
        <td align="right">
            ' . Translator::numberToLocale($imponibile) . ' &euro;
        </td>
        <td></td>
    </tr>';

    echo '
    <tr>
        <td colspan="7" class="text-right">
<<<<<<< HEAD
            <b>' . tr('Sconto', [], ['upper' => true]) . ':</b>
=======
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
>>>>>>> 5987bd43fd89765b2b890a82b0f7d3d81bfe7ab7
        </td>
        <td align="right">
            ' . Translator::numberToLocale($sconto) . ' &euro;
        </td>
        <td></td>
    </tr>';

    // Totale imponibile
    echo '
    <tr>
        <td colspan="7" class="text-right">
<<<<<<< HEAD
            <b>' . tr('Imponibile scontato', [], ['upper' => true]) . ':</b>
=======
            <b>'.tr('Imponibile scontato', [], ['upper' => true]).':</b>
>>>>>>> 5987bd43fd89765b2b890a82b0f7d3d81bfe7ab7
        </td>
        <td align="right">
            ' . Translator::numberToLocale($imponibile_scontato) . ' &euro;
        </td>
        <td></td>
    </tr>';
} else {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="7" class="text-right">
<<<<<<< HEAD
            <b>' . tr('Imponibile', [], ['upper' => true]) . ':</b>
=======
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
>>>>>>> 5987bd43fd89765b2b890a82b0f7d3d81bfe7ab7
        </td>
        <td align="right">
            ' . Translator::numberToLocale($imponibile) . ' &euro;
        </td>
        <td></td>
    </tr>';
}

// Totale iva
echo '
    <tr>
        <td colspan="7" class="text-right">
<<<<<<< HEAD
            <b>' . tr('IVA', [], ['upper' => true]) . ':</b>
=======
            <b>'.tr('IVA', [], ['upper' => true]).':</b>
>>>>>>> 5987bd43fd89765b2b890a82b0f7d3d81bfe7ab7
        </td>
        <td align="right">
            ' . Translator::numberToLocale($iva) . ' &euro;
        </td>
        <td></td>
    </tr>';

// Totale preventivo
echo '
    <tr>
        <td colspan="7" class="text-right">
<<<<<<< HEAD
            <b>' . tr('Totale', [], ['upper' => true]) . ':</b>
=======
            <b>'.tr('Totale', [], ['upper' => true]).':</b>
>>>>>>> 5987bd43fd89765b2b890a82b0f7d3d81bfe7ab7
        </td>
        <td align="right">
            ' . Translator::numberToLocale($totale) . ' &euro;
        </td>
        <td></td>
    </tr>';

<<<<<<< HEAD
// Guadagno totale
=======
// GUADAGNO TOTALE
if ($totale_guadagno < 0) {
    $guadagno_style = "background-color: #FFC6C6; border: 3px solid red";
} else {
    $guadagno_style = "";
}
echo '
    <tr>
        <td colspan="7" class="text-right">
            <b>'.tr('Guadagno totale', [], ['upper' => true]).':</b>
        </td>
        <td align="right" style="' . $guadagno_style . '">
            '.Translator::numberToLocale($totale_guadagno).' &euro;
        </td>
        <td></td>
    </tr>';

>>>>>>> 5987bd43fd89765b2b890a82b0f7d3d81bfe7ab7
echo '
    <tr>
        <td colspan="7" class="text-right" id="guadagno_text">
            <b>' . tr('Guadagno', [], ['upper' => true]) . ':</b>
        </td>
        <td align="right" id="guadagno_totale">
            ' . Translator::numberToLocale($guadagno_totale) . ' &euro;
        </td>
        <td></td>
    </tr>';

echo '
</table>
<script>

controlla_guadagno("tot");

$(document).ready(function(){
	$(".sortable").each(function() {
        $(this).sortable({
            axis: "y",
            handle: ".handle",
			cursor: "move",
			dropOnEmpty: true,
			scroll: true,
			update: function(event, ui) {
<<<<<<< HEAD
				$.post("' . $rootdir . '/actions.php", {
=======
                var order = "";
                $(".table tr[data-id]").each( function(){
                    order += ","+$(this).data("id");
                });
                order = order.replace(/^,/, "");
                
				$.post("'.$rootdir.'/actions.php", {
>>>>>>> 2ae57384089d87555550bf51f8419fa60ad26f2b
					id: ui.item.data("id"),
					id_module: ' . $id_module . ',
					id_record: ' . $id_record . ',
					op: "update_position",
                    order: order,
				});
			}
		});
	});
});
</script>';
