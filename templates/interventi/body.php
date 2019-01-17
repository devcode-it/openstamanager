<?php

include_once __DIR__.'/../../core.php';

$report_name = 'intervento_'.$records[0]['codice'].'.pdf';

/*
    Dati intervento
*/
echo '
<table class="table table-bordered">
    <tr>
        <th colspan="4" style="font-size:13pt;" class="text-center">'.tr('Rapporto operazioni e interventi', [], ['upper' => true]).'</th>
    </tr>

    <tr>
        <td class="text-center" style="width:30%">'.tr('Intervento num.').': <b>'.$records[0]['codice'].'</b></td>
        <td class="text-center" style="width:20%">'.tr('Data').': <b>'.Translator::dateToLocale($records[0]['data_richiesta']).'</b></td>
        <td class="text-center" style="width:25%">'.tr('Preventivo num.').': <b>'.$records[0]['numero_preventivo'].'</b></td>
        <td class="text-center" style="width:25%">'.tr('Contratto num.').': <b>'.$records[0]['numero_contratto'].'</b></td>
    </tr>';

    // Dati cliente
    echo '
        <tr>
            <td colspan=2>
                '.tr('Cliente').': <b>'.$c_ragionesociale.'</b>
            </td>';

    // Codice fiscale o P.Iva

    if (!empty($c_piva)) {
        echo '
				<td colspan=2>
					'.tr('P.Iva').': <b>'.strtoupper($c_piva).'</b>
				</td>';
    } else {
        echo '
    			<td colspan=2>
    				'.tr('C.F.').': <b>'.strtoupper($c_codicefiscale).'</b>
    			</td>';
    }

    echo '</tr>';

    //Indirizzo
    if (!empty($c_indirizzo) or !empty($c_cap) or !empty($c_citta) or !empty($c_provincia)) {
        echo '
			<tr>
				<td colspan="4">
					'.((!empty($c_indirizzo)) ? tr('Via').': <b>'.$c_indirizzo.'</b>' : '').'
					'.((!empty($c_cap)) ? tr('CAP').': <b>'.$c_cap.'</b>' : '').'
					'.((!empty($c_citta)) ? tr('Città').': <b>'.$c_citta.'</b>' : '').'
					'.((!empty($c_provincia)) ? tr('Provincia').': <b>'.strtoupper($c_provincia).'</b>' : '').'
				</td>
			</tr>';
    }

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
$rs2 = $dbo->fetchArray('SELECT *, (SELECT nome FROM my_impianti WHERE id=my_impianti_interventi.idimpianto) AS nome, (SELECT matricola FROM my_impianti WHERE id=my_impianti_interventi.idimpianto) AS matricola FROM my_impianti_interventi WHERE idintervento='.prepare($id_record));
$impianti = [];
for ($j = 0; $j < count($rs2); ++$j) {
    $impianti[] = '<b>'.$rs2[$j]['nome']."</b> <small style='color:#777;'>(".$rs2[$j]['matricola'].')</small>';
}
echo '
    <tr>
        <td colspan="4">
        '.tr('Impianti').': '.implode(', ', $impianti).'
        </td>
    </tr>';

// Tipo intervento
echo '
    <tr>
        <td colspan="4">
            <b>'.tr('Tipo intervento').':</b> '.$records[0]['tipointervento'].'
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
$rs2 = $dbo->fetchArray("SELECT *,
    (SELECT codice FROM mg_articoli WHERE id=idarticolo) AS codice_art,
    (SELECT GROUP_CONCAT(`serial` SEPARATOR ', ') FROM `mg_prodotti` WHERE `id_riga_intervento` = `mg_articoli_interventi`.`idintervento`) AS seriali
FROM `mg_articoli_interventi` WHERE idintervento=".prepare($id_record)." AND NOT idarticolo='0' ORDER BY idarticolo ASC");
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
                '.$r['descrizione'];

        // Seriali
        if (!empty($r['seriali'])) {
            echo '
                <br><small>'.tr('SN').': '.$r['seriali'].'</small>';
        }

        echo '
            </td>';

        // Quantità
        echo '
            <td class="text-center">
                '.Translator::numberToLocale($r['qta'], 'qta').' '.$r['um'].'
            </td>';

        // Netto
        $netto = $r['prezzo_vendita'] * $r['qta'] - $r['sconto'];
        echo '
            <td class="text-center">
                '.($options['pricing'] ? Translator::numberToLocale($netto) : '-').'
            </td>
        </tr>';
    }

    echo '
    </tbody>';

    // Totale spesa articoli
    if ($options['pricing']) {
        echo '
    <tr>
        <td colspan="2" class="text-right">
            <b>'.tr('Totale materiale utilizzato', [], ['upper' => true]).':</b>
        </td>

        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($costi_intervento['ricambi_scontato']).' &euro;</b>
        </th>
    </tr>';
    }

    echo '
</table>';
}

// FINE MATERIALE UTILIZZATO

// Conteggio SPESE AGGIUNTIVE
$rs2 = $dbo->fetchArray('SELECT * FROM in_righe_interventi WHERE idintervento='.prepare($id_record).' ORDER BY id ASC');
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
            '.Translator::numberToLocale($r['qta'], 'qta').' '.$r['um'].'
        </td>';

        // Prezzo unitario
        echo '
        <td class="text-center">
            '.($options['pricing'] ? Translator::numberToLocale($r['prezzo_vendita']).' &euro;' : '-');

        if ($options['pricing'] && $r['sconto'] > 0) {
            echo "
            <br><small class='text-muted'>".tr('sconto _TOT_ _TYPE_', [
                '_TOT_' => Translator::numberToLocale($r['sconto_unitario']),
                '_TYPE_' => ($r['tipo_sconto'] == 'PRC' ? '%' : '&euro;'),
            ]).'</small>';
        }

        echo '
        </td>';

        // Prezzo totale
        $netto = $r['prezzo_vendita'] * $r['qta'] - $r['sconto'];
        echo '
        <td class="text-center">
            '.($options['pricing'] ? Translator::numberToLocale($netto) : '-').'
        </td>
    </tr>';
    }
    echo '
    </tbody>';

    if ($options['pricing']) {
        // Totale spese aggiuntive
        echo '
    <tr>
        <td colspan="3" class="text-right">
            <b>'.tr('Totale spese aggiuntive', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>'.Translator::numberToLocale($costi_intervento['altro_scontato']).' &euro;</b>
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
            <th class="text-center" style="font-size:8pt;">
                <b>'.tr('Tecnico').'</b>
            </th>

            <th class="text-center" style="font-size:8pt;width:14%">
                <b>'.tr('Data').'</b>
            </th>

            <th class="text-center" style="font-size:8pt;width:7%">
                <b>'.tr('Dalle').'</b>
            </th>

            <th class="text-center" style="font-size:8pt;width:7%">
                <b>'.tr('Alle').'</b>
            </th>

            <td class="text-center" style="font-size:6pt;width:35%">
                '.tr('I dati del ricevente verrano trattati in base alla normativa europea UE 2016/679 del 27 aprile 2016 (GDPR)').'
            </td>
        </tr>
    </thead>

    <tbody>';

// Sessioni di lavoro dei tecnici
$rst = $dbo->fetchArray('SELECT an_anagrafiche.*, in_interventi_tecnici.* FROM in_interventi_tecnici JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico=an_anagrafiche.idanagrafica WHERE in_interventi_tecnici.idintervento='.prepare($id_record).' ORDER BY in_interventi_tecnici.orario_inizio');

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

// Ore lavorate
$ore = get_ore_intervento($id_record);

echo '
    <tr>
        <td class="text-center">
            <small>'.tr('Ore lavorate').':</small><br/><b>'.Translator::numberToLocale($ore).'</b>
        </td>';

// Costo totale manodopera
if ($options['pricing']) {
    echo '
        <td colspan="3" class="text-center">
            <small>'.tr('Totale manodopera').':</small><br/><b>'.Translator::numberToLocale($costi_intervento['manodopera_addebito']).' &euro;</b>
        </td>';
} else {
    echo '
        <td colspan="3" class="text-center">-</td>';
}

// Timbro e firma
$firma = !empty($records[0]['firma_file']) ? '<img src="'.DOCROOT.'/files/interventi/'.$records[0]['firma_file'].'" style="width:70mm;">' : '';
echo '
        <td rowspan="2" class="text-center" style="font-size:8pt;height:30mm;vertical-align:bottom">
            '.$firma.'<br>
            <i>('.tr('Timbro e firma leggibile').'.)</i>
        </td>
    </tr>';

// Totale km
echo '
    <tr>
        <td class="text-center">
            <small>'.tr('Km percorsi').':</small><br/><b>'.Translator::numberToLocale($records[0]['tot_km']).'</b>
        </td>';

// Costo trasferta
if ($options['pricing']) {
    echo '
        <td class="text-center">
            <small>'.tr('Costi di trasferta').':</small><br/><b>'.Translator::numberToLocale($records[0]['tot_km_consuntivo']).' &euro;</b>
        </td>';
} else {
    echo '
        <td class="text-center">-</td>';
}

// Diritto di chiamata
if ($options['pricing']) {
    echo '
        <td class="text-center" colspan="2">
            <small>'.tr('Diritto di chiamata').':</small><br/><b>'.Translator::numberToLocale($records[0]['tot_dirittochiamata']).' &euro;</b>
        </td>';
} else {
    echo '
        <td class="text-center" colspan="2">-</td>
        ';
}

// TOTALE COSTI FINALI
if ($options['pricing']) {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="4" class="text-right">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>'.Translator::numberToLocale($costi_intervento['totale_addebito']).' &euro;</b>
        </th>
    </tr>';

    $totale_sconto = $costi_intervento['totale_addebito'] - $costi_intervento['totale_scontato'];

    // Eventuale sconto totale
    if (!empty($totale_sconto)) {
        echo '
        <tr>
            <td colspan="4" class="text-right">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
            </td>

            <th class="text-center">
                <b>-'.Translator::numberToLocale($totale_sconto).' &euro;</b>
            </th>
        </tr>';

        // Imponibile scontato
        echo '
        <tr>
            <td colspan="4" class="text-right">
                <b>'.tr('Imponibile scontato', [], ['upper' => true]).':</b>
            </td>

            <th class="text-center">
                <b>'.Translator::numberToLocale($costi_intervento['totale_scontato']).' &euro;</b>
            </th>
        </tr>';
    }

    // IVA
    // Totale intervento
    echo '
    <tr>
        <td colspan="4" class="text-right">
            <b>'.tr('Iva', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>'.Translator::numberToLocale($costi_intervento['iva_totale']).' &euro;</b>
        </th>
    </tr>';

    // TOTALE INTERVENTO
    echo '
    <tr>
    	<td colspan="4" class="text-right">
            <b>'.tr('Totale intervento', [], ['upper' => true]).':</b>
    	</td>
    	<th class="text-center">
    		<b>'.Translator::numberToLocale($costi_intervento['totale']).' &euro;</b>
    	</th>
    </tr>';
}

echo '
</table>';
