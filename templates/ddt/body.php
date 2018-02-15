<?php

include_once __DIR__.'/../../core.php';

$report_name = 'ddt_'.$numero.'.pdf';

$autofill = [
    'count' => 0,
    'words' => 70,
    'rows' => 16,
    'additional' => 15,
    'columns' => $options['pricing'] ? 5 : 2,
];

$imponibile = [];
$iva = [];
$sconto = [];

// Intestazione tabella per righe
echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Q.tà', [], ['upper' => true]).'</th>';

if ($options['pricing']) {
    echo "
            <th class='text-center' style='width:15%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Importo', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('IVA', [], ['upper' => true]).' (%)</th>';
}

            echo '
        </tr>
    </thead>

    <tbody>';

// Righe
$rs_gen = $dbo->fetchArray('SELECT *, (SELECT percentuale FROM co_iva WHERE id=idiva) AS perc_iva, IFNULL((SELECT peso_lordo FROM mg_articoli WHERE id=idarticolo),0) * qta AS peso_lordo, IFNULL((SELECT volume FROM mg_articoli WHERE id=idarticolo),0) * qta AS volume FROM `dt_righe_ddt` WHERE idddt='.prepare($id_record));
foreach ($rs_gen as $r) {
    $count = 0;
    $count += ceil(strlen($r['descrizione']) / $autofill['words']);
    $count += substr_count($r['descrizione'], PHP_EOL);

    echo '
    <tr>
        <td>
            '.nl2br($r['descrizione']);

    // Aggiunta riferimento a ordine
    if (!empty($r['idordine'])) {
        $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM or_ordini WHERE id='.prepare($r['idordine']));
        $numero = !empty($rso[0]['numero_esterno']) ? $rso[0]['numero_esterno'] : $rso[0]['numero'];

        echo '
            <br/><small>'.tr('Rif. ordine num. _NUM_ del _DATE_', [
                '_NUM_' => $numero,
                '_DATE_' => Translator::dateToLocale($rso[0]['data']),
            ]).'</small>';

        if ($count <= 1) {
            $count += 0.4;
        }
    }

    echo '
        </td>';

    echo '
        <td class="text-center">';
    if (empty($r['is_descrizione'])) {
        echo
            Translator::numberToLocale($r['qta']).' '.$r['um'];
    }
    echo '
        </td>';

    if ($options['pricing']) {
        // Prezzo unitario
        echo "
        <td class='text-right'>";
        if (empty($r['is_descrizione'])) {
            echo '
            '.Translator::numberToLocale($r['subtotale'] / $r['qta']).' &euro;';
        }
        echo '
        </td>';

        // Imponibile
        echo "
        <td class='text-right'>";
        if (empty($r['is_descrizione'])) {
            echo
            Translator::numberToLocale($r['subtotale']).' &euro;';

            if ($r['sconto'] > 0) {
                if ($count <= 1) {
                    $count += 0.4;
                }
                echo '
                <br><small class="help-block">- '.tr('sconto _TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($r['sconto_unitario']),
                    '_TYPE_' => ($r['tipo_sconto'] == 'PRC' ? '%' : '&euro;'),
                ]).'</small>';
            }
        }
        echo '
        </td>';

        // Iva
        echo "
        <td class='text-center'>";
        if (empty($r['is_descrizione'])) {
            echo
            Translator::numberToLocale($r['perc_iva']);
        }
        echo '
        </td>';
    }
    echo '
    </tr>';

    $autofill['count'] += $count;

    $imponibile[] = $r['subtotale'];
    $iva[] = $r['iva'];
    $sconto[] = $r['sconto'];
}

echo '
        |autofill|
    </tbody>
</table>';

// Info per il footer
$imponibile = sum($imponibile) - sum($sconto);
$iva = sum($iva);

$totale = $imponibile + $iva;

$volume = sum(array_column($rs_gen, 'volume'));
$peso_lordo = sum(array_column($rs_gen, 'peso_lordo'));
