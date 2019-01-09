<?php

include_once __DIR__.'/../../core.php';

$report_name = 'ordine_'.$numero_ord.'.pdf';

$autofill = [
    'count' => 0, // Conteggio delle righe
    'words' => 70, // Numero di parolo dopo cui contare una riga nuova
    'rows' => 20, // Numero di righe massimo presente nella pagina
    'additional' => 15, // Numero di righe massimo da aggiungere
    'columns' => 5, // Numero di colonne della tabella
];

$sconto = [];
$imponibile = [];
$iva = [];

// Intestazione tabella per righe
echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Q.tà', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Importo', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('IVA', [], ['upper' => true]).' (%)</th>
        </tr>
    </thead>

    <tbody>';

// RIGHE PREVENTIVO CON ORDINAMENTO UNICO
$righe = $dbo->fetchArray("SELECT *,
    IFNULL((SELECT `codice` FROM `mg_articoli` WHERE `id` = `or_righe_ordini`.`idarticolo`), '') AS codice_articolo,
	IFNULL((SELECT `immagine` FROM `mg_articoli` WHERE `id` = `or_righe_ordini`.`idarticolo`), '') AS immagine_articolo,
    (SELECT GROUP_CONCAT(`serial` SEPARATOR ', ') FROM `mg_prodotti` WHERE `id_riga_ordine` = `or_righe_ordini`.`id`) AS seriali,
    (SELECT `percentuale` FROM `co_iva` WHERE `id` = `or_righe_ordini`.`idiva`) AS perc_iva
FROM `or_righe_ordini` WHERE idordine=".prepare($id_record).' ORDER BY `order`');
foreach ($righe as $r) {
    $count = 0;
    $count += ceil(strlen($r['descrizione']) / $autofill['words']);
    $count += substr_count($r['descrizione'], PHP_EOL);

    echo '
        <tr>
            <td>';
			
	if (!empty($r['immagine_articolo'])) {
		//echo '<img src="files/articoli/'.$r['immagine_articolo'].'" height="120"></img><div class="clearfix" ></div>';
	}
	
	echo '
                '.nl2br($r['descrizione']);

    // Codice articolo
    if (!empty($r['codice_articolo'])) {
        echo '
                <br><small>'.tr('COD. _COD_', [
                    '_COD_' => $r['codice_articolo'],
                ]).'</small>';

        if ($count <= 1) {
            $count += 0.4;
        }
    }

    // Seriali
    if (!empty($r['seriali'])) {
        echo '
                <br><small>'.tr('SN').': '.$r['seriali'].'</small>';

        if ($count <= 1) {
            $count += 0.4;
        }
    }

    echo '
            </td>';

    echo "
            <td class='text-center'>";
    if (empty($r['is_descrizione'])) {
        echo '
                '.(empty($r['qta']) ? '' : Translator::numberToLocale($r['qta'], 'qta')).' '.$r['um'];
    }
    echo '
            </td>';

    if ($options['pricing']) {
        // Prezzo unitario
        echo "
            <td class='text-right'>";
        if (empty($r['is_descrizione'])) {
            echo '
                '.(empty($r['qta']) || empty($r['subtotale']) ? '' : Translator::numberToLocale($r['subtotale'] / $r['qta'])).' &euro;';

            if ($r['sconto'] > 0) {
                echo "
                <br><small class='text-muted'>- ".tr('sconto _TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($r['sconto_unitario']),
                    '_TYPE_' => ($r['tipo_sconto'] == 'PRC' ? '%' : '&euro;'),
                ]).'</small>';

                if ($count <= 1) {
                    $count += 0.4;
                }
            }
        }
        echo '
            </td>';

        // Imponibile
        echo "
            <td class='text-right'>";
        if (empty($r['is_descrizione'])) {
            echo '
                '.(empty($r['subtotale']) ? '' : Translator::numberToLocale($r['subtotale'])).' &euro;';

            if ($r['sconto'] > 0) {
                echo "
                <br><small class='text-muted'>- ".tr('sconto _TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($r['sconto']),
                    '_TYPE_' => '&euro;',
                ]).'</small>';

                if ($count <= 1) {
                    $count += 0.4;
                }
            }
        }
        echo '
            </td>';
    } else {
        echo '
            <td class="text-center">-</td>
            <td class="text-center">-</td>';
    }

    // Iva
    echo '
            <td class="text-center">';
    if (empty($r['is_descrizione'])) {
        echo '
                '.Translator::numberToLocale($r['perc_iva']);
    }
    echo '
            </td>
        </tr>';

    $autofill['count'] += $count;

    $sconto[] = $r['sconto'];
    $imponibile[] = $r['subtotale'];
    $iva[] = $r['iva'];
}

$sconto = sum($sconto);
$imponibile = sum($imponibile);
$iva = sum($iva);

$totale = $imponibile - $sconto;

echo '
        |autofill|
    </tbody>';

// TOTALE COSTI FINALI
if ($options['pricing']) {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($imponibile).' &euro;</b>
        </th>
    </tr>';

    // Eventuale sconto incondizionato
    if (!empty($sconto)) {
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-center">
            <b>-'.Translator::numberToLocale($sconto).' &euro;</b>
        </th>
    </tr>';

        // Imponibile scontato
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Imponibile scontato', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($totale).' &euro;</b>
        </th>
    </tr>';
    }

    // IVA
    echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Totale IVA', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($iva).' &euro;</b>
        </th>
    </tr>';

    $totale = sum($totale, $iva);

    // TOTALE
    echo '
    <tr>
    	<td colspan="3" class="text-right border-top">
            <b>'.tr('Quotazione totale', [], ['upper' => true]).':</b>
    	</td>
    	<th colspan="2" class="text-center">
    		<b>'.Translator::numberToLocale($totale).' &euro;</b>
    	</th>
    </tr>';
}

echo '
</table>';

if (!empty($records[0]['note'])) {
    echo '
<br>
<p class="small-bold">'.tr('Note', [], ['upper' => true]).':</p>
<p>'.nl2br($records[0]['note']).'</p>';
}
