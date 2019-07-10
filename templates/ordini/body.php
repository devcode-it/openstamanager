<?php

include_once __DIR__.'/../../core.php';

function findKey($array, $keySearch)
{
    foreach ($array as $key => $item) {
        if ($key == $keySearch) {
            return true;
        } elseif (is_array($item) && findKey($item, $keySearch)) {
            echo $key;

            return true;
        }
    }

    return false;
}

$has_image = false;

// RIGHE ORDINE
$righe = $dbo->fetchArray("SELECT *,
    IFNULL((SELECT `codice` FROM `mg_articoli` WHERE `id` = `or_righe_ordini`.`idarticolo`), '') AS codice_articolo,
	IFNULL((SELECT `immagine` FROM `mg_articoli` WHERE `id` = `or_righe_ordini`.`idarticolo`), '') AS immagine_articolo,
    (SELECT GROUP_CONCAT(`serial` SEPARATOR ', ') FROM `mg_prodotti` WHERE `id_riga_ordine` = `or_righe_ordini`.`id`) AS seriali,
    (SELECT `percentuale` FROM `co_iva` WHERE `id` = `or_righe_ordini`.`idiva`) AS perc_iva
FROM `or_righe_ordini` WHERE idordine=".prepare($id_record).' ORDER BY `order`');

//controllo se gli articoli nell'ordine hanno un'immagine
if (findKey($righe, 'immagine_articolo')) {
    if (!empty($righe[(findKey($righe, 'immagine_articolo') - 1)]['immagine_articolo'])) {
        $has_image = true;
    }
}

$autofill = [
    'count' => 0, // Conteggio delle righe
    'words' => 70, // Numero di parolo dopo cui contare una riga nuova
    'rows' => 20, // Numero di righe massimo presente nella pagina
    'additional' => 15, // Numero di righe massimo da aggiungere
    'columns' => (($has_image) ? 6 : 5), // Numero di colonne della tabella
];

$sconto = [];
$imponibile = [];
$iva = [];

// Intestazione tabella per righe
echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>";

            if ($has_image) {
                echo "		<th class='text-center' style='width:20%'>".tr('Immagine', [], ['upper' => true]).'</th>';
            }
echo "
			<th class='text-center' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Q.tà', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Importo', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('IVA', [], ['upper' => true]).' (%)</th>
        </tr>
    </thead>

    <tbody>';

foreach ($righe as $r) {
    $count = 0;
    $count += ceil(strlen($r['descrizione']) / $autofill['words']);
    $count += substr_count($r['descrizione'], PHP_EOL);

    echo '
        <tr>';

    if ($has_image) {
        echo '
				<td>';
        if (!empty($r['immagine_articolo'])) {
            echo '<img src="files/articoli/'.$r['immagine_articolo'].'" height="80"></img>';
        }
        echo '
				</td>';
    }

    echo '
            <td>';

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
                '.(empty($r['qta']) || empty($r['subtotale']) ? '' : moneyFormat($r['subtotale'] / $r['qta']));

            if ($r['sconto'] > 0) {
                echo "
                <br><small class='text-muted'>- ".tr('sconto _TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($r['sconto_unitario']),
                    '_TYPE_' => ($r['tipo_sconto'] == 'PRC' ? '%' : currency()),
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
                '.(empty($r['subtotale']) ? '' : moneyFormat($r['subtotale']));

            if ($r['sconto'] > 0) {
                echo "
                <br><small class='text-muted'>- ".tr('sconto _TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($r['sconto']),
                    '_TYPE_' => currency(),
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
                '.Translator::numberToLocale($r['perc_iva'], 0);
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
        <td colspan="'.(($has_image) ? 4 : 3).'" class="text-right border-top">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($imponibile, 2).'</b>
        </th>
    </tr>';

    // Eventuale sconto incondizionato
    if (!empty($sconto)) {
        echo '
    <tr>
        <td colspan="'.(($has_image) ? 4 : 3).'" class="text-right border-top">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>-'.moneyFormat($sconto, 2).'</b>
        </th>
    </tr>';

        // Imponibile scontato
        echo '
    <tr>
        <td colspan="'.(($has_image) ? 4 : 3).'" class="text-right border-top">
            <b>'.tr('Imponibile scontato', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($totale, 2).'</b>
        </th>
    </tr>';
    }

    // IVA
    echo '
    <tr>
        <td colspan="'.(($has_image) ? 4 : 3).'" class="text-right border-top">
            <b>'.tr('Totale IVA', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-right">
            <b>'.moneyFormat($iva, 2).'</b>
        </th>
    </tr>';

    $totale = sum($totale, $iva);

    // TOTALE
    echo '
    <tr>
    	<td colspan="'.(($has_image) ? 4 : 3).'" class="text-right border-top">
            <b>'.tr('Quotazione totale', [], ['upper' => true]).':</b>
    	</td>
    	<th colspan="2" class="text-right">
    		<b>'.moneyFormat($totale, 2).'</b>
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
