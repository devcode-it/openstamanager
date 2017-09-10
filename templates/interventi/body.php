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
        <th colspan="4" style="dont-size:14pt;" class="text-center">RAPPORTO OPERAZIONI E INTERVENTI</th>
    </tr>

    <tr>
        <td class="text-center" style="width:40%">Intervento numero: <b>'.$records[0]['codice'].'</b></td>
        <td class="text-center" style="width:20%">Data: <b>'.Translator::dateToLocale($records[0]['data_richiesta']).'</b></td>
        <td class="text-center" style="width:20%">Preventivo N<sup>o</sup>: <b>'.$records[0]['numero_preventivo'].'</b></td>
        <td class="text-center" style="width:20%">Contratto N<sup>o</sup>: <b>'.$records[0]['numero_contratto'].'</b></td>
    </tr>';

    // Dati cliente
echo '
    <tr>
        <td colspan=3>
            Cliente: <b>'.$c_ragionesociale.'</b>
        </td>';

//Codice fiscale
echo '
        <td>
            P.iva: <b>'.strtoupper($c_piva).'</b>
        </td>
    </tr>';

//riga 2
echo '
    <tr>
        <td colspan="4">
            Via: <b>'.$c_indirizzo.'</b> -
            Cap: <b>'.$c_cap.'</b> -
            Comune: <b>'.$c_citta.' ('.strtoupper($c_provincia).')</b>
        </td>
    </tr>';

echo '
    <tr>
        <td colspan="4">
            Telefono: <b>'.$c_telefono.'</b>';
if (!empty($c_cellulare)) {
    echo' - Cellulare: <b>'.$c_cellulare.'</b>';
}
echo '
        </td>
    </tr>';

//riga 3
//Elenco impianti su cui è stato fatto l'intervento
$rs2 = $dbo->fetchArray('SELECT *, (SELECT nome FROM my_impianti WHERE id=my_impianti_interventi.idimpianto) AS nome, (SELECT matricola FROM my_impianti WHERE id=my_impianti_interventi.idimpianto) AS matricola FROM my_impianti_interventi WHERE idintervento='.prepare($idintervento));
$impianti = [];
for ($j = 0; $j < sizeof($rs2); ++$j) {
    $impianti[] = '<b>'.$rs2[$j]['nome']."</b> <small style='color:#777;'>(".$rs2[$j]['matricola'].')</small>';
}
echo '
    <tr>
        <td colspan="4">
            Impianti: '.implode(', ', $impianti).'
        </td>
    </tr>';

if (!empty($records[0]['richiesta'])) {
    //Richiesta
    echo '
    <tr>
        <td colspan="4" style="height:20mm;">
            <b>Richiesta:</b>
            <p>'.nl2br($records[0]['richiesta']).'</p>
        </td>
    </tr>';
}

if (!empty($records[0]['descrizione_intervento'])) {
    //descrizione
    echo '
    <tr>
        <td><b>Descrizione:</b></td>
    </tr>
    <tr>
        <td style="height:5mm;">'.nl2br($records[0]['descrizione_intervento']).'</td>
    </tr>';
}
echo '
</table>';

$totale = [];

// MATERIALE UTILIZZATO

// Conteggio articoli utilizzati
$rs2 = $dbo->fetchArray('SELECT *, (SELECT codice FROM mg_articoli WHERE id=idarticolo) AS codice_art, SUM(qta) AS sumqta FROM `mg_articoli_interventi` HAVING idintervento='.prepare($idintervento)." AND NOT idarticolo='0' ORDER BY idarticolo ASC");
if (!empty($rs2)) {
    echo '
<table class="table_values" cellspacing="0" cellpadding="0" style="font-size:11px; table-layout:fixed; border-color:#aaa;">
    <col width="90"><col width="254"><col width="54"><col width="80"><col width="80"><col width="80">

    <tr>
        <td align="center" colspan="6" valign="middle" style="font-size:11pt;" bgcolor="#cccccc">
            <b>'.tr('Materiale utilizzato', [], ['upper' => true]).'</b>
        </td>
    </tr>

    <tr>
        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Codice').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Descrizione').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Q.tà').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Prezzo listino').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Sconto').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Subtot. netto').'</b>
        </td>
    </tr>';

    $totale_articoli = [];

    foreach ($rs2 as $r) {
        echo '
    <tr>';

        // Codice
        echo '
        <td valign="top">
            '.$r['codice_art'].'
        </td>';

        // Descrizione
        echo '
        <td valign="top">
            '.$r['descrizione'].'
        </td>';

        // Quantità
        echo '
        <td align="center" valign="top">
            '.Translator::numberToLocale($r['sumqta'], 2).' '.$r['um'].'
        </td>';

        // Prezzo unitario
        echo '
        <td align="right" valign="top">
            '.($visualizza_costi ? Translator::numberToLocale($r['prezzo_vendita'], 2).' &euro;' : '-').'
        </td>';

        // Sconto unitario
        if ($r['sconto_unitario'] > 0) {
            $sconto = Translator::numberToLocale($r['sconto_unitario']).($r['tipo_sconto'] == 'PRC' ? '%' : ' &euro;');
        } else {
            $sconto = '-';
        }

        echo '
        <td align="center" valign="top">
            '.($visualizza_costi ? $sconto : '-').'
        </td>';

        // Netto
        $netto = ($r['prezzo_vendita'] - $r['sconto']) * $r['sumqta'];

        echo '
        <td align="right" valign="top">
            '.($visualizza_costi ? Translator::numberToLocale($netto, 2) : '-').'
        </td>
    </tr>';

        // Totale
        $totale_articoli[] = $netto;
    }

    $totale_articoli = sum($totale_articoli);
    $totale[] = $totale_articoli;

    // Totale spesa articoli
    if ($visualizza_costi) {
        echo '
    <tr>
        <td colspan="5" align="right">
            <b>'.tr('Totale materiale utilizzato', [], ['upper' => true]).':</b>
        </td>

        <td align="right" bgcolor="#dddddd">
            <b>'.Translator::numberToLocale($totale_articoli, 2).' &euro;</b>
        </td>
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
<table class="table_values" cellspacing="0" cellpadding="0" style="table-layout:fixed; border-color:#aaa; font-size:11px;">
    <col width="90"><col width="254"><col width="54"><col width="80"><col width="80"><col width="80">

    <tr>
        <td align="center" colspan="6" valign="middle" style="font-size:11pt;" bgcolor="#cccccc">
            <b>'.tr('Spese aggiuntive', [], ['upper' => true]).'</b>
        </td>
    </tr>

    <tr>
        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b></b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Descrizione').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Q.tà').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Prezzo listino').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Sconto').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Subtot. netto').'</b>
        </td>
    </tr>';

    $totale_righe = [];

    foreach ($rs2 as $r) {
        // Articolo
        echo '
    <tr>
        <td></td>

        <td>
            '.nl2br($r['descrizione']).'
        </td>';

        // Quantità
        echo '
        <td align="center">
            '.Translator::numberToLocale($r['qta'], 2).'
        </td>';

        // Prezzo unitario

        echo '
        <td align="right" valign="top">
            '.($visualizza_costi ? Translator::numberToLocale($r['prezzo_vendita'], 2).' &euro;' : '-').'
        </td>';

        // Sconto unitario
        if ($r['sconto_unitario'] > 0) {
            $sconto = Translator::numberToLocale($r['sconto_unitario']).($r['tipo_sconto'] == 'PRC' ? '%' : ' &euro;');
        } else {
            $sconto = '-';
        }

        echo '
        <td align="center" valign="top">
            '.($visualizza_costi ? $sconto : '-').'
        </td>';

        // Prezzo totale
        $netto = ($r['prezzo_vendita'] - $r['sconto']) * $r['qta'];

        echo '
        <td align="right" valign="top">
            '.($visualizza_costi ? Translator::numberToLocale($netto, 2) : '-').'
        </td>
    </tr>';

        // Subtot
        $totale_righe[] = $netto;
    }

    $totale_righe = sum($totale_righe);
    $totale[] = $totale_righe;

    if ($visualizza_costi) {
        // Totale spese aggiuntive
        echo '
    <tr>
        <td colspan="5" align="right">
            <b>'.tr('Totale spese aggiuntive', [], ['upper' => true]).':</b>
        </td>

        <td align="right" bgcolor="#dddddd">
            <b>'.Translator::numberToLocale($totale_righe, 2).' &euro;</b>
        </td>
    </tr>';
    }

    echo '
</table>';
}

// FINE SPESE AGGIUNTIVE

// ORE TECNICI + FIRMA
echo '
<table class="table_values" cellspacing="0" cellpadding="0" style="table-layout:fixed;">
    <col width="362"><col width="80"><col width="70"><col width="70"><col width="74">

    <tr>
        <td align="center" colspan="5" valign="middle" style="font-size:11pt;" bgcolor="#cccccc">
            <b>'.tr('Ore tecnici', [], ['upper' => true]).'</b>
        </td>
    </tr>';

// INTESTAZIONE ELENCO TECNICI
echo '
    <tr>
        <td align="center" style="font-size:8pt;" bgcolor="#dddddd">
            <b>'.tr('Tecnico').'</b>
        </td>

        <td align="center" style="font-size:8pt;" bgcolor="#dddddd">
            <b>'.tr('Data').'</b>
        </td>

        <td align="center" style="font-size:8pt;" bgcolor="#dddddd">
            <b>'.tr('Dalle').'</b>
        </td>

        <td align="center" style="font-size:8pt;" bgcolor="#dddddd">
            <b>'.tr('Alle').'</b>
        </td>

        <td align="center" style="font-size:8pt;" bgcolor="#dddddd">
            <b>'.tr('Sconto').'</b>
        </td>
    </tr>';

// Sessioni di lavoro dei tecnici
$rst = $dbo->fetchArray('SELECT an_anagrafiche.*, in_interventi_tecnici.* FROM in_interventi_tecnici JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico=an_anagrafiche.idanagrafica WHERE in_interventi_tecnici.idintervento='.prepare($idintervento).' ORDER BY in_interventi_tecnici.orario_inizio');

$totale_ore = 0;
$totale_costo_ore = 0;
$totale_costo_km = 0;
$totale_sconto = 0;
$totale_sconto_km = 0;
$totale_manodopera = 0;
$totale_viaggio = 0;

foreach ($rst as $r) {
    echo '
    <tr>';

    // nome tecnico
    echo '
    	<td align="left">
    	    '.$r['ragione_sociale'].'
    	</td>';

    // data
    echo '
    	<td align="center">
            '.Translator::dateToLocale($r['orario_inizio'], '-').'
    	</td>';

    // ora inizio
    echo '
    	<td align="center">
            '.Translator::timeToLocale($r['orario_inizio'], '-').'
    	</td>';

    // ora fine
    echo '
    	<td align="center">
            '.Translator::timeToLocale($r['orario_fine'], '-').'
    	</td>';

    // Sconto
    echo '
    	<td align="center">
            '.($r['sconto_unitario'] > 0 ? Translator::numberToLocale($r['sconto_unitario']).($r['tipo_sconto'] == 'PRC' ? '%' : ' &euro;') : '-').'
    	</td>
    </tr>';

    $totale_ore += $r['ore'];
    $totale_km += $r['km'];

    $totale_costo_ore = sum($totale_costo_ore, $r['prezzo_ore_consuntivo']);
    $totale_sconto = sum($totale_sconto, $r['sconto']);

    $totale_costo_km = sum($totale_costo_km, $r['prezzo_km_consuntivo']);
    $totale_sconto_km = sum($totale_sconto_km, $r['scontokm']);
}

$totale_manodopera = sum($totale_costo_ore, -$totale_sconto);
$totale_viaggio = sum($totale_costo_km, -$totale_sconto_km);

$totale_intervento = sum($totale_manodopera, $totale_viaggio);

$totale[] = $totale_intervento;

echo '
</table>';

// ore lavorate
echo '
<table class="table_values" cellspacing="0" cellpadding="0" style="table-layout:fixed; font-size:11px;">
    <col width="90"><col width="326"><col width="80"><col width="80"><col width="80">

    <tr>
        <td style="font-size:8pt;" align="center" bgcolor="#dedede"></td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Descrizione').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Q.tà').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Prezzo listino').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'.tr('Subtot. netto').'</b>
        </td>
    </tr>';

// Ore lavoro
echo '
    <tr>
        <td></td>

        <td>
            '.tr('Ore tecnici').'
        </td>

        <td align="center">
            '.Translator::numberToLocale($totale_ore, 2).' ore
        </td>';

if ($visualizza_costi) {
    echo '
        <td align="right">
            '.Translator::numberToLocale($totale_costo_ore, 2).' &euro;
        </td>

        <td align="right">
            '.Translator::numberToLocale($totale_manodopera, 2).' &euro;
        </td>';
} else {
    echo '
        <td align="right">-</td>
        <td align="right">-</td>';
}

echo '
    </tr>';

// Ore di viaggio
if ($totale_km > 0) {
    echo '
    <tr>
        <td></td>

        <td>
            '.tr('Km / viaggio').'
        </td>

        <td align="center">
            '.Translator::numberToLocale($totale_km, 2).' km
        </td>';

    if ($visualizza_costi) {
        echo '
        <td align="right">
        	'.Translator::numberToLocale($totale_costo_km, 2).' &euro;
        </td>

        <td align="right">
        	'.Translator::numberToLocale($totale_viaggio, 2).' &euro;
        </td>';
    } else {
        echo '
        <td align="right">-</td>
        <td align="right">-</td>';
    }
    echo '
    </tr>';
}

// Subtotale manodopera + viaggio
if ($visualizza_costi) {
    echo '
    <tr>
        <td colspan="4" align="right">
            <b>'.tr('Totale intervento', [], ['upper' => true]).':</b>
        </td>

        <td align="right" bgcolor="#dddddd">
            <b>'.Translator::numberToLocale($totale_intervento, 2).' &euro;</b>
        </td>
    </tr>';
}

echo '
</table>';

$totale = sum($totale);

// TOTALE COSTI FINALI
if ($visualizza_costi) {
    echo '
<br>

<nobreak>
<table class="table_values" cellspacing="0" cellpadding="0" style="table-layout:fixed; font-size:11px;">
    <col width="630"><col width="80">';

    // Totale imponibile
    echo '
    <tr>
        <td valign="middle" align="right">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <td align="right" bgcolor="#cccccc">
            <b>'.Translator::numberToLocale($totale, 2).' &euro;</b>
        </td>
    </tr>';

    // Eventuale sconto incondizionato
    if ($records[0]['sconto_globale'] > 0) {
        $prc = ($records[0]['tipo_sconto'] == 'PRC');
        $records[0]['sconto_globale'] = $prc ? $totale * $records[0]['sconto_globale'] / 100 : $records[0]['sconto_globale'];

        $sconto = Translator::numberToLocale($records[0]['sconto_globale'], ($prc ? 0 : 2)).($prc ? '%' : '&euro;');

        $totale = sum($totale, -$records[0]['sconto_globale']);

        echo '
    <tr>
        <td valign="middle" align="right">
            <b>'.tr('Sconto incondizionato', [], ['upper' => true]).':</b>
        </td>

        <td align="right" bgcolor="#cccccc">
            <b>-'.Translator::numberToLocale($sconto, 2).' &euro;</b>
        </td>
    </tr>';

        // Imponibile scontato
        echo '
    <tr>
        <td valign="middle" align="right">
            <b>'.tr('Imponibile scontato', [], ['upper' => true]).':</b>
        </td>

        <td align="right" bgcolor="#cccccc">
            <b>'.Translator::numberToLocale($totale, 2).' &euro;</b>
        </td>
    </tr>';
    }

    // Leggo iva da applicare
    $q1 = 'SELECT percentuale FROM co_iva WHERE id='.prepare(get_var('Iva predefinita'));
    $rs1 = $dbo->fetchArray($q1);
    $percentuale_iva = $rs1[0]['percentuale'];

    $iva = ($totale / 100 * $percentuale_iva);

    // IVA
    // Totale intervento
    echo '
    <tr>
        <td valign="middle" align="right">
            <b>'.tr('Iva (_PRC_%)', [
                '_PRC_' => Translator::numberToLocale($percentuale_iva, 0),
            ], ['upper' => true]).':</b>
        </td>

        <td align="right" bgcolor="#cccccc">
            <b>'.Translator::numberToLocale($iva, 2).' &euro;</b>
        </td>
    </tr>';

    $totale = sum($totale, $iva);

    // TOTALE INTERVENTO
    echo '
    <tr>
    	<td valign="middle" align="right">
            <b>'.tr('Totale intervento', [], ['upper' => true]).':</b>
    	</td>
    	<td align="right" bgcolor="#cccccc">
    		<b>'.Translator::numberToLocale($totale, 2).' &euro;</b>
    	</td>
    </tr>
</table>
</nobreak>';
}

//  timbro e firma
if ($records[0]['firma_file'] != '') {
    $firma = '<img src="'.$docroot.'/files/interventi/'.$records[0]['firma_file'].'" style="width:70mm;">';
} else {
    $firma = '';
}

echo '
<br>

<table border="0" cellspacing="0" cellpadding="0" style="table-layout:fixed;">
    <col width="454"><col width="280">
    <tr>
        <td align="left" valign="middle">
            <b>'.tr('Si dichiara che i lavori sono stati eseguiti ed i materiali installati').'.</b><br>
            '.tr('I dati del ricevente verrano trattati in base al D.lgs n. 196/2003').'.
        </td>
        <td align="center" valign="bottom" style="border:1px solid #888; height:20mm; font-size:8pt;">
            '.$firma.'<br>
            <i>('.tr('Timbro e firma leggibile').'.)</i>
        </td>
    </tr>
</table>';
