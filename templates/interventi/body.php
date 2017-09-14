<?php

include_once __DIR__.'/../../core.php';

include_once $docroot.'/modules/interventi/modutil.php';

$report_name = 'intervento_'.$idintervento.'.pdf';

/*
    Dati intervento
*/
echo '
<table class="table table-bordered">
    <tr>
        <th colspan="4" style="font-size:13pt;" class="text-center">'.tr('Rapporto operazioni e interventi', [], ['upper' => true]).'</th>
    </tr>

    <tr>
        <td class="text-center" style="width:40%">'.tr('Intervento numero').': <b>'.$records[0]['codice'].'</b></td>
        <td class="text-center" style="width:20%">'.tr('Data').': <b>'.Translator::dateToLocale($records[0]['data_richiesta']).'</b></td>
        <td class="text-center" style="width:20%">'.tr('Preventivo Num.').': <b>'.$records[0]['numero_preventivo'].'</b></td>
        <td class="text-center" style="width:20%">'.tr('Contratto Num.').': <b>'.$records[0]['numero_contratto'].'</b></td>
    </tr>';

// Dati cliente
echo '
    <tr>
        <td colspan=3>
            '.tr('Cliente').': <b>'.$c_ragionesociale.'</b>
        </td>';

// Codice fiscale
echo '
        <td>
            '.tr('P.Iva').': <b>'.strtoupper($c_piva).'</b>
        </td>
    </tr>';

// riga 2
echo '
    <tr>
        <td colspan="4">
            '.tr('Via').': <b>'.$c_indirizzo.'</b> -
            '.tr('CAP').': <b>'.$c_cap.'</b> -
            '.tr('Comune').': <b>'.$c_citta.' ('.strtoupper($c_provincia).')</b>
        </td>
    </tr>';

echo '
    <tr>
        <td colspan="4">
            '.tr('Telefono').': <b>'.$c_telefono.'</b>';
if (!empty($c_cellulare)) {
    echo' - '.tr('Cellulare').': <b>'.$c_cellulare.'</b>';
}
echo '
        </td>
    </tr>';

// riga 3
// Elenco impianti su cui è stato fatto l'intervento
$rs2 = $dbo->fetchArray('SELECT *, (SELECT nome FROM my_impianti WHERE id=my_impianti_interventi.idimpianto) AS nome, (SELECT matricola FROM my_impianti WHERE id=my_impianti_interventi.idimpianto) AS matricola FROM my_impianti_interventi WHERE idintervento='.prepare($idintervento));
$impianti = [];
for ($j = 0; $j < sizeof($rs2); ++$j) {
    $impianti[] = '<b>'.$rs2[$j]['nome']."</b> <small style='color:#777;'>(".$rs2[$j]['matricola'].')</small>';
}
echo '
    <tr>
        <td colspan="4">
        '.tr('Impianti').': '.implode(', ', $impianti).'
        </td>
    </tr>';

// Richiesta
echo '
    <tr>
        <td colspan="4" style="height:20mm;">
            <b>'.tr('Richiesta').':</b>
            <p>'.nl2br($records[0]['richiesta']).'</p>
        </td>
    </tr>';

// Descrizione
echo '
    <tr>
        <td colspan="4" style="height:20mm;">
            <b>'.tr('Descrizione').':</b>
            <p>'.nl2br($records[0]['descrizione_intervento']).'</p>
        </td>
    </tr>';

echo '
</table>';

$totale = [];

// MATERIALE UTILIZZATO
$rs2 = $dbo->fetchArray('SELECT *, (SELECT codice FROM mg_articoli WHERE id=idarticolo) AS codice_art FROM `mg_articoli_interventi` WHERE idintervento='.prepare($idintervento)." AND NOT idarticolo='0' ORDER BY idarticolo ASC");
if (!empty($rs2)) {
    echo '
<table class="table table-bordered">
    <thead>
        <tr>
            <th colspan="4" class="text-center">
                <b>'.tr('Materiale utilizzato', [], ['upper' => true]).'</b>
            </th>
        </tr>

        <tr>
            <th style="font-size:8pt;width:20%" class="text-center">
                <b>'.tr('Codice').'</b>
            </th>

            <th style="font-size:8pt;width:50%" class="text-center">
                <b>'.tr('Descrizione').'</b>
            </th>

            <th style="font-size:8pt;width:15%" class="text-center">
                <b>'.tr('Q.tà').'</b>
            </th>

            <th style="font-size:8pt;width:15%" class="text-center">
                <b>'.tr('Prezzo').'</b>
            </th>
        </tr>
    </thead>

    <tbody>';

    foreach ($rs2 as $r) {
        echo '
        <tr>';

        // Codice
        echo '
            <td>
                '.$r['codice_art'].'
            </td>';

        // Descrizione
        echo '
            <td>
                '.$r['descrizione'].'
            </td>';

        // Quantità
        echo '
            <td class="text-center">
                '.Translator::numberToLocale($r['qta'], 2).' '.$r['um'].'
            </td>';

        // Netto
        $netto = $r['prezzo_vendita'] * $r['qta'] - $r['sconto'];
        echo '
            <td class="text-center">
                '.($mostra_prezzi ? Translator::numberToLocale($netto, 2) : '-').'
            </td>
        </tr>';
    }

    echo '
    </tbody>';

    // Totale spesa articoli
    if ($mostra_prezzi) {
        echo '
    <tr>
        <td colspan="2" class="text-right">
            <b>'.tr('Totale materiale utilizzato', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($costi_intervento['ricambi_scontato'], 2).' &euro;</b>
        </th>
    </tr>';
    }

    echo '
</table>';
}

// FINE MATERIALE UTILIZZATO

// Conteggio SPESE AGGIUNTIVE
$rs2 = $dbo->fetchArray('SELECT * FROM in_righe_interventi WHERE idintervento='.prepare($idintervento).' ORDER BY id ASC');
if (!empty($rs2)) {
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
                <b>'.tr('Prezzo unitario').'</b>
            </th>

            <th style="font-size:8pt;width:20%" class="text-center">
                <b>'.tr('Subtot.').'</b>
            </th>
        </tr>
    </thead>

    <tbody>';

    foreach ($rs2 as $r) {
        // Articolo
        echo '
    <tr>
        <td>
            '.nl2br($r['descrizione']).'
        </td>';

        // Quantità
        echo '
        <td class="text-center">
            '.Translator::numberToLocale($r['qta'], 2).'
        </td>';

        // Prezzo unitario
        echo '
        <td class="text-center">
            '.($mostra_prezzi ? Translator::numberToLocale($r['prezzo_vendita'], 2).' &euro;' : '-').'
        </td>';

        // Prezzo totale
        $netto = $r['prezzo_vendita'] * $r['qta'] - $r['sconto'];
        echo '
        <td class="text-center">
            '.($mostra_prezzi ? Translator::numberToLocale($netto, 2) : '-').'
        </td>
    </tr>';
    }
    echo '
    </tbody>';

    if ($mostra_prezzi) {
        // Totale spese aggiuntive
        echo '
    <tr>
        <td colspan="3" class="text-right">
            <b>'.tr('Totale spese aggiuntive', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>'.Translator::numberToLocale($costi_intervento['altro_scontato'], 2).' &euro;</b>
        </th>
    </tr>';
    }

    echo '
</table>';
}

// FINE SPESE AGGIUNTIVE

// INTESTAZIONE ELENCO TECNICI
echo '
<table class="table table-bordered vertical-middle">
    <thead>
        <tr>
            <th class="text-center" colspan="5" style="font-size:11pt;">
                <b>'.tr('Ore tecnici', [], ['upper' => true]).'</b>
            </th>
        </tr>
        <tr>
            <th class="text-center" style="font-size:8pt;width:30%">
                <b>'.tr('Tecnico').'</b>
            </th>

            <th class="text-center" style="font-size:8pt;width:15%">
                <b>'.tr('Data').'</b>
            </th>

            <th class="text-center" style="font-size:8pt;width:10%">
                <b>'.tr('Dalle').'</b>
            </th>

            <th class="text-center" style="font-size:8pt;width:10%">
                <b>'.tr('Alle').'</b>
            </th>

            <td class="text-center" style="font-size:6pt;width:35%">
                '.tr('I dati del ricevente verrano trattati in base al D.lgs n. 196/2003').'
            </td>
        </tr>
    </thead>

    <tbody>';

// Sessioni di lavoro dei tecnici
$rst = $dbo->fetchArray('SELECT an_anagrafiche.*, in_interventi_tecnici.* FROM in_interventi_tecnici JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico=an_anagrafiche.idanagrafica WHERE in_interventi_tecnici.idintervento='.prepare($idintervento).' ORDER BY in_interventi_tecnici.orario_inizio');

foreach ($rst as $i => $r) {
    echo '
    <tr>';

    // nome tecnico
    echo '
    	<td>
    	    '.$r['ragione_sociale'].'
    	</td>';

    // data
    echo '
    	<td class="text-center">
            '.Translator::dateToLocale($r['orario_inizio'], '-').'
    	</td>';

    // ora inizio
    echo '
    	<td class="text-center">
            '.Translator::timeToLocale($r['orario_inizio'], '-').'
    	</td>';

    // ora fine
    echo '
    	<td class="text-center">
            '.Translator::timeToLocale($r['orario_fine'], '-').'
        </td>';

    // Spazio aggiuntivo
    if ($i == 0) {
        echo '
    	<td class="text-center" style="font-size:8pt;">
            '.tr('Si dichiara che i lavori sono stati eseguiti ed i materiali installati').'
        </td>';
    } else {
        echo '
    	<td class="text-center" style="border-bottom:0px;border-top:0px;"></td>';
    }

    echo '
    </tr>';
}

echo '
    <tr>';

// Ore lavorate
if ($mostra_prezzi) {
    $ore = get_ore_intervento($idintervento);

    $costo_orario = $records[0]['tot_ore_consuntivo'] - $records[0]['tot_dirittochiamata'];

    if ($ore > 0) {
        $costo_orario /= $ore;
    }

    echo '
        <td class="text-center">
        '.tr('Ore lavorate').':<br/><b>'.Translator::numberToLocale($ore, 2).'</b>
        </td>';

    // Costo orario
    echo '
        <td class="text-center">
            '.tr('Costo orario').':<br/><b>'.Translator::numberToLocale($costo_orario, 2).'</b>';

    if ($records[0]['tot_dirittochiamata'] != 0) {
        echo '
            <small> + '.Translator::numberToLocale($records[0]['tot_dirittochiamata'], 2).' d.c.</small>';
    }

    echo '
        </td>';

    // Costo totale manodopera
    echo '
        <td colspan="2" class="text-center">
        '.tr('Manodopera').':<br/><b>'.Translator::numberToLocale($costi_intervento['manodopera_scontato'], 2).'</b>
        </td>';
} else {
    echo '
        <td colspan="4"></td>';
}

// Timbro e firma
$firma = !empty($records[0]['firma_file']) ? '<img src="'.$docroot.'/files/interventi/'.$records[0]['firma_file'].'" style="width:70mm;">' : '';
echo '
        <td class="text-center" style="font-size:8pt;height:30mm;vertical-align:bottom">
            '.$firma.'<br>
            <i>('.tr('Timbro e firma leggibile').'.)</i>
        </td>
    </tr>';

// TOTALE COSTI FINALI
if ($mostra_prezzi) {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="4" class="text-right">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>'.Translator::numberToLocale($costi_intervento['totale_scontato'], 2).' &euro;</b>
        </th>
    </tr>';

    // Eventuale sconto incondizionato
    if (!empty($costi_intervento['sconto_globale'])) {
        echo '
    <tr>
        <td colspan="4" class="text-right">
            <b>'.tr('Sconto incondizionato', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>-'.Translator::numberToLocale($costi_intervento['sconto_globale'], 2).' &euro;</b>
        </th>
    </tr>';

        // Imponibile scontato
        echo '
    <tr>
        <td colspan="4" class="text-right">
            <b>'.tr('Imponibile scontato', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>'.Translator::numberToLocale($costi_intervento['totale'], 2).' &euro;</b>
        </th>
    </tr>';
    }

    // Leggo iva da applicare
    $rs1 = $dbo->fetchArray('SELECT percentuale FROM co_iva WHERE id='.prepare(get_var('Iva predefinita')));
    $percentuale_iva = $rs1[0]['percentuale'];

    $iva = ($costi_intervento['totale'] / 100 * $percentuale_iva);

    // IVA
    // Totale intervento
    echo '
    <tr>
        <td colspan="4" class="text-right">
            <b>'.tr('Iva (_PRC_%)', [
                '_PRC_' => Translator::numberToLocale($percentuale_iva, 0),
            ], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>'.Translator::numberToLocale($iva, 2).' &euro;</b>
        </th>
    </tr>';

    $totale = sum($costi_intervento['totale'], $iva);

    // TOTALE INTERVENTO
    echo '
    <tr>
    	<td colspan="4" class="text-right">
            <b>'.tr('Totale intervento', [], ['upper' => true]).':</b>
    	</td>
    	<th class="text-center">
    		<b>'.Translator::numberToLocale($totale, 2).' &euro;</b>
    	</th>
    </tr>';
}

echo '
</table>';
