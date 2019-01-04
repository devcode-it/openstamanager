<?php

include_once __DIR__.'/../../core.php';

$report_name = 'preventivo_'.$records[0]['numero'].'_cons.pdf';

echo '
<div class="row">
    <div class="col-xs-6">
        <div class="text-center">
            <h4 class="text-bold">'.tr('Consuntivo', [], ['upper' => true]).'</h4>
            <b>'.tr('Preventivo num. _NUM_ del _DATE_', [
                '_NUM_' => $records[0]['numero'],
                '_DATE_' => Translator::dateToLocale($records[0]['data']),
            ], ['upper' => true]).'</b>
        </div>
    </div>

    <div class="col-xs-5 col-xs-offset-1">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td colspan=2 class="border-full" style="height:16mm;">
                    <p class="small-bold">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
                    <p>$c_indirizzo$ $c_citta_full$</p>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.tr('Partita IVA', [], ['upper' => true]).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_piva$</small>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.tr('Codice fiscale', [], ['upper' => true]).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_codicefiscale$</small>
                </td>
            </tr>
        </table>
    </div>
</div>';

// Descrizione
if (!empty($records[0]['descrizione'])) {
    echo '
<p>'.nl2br($records[0]['descrizione']).'</p>
<br>';
}

$sconto = [];
$imponibile = [];

$interventi = $dbo->fetchArray('SELECT *, in_interventi.id, in_interventi.codice, (SELECT GROUP_CONCAT(DISTINCT ragione_sociale) FROM in_interventi_tecnici JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = in_interventi_tecnici.idtecnico WHERE idintervento=in_interventi.id) AS tecnici, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS inizio, (SELECT SUM(ore) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS ore, (SELECT SUM(km) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS km FROM in_interventi WHERE in_interventi.id_preventivo='.prepare($id_record).' ORDER BY inizio DESC');

if (!empty($interventi)) {
    // Interventi
    echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:50%'>".tr('Attività', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Ore', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Km', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Sconto', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Imponibile', [], ['upper' => true]).'</th>
        </tr>
    </thead>

    <tbody>';

    $ore = [];
    $km = [];
    $sconto_int = [];
    $imponibile_int = [];

    foreach ($interventi as $int) {
        $int = array_merge($int, get_costi_intervento($int['id']));
        $int['sconto'] = ($int['manodopera_addebito'] - $int['manodopera_scontato']) + ($int['viaggio_addebito'] - $int['viaggio_scontato']);
        $int['subtotale'] = $int['manodopera_scontato'] + $int['viaggio_scontato'];
        $sconto[] = $int['sconto_globale'];

        echo '
        <tr>
            <td>
                '.tr('Intervento num. _NUM_ del _DATE_', [
                    '_NUM_' => $int['codice'],
                    '_DATE_' => Translator::dateToLocale($int['inizio']),
                ]);

        if (!empty($int['tecnici'])) {
            echo '
                <br><small class="text-muted">'.tr('Tecnici').': '.str_replace(',', ', ', $int['tecnici']).'.</small>';
        }

        echo '
            </td>';

        echo '
            <td class="text-center">
                '.Translator::numberToLocale($int['ore']).'
            </td>

            <td class="text-center">
                '.Translator::numberToLocale($int['km']).'
            </td>

            <td class="text-center">
                '.Translator::numberToLocale($int['sconto']).' &euro;
            </td>

            <td class="text-center">
                '.Translator::numberToLocale($int['subtotale']).' &euro;
            </td>
        </tr>';

        $ore[] = $int['ore'];
        $km[] = $int['km'];

        $sconto_int[] = $sconto;
        $imponibile_int[] = $int['subtotale'];
    }

    $ore = sum($ore);
    $km = sum($km);

    $sconto_int = sum($sconto_int);
    $imponibile_int = sum($imponibile_int);
    $totale_int = $imponibile_int - $sconto_int;

    $sconto[] = $sconto_int;
    $imponibile[] = $imponibile_int;

    echo '
    </tbody>';

    // Totale interventi
    echo '
    <tr>
        <td class="text-right">
            <b>'.tr('Totale', [], ['upper' => true]).':</b>
        </td>

        <td class="text-center">
            <b>'.Translator::numberToLocale($ore).'</b>
        </td>

        <td class="text-center">
            <b>'.Translator::numberToLocale($km).'</b>
        </td>

        <td class="text-center">
            <b>'.Translator::numberToLocale($sconto_int).' &euro;</b>
        </td>

        <th class="text-center">
            <b>'.Translator::numberToLocale($totale_int).' &euro;</b>
        </th>
    </tr>';

    echo '
</table>';

    $count = $dbo->fetchArray('SELECT COUNT(*) FROM `mg_articoli_interventi` WHERE idintervento IN ('.implode(',', array_column($interventi, 'id')).')');
    if (!empty($count)) {
        echo '
    <table class="table table-bordered">
        <thead>
            <tr>
                <th colspan="4" class="text-center">
                    <b>'.tr('Materiale utilizzato', [], ['upper' => true]).'</b>
                </th>
            </tr>

            <tr>
                <th style="font-size:8pt;width:50%" class="text-center">
                    <b>'.tr('Descrizione').'</b>
                </th>

                <th style="font-size:8pt;width:15%" class="text-center">
                    <b>'.tr('Q.tà').'</b>
                </th>

                <th style="font-size:8pt;width:15%" class="text-center">
                    <b>'.tr('Prezzo').'</b>
                </th>

                <th style="font-size:8pt;width:15%" class="text-center">
                    <b>'.tr('Importo').'</b>
                </th>
            </tr>
        </thead>

        <tbody>';

        $sconto_art = [];
        $imponibile_art = [];

        // Articoli per intervento
        foreach ($interventi as $int) {
            $righe = $dbo->fetchArray("SELECT *, (SELECT codice FROM mg_articoli WHERE id=idarticolo) AS codice, (SELECT CONCAT_WS(serial, 'SN: ', ', ') FROM mg_prodotti WHERE mg_articoli_interventi.idarticolo = mg_prodotti.id_articolo AND mg_prodotti.id_riga_intervento = mg_articoli_interventi.idintervento) AS serials FROM `mg_articoli_interventi` WHERE idintervento =".prepare($int['id']).' ORDER BY idarticolo ASC');

            foreach ($righe as $r) {
                echo '
        <tr>';

                // Descrizione
                echo '
            <td>
                '.$r['descrizione'];

                // Codice
                if (!empty($r['codice'])) {
                    echo '
                <br><small class="text-muted">'.tr('COD. _COD_', [
                    '_COD_' => $r['codice'],
                ]).'</small>';
                }

                echo '
                <br><small class="text-muted">'.tr('Intervento num. _NUM_ del _DATE_', [
                    '_NUM_' => $int['codice'],
                    '_DATE_' => Translator::dateToLocale($int['inizio']),
                ]).'.</small>';

                echo '
            </td>';

                // Quantità
                echo '
            <td class="text-center">
                '.Translator::numberToLocale($r['qta'], 'qta').' '.$r['um'].'
            </td>';

                // Prezzo unitario
                echo "
            <td class='text-center'>
                ".Translator::numberToLocale($r['prezzo_vendita']).' &euro;';

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

                echo '
            </td>';

                // Netto
                $netto = $r['prezzo_vendita'] * $r['qta'];
                echo '
            <td class="text-center">
                '.Translator::numberToLocale($netto).' &euro;';

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

                echo '
            </td>
        </tr>';

                $sconto_art[] = $r['sconto'];
                $imponibile_art[] = $r['prezzo_vendita'] * $r['qta'];
            }
        }

        echo '
    </tbody>';

        $sconto_art = sum($sconto_art);
        $imponibile_art = sum($imponibile_art);
        $totale_art = $imponibile_art - $sconto_art;

        $sconto[] = $sconto_art;
        $imponibile[] = $imponibile_art;

        // Totale spesa articoli
        echo '
    <tr>
        <td colspan="2" class="text-right">
            <b>'.tr('Totale materiale utilizzato', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($totale_art).' &euro;</b>
        </th>
    </tr>';

        echo '
</table>';
    }

    // Altre spese per intervento
    $count = $dbo->fetchArray('SELECT COUNT(*) FROM `in_righe_interventi` WHERE idintervento IN ('.implode(',', array_column($interventi, 'id')).')');
    if (!empty($count)) {
        echo '
    <table class="table table-bordered">
        <thead>
            <tr>
                <th colspan="4" class="text-center">
                    <b>'.tr('Spese aggiuntive', [], ['upper' => true]).'</b>
                </th>
            </tr>

            <tr>
                <th style="font-size:8pt;width:50%" class="text-center">
                    <b>'.tr('Descrizione').'</b>
                </th>

                <th style="font-size:8pt;width:15%" class="text-center">
                    <b>'.tr('Q.tà').'</b>
                </th>

                <th style="font-size:8pt;width:15%" class="text-center">
                    <b>'.tr('Prezzo').'</b>
                </th>

                <th style="font-size:8pt;width:15%" class="text-center">
                    <b>'.tr('Importo').'</b>
                </th>
            </tr>
        </thead>

        <tbody>';

        $sconto_spese = [];
        $imponibile_spese = [];

        // Articoli per intervento
        foreach ($interventi as $int) {
            $righe = $dbo->fetchArray('SELECT * FROM `in_righe_interventi` WHERE idintervento ='.prepare($int['id']).' ORDER BY id ASC');

            foreach ($righe as $r) {
                echo '
        <tr>';

                // Descrizione
                echo '
            <td>
                '.$r['descrizione'];

                echo '
                <br><small class="text-muted">'.tr('Intervento num. _NUM_ del _DATE_', [
                    '_NUM_' => $int['codice'],
                    '_DATE_' => Translator::dateToLocale($int['inizio']),
                ]).'.</small>';

                echo '
            </td>';

                // Quantità
                echo '
            <td class="text-center">
                '.Translator::numberToLocale($r['qta'], 'qta').' '.$r['um'].'
            </td>';

                // Prezzo unitario
                echo "
            <td class='text-center'>
                ".Translator::numberToLocale($r['prezzo_vendita']).' &euro;';

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

                echo '
            </td>';

                // Netto
                $netto = $r['prezzo_vendita'] * $r['qta'];
                echo '
            <td class="text-center">
                '.Translator::numberToLocale($netto).' &euro;';

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

                echo '
            </td>
        </tr>';

                $sconto_spese[] = $r['sconto'];
                $imponibile_spese[] = $r['prezzo_vendita'] * $r['qta'];
            }
        }

        echo '
    </tbody>';

        $sconto_spese = sum($sconto_spese);
        $imponibile_spese = sum($imponibile_spese);
        $totale_spese = $imponibile_spese - $sconto_spese;

        $sconto[] = $sconto_spese;
        $imponibile[] = $imponibile_spese;

        // Totale spese aggiuntive
        echo '
    <tr>
        <td colspan="2" class="text-right">
            <b>'.tr('Totale spese aggiuntive', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($totale_spese).' &euro;</b>
        </th>
    </tr>';

        echo '
</table>';
    }
}

// TOTALE COSTI FINALI
$sconto = sum($sconto);
$imponibile = sum($imponibile);

$totale = $imponibile - $sconto;

//$rs = $dbo->fetchArray('SELECT SUM(subtotale) as budget FROM `co_righe_preventivi` WHERE idpreventivo = '.prepare($id_record));
//$budget = $rs[0]['budget'];
$budget = get_imponibile_preventivo($id_record);

//pulisco da informazioni irrilevanti (imponibile,iva)
$show = false;

$rapporto = floatval($budget) - floatval($totale);

// Totale imponibile
echo '
<table class="table table-bordered">';

if ($show) {
    echo '<tr>
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
    $rs = $dbo->fetchArray('SELECT * FROM co_iva WHERE co_iva.id = '.prepare(setting('Iva predefinita')));
    $percentuale_iva = $rs[0]['percentuale'];
    $iva = $totale / 100 * $percentuale_iva;

    echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Iva (_PRC_%)', [
                '_PRC_' => Translator::numberToLocale($percentuale_iva, 0),
            ], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($iva).' &euro;</b>
        </th>
    </tr>';

    //$totale = sum($totale, $iva);
}

// TOTALE
echo '
    <tr>
    	<td colspan="3" class="text-right border-top">
            <b>'.tr('Totale consuntivo (no iva)', [], ['upper' => true]).':</b>
    	</td>
    	<th colspan="2" class="text-center">
    		<b>'.Translator::numberToLocale($totale).' &euro;</b>
    	</th>
    </tr>';

// BUDGET
echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Budget (no IVA)', [], ['upper' => true]).':</b>
        </td>
        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($budget).' &euro;</b>
        </th>
    </tr>';

// RAPPORTO
echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Rapporto budget/spesa (no IVA)', [], ['upper' => true]).':</b>
        </td>
        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($rapporto).' &euro;</b>
        </th>
    </tr>';

echo'
</table>';
