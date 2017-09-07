<?php

include_once __DIR__.'/../../core.php';

$report_name = 'fattura_'.$numero.'.pdf';

$n_rows = 0;
$words4row = 70;

$v_iva = [];
$v_totale = [];

$totale_documento = 0;
$totale_iva = 0;
$sconto = 0;
$imponibile = 0;
$iva = 0;

// Intestazione tabella per righe
echo "
<table class='table border-full table-striped' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:50%'>".strtoupper(tr('Descrizione'))."</th>
            <th class='text-center' style='width:10%'>".strtoupper(tr('Q.TÀ'))."</th>
            <th class='text-center' style='width:7%'>".strtoupper(tr('Um'))."</th>
            <th class='text-center' style='width:16%'>".strtoupper(tr('Costo unitario'))."</th>
            <th class='text-center' style='width:20%'>".strtoupper(tr('Importo'))."</th>
            <th class='text-center' style='width:7%'>".strtoupper(tr('IVA')).'</th>
        </tr>
    </thead>

    <tbody>';

// RIGHE FATTURA CON ORDINAMENTO UNICO
$righe = $dbo->fetchArray("SELECT *, IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo),'') AS codice_articolo, (SELECT percentuale FROM co_iva WHERE id=idiva) AS perc_iva FROM `co_righe_documenti` WHERE iddocumento=".prepare($iddocumento).' ORDER BY `order`');
$tot_righe = sizeof($righe);

foreach ($righe as $i => $riga) {
    $n_rows += ceil(strlen($riga['descrizione']) / $words4row);

    echo "
        <tr class='".($i % 2 != 0 ? 'bg-default' : '')."'>
            <td class='border-right'>
                ".nl2br($riga['descrizione']);

    if (!empty($riga['codice_articolo'])) {
        echo '
                <br><small>'.str_replace('_COD_', $riga['codice_articolo'], tr('COD. _COD_')).'</small>';
        $n_rows += 0.4;
    }

    // Aggiunta riferimento a ordine
    if (!empty($riga['idordine'])) {
        $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM or_ordini WHERE id='.prepare($riga['idordine']));
        $numero = !empty($rso[0]['numero_esterno']) ? $rso[0]['numero_esterno'] : $rso[0]['numero'];

        echo '
                <br><small>'.str_replace(['_NUM_', '_DATE_'], [$numero, Translator::dateToLocale($rso[0]['data'])], tr('Rif. ordine n<sup>o</sup>_NUM_ del _DATE_')).'</small>';
        $n_rows += 0.4;
    }

    // Aggiunta riferimento a ddt
    elseif (!empty($riga['idddt'])) {
        $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM dt_ddt WHERE id='.prepare($riga['idddt']));
        $numero = !empty($rso[0]['numero_esterno']) ? $rso[0]['numero_esterno'] : $rso[0]['numero'];

        echo '
                <br><small>'.str_replace(['_NUM_', '_DATE_'], [$numero, Translator::dateToLocale($rso[0]['data'])], tr('Rif. ddt n<sup>o</sup>_NUM_ del _DATE_')).'</small>';
        $n_rows += 0.4;
    }
    echo '
            </td>';

    echo "
            <td class='center border-right text-center'>
                ".(empty($riga['qta']) ? '' : Translator::numberToLocale($riga['qta'], 2)).'
            </td>';

    // Unità di miusura
    echo "
            <td class='border-right text-center'>
                ".nl2br(strtoupper($riga['um'])).'
            </td>';

    // Costo unitario
    echo "
            <td class='border-right text-right'>
                ".(empty($riga['qta']) || empty($riga['subtotale']) ? '' : Translator::numberToLocale($riga['subtotale'] / $riga['qta'], 2)).' &euro;
            </td>';

    // Imponibile
    echo "
            <td class='border-right text-right'>
                ".(empty($riga['subtotale']) ? '' : Translator::numberToLocale($riga['subtotale'], 2)).' &euro;';

    if ($riga['sconto'] > 0) {
        $n_rows += 0.4;
        echo "
                <br><small class='help-block'>- sconto ".Translator::numberToLocale($riga['sconto_unitario']).($riga['tipo_sconto'] == 'PRC' ? '%' : ' &euro;').'</small>';
    }

    echo '
            </td>';

    // Iva
    echo "
            <td class='text-center'>";
    if (!empty($riga['idiva'])) {
        echo '
                '.intval($riga['perc_iva']).'%';
    }
    echo '
            </td>
        </tr>';

    $imponibile += $riga['subtotale'];
    $iva += $riga['iva'];
    $sconto += $riga['sconto'];

    $v_iva[$riga['desc_iva']] += $riga['iva'];
    $v_totale[$riga['desc_iva']] += $riga['subtotale'] - $riga['sconto'];
}

$imponibile_documento += $imponibile;
$totale_iva += $iva;
$totale_documento += $imponibile;

// Aggiungo diciture per condizioni iva particolari
if (!empty($v_iva)) {
    $elenco = [
        'Reverse charge ex art. 17, comma 6, DPR 633/72' => tr('Operazione soggetta a reverse charge ex art. 17, comma 6, DPR 633/72'),
        'Esente ex art. 74' => tr('Senza addebito iva ex art. 74 comma 8-9 del DPR 633/72'),
    ];

    $keys = array_keys($v_iva);

    // Controllo se è stata applicata questa tipologia di iva
    foreach ($elenco as $e => $testo) {
        if (in_array($e, $keys)) {
            $n_rows += nl2br($testo) / $words4row;

            echo "
        <tr>
            <td class='border-right text-center'>
                <b>".nl2br($testo)."</b>
            </td>

            <td class='center border-right'></td>
            <td class='center border-right'></td>
            <td class='center border-right'></td>
        </tr>";
        }
    }
}

for ($i = (floor($n_rows) % 20); $i < 15; ++$i) {
    echo '
    <tr>
        <td class="border-right">&nbsp;</td>
        <td class="border-right"></td>
        <td class="border-right"></td>
        <td class="border-right"></td>
        <td class="border-right"></td>
        <td class="border-right"></td>
    </tr>';
}
echo '
    </tbody>
</table>';

$imponibile_documento -= $sconto;
$totale_documento = $totale_documento - $sconto + $totale_iva;

if (!empty($rs[0]['note'])) {
    echo '
<br>
<p class="small-bold">'.strtoupper(tr('Note')).':</p>
<p>'.$rs[0]['note'].'</p>';
}
