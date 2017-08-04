<?php

include_once __DIR__.'/../../core.php';

include_once $docroot.'/modules/interventi/modutil.php';

/*
    Dati intervento
*/
$body .= '
<table class="table_values" cellspacing="0" cellpadding="0" style="table-layout:fixed;">
    <col width="400"><col width="310">

    <tr>
        <td align="left">
            '._('Cliente').': <b>'.$c_codiceanagrafica.' '.$c_ragionesociale.'</b><br>
            '._('Indirizzo').': <b>'.$c_indirizzo.'-'.$c_cap.' '.$c_citta.' ('.strtoupper($c_provincia).')</b><br>
        </td>
        <td align="left">
            '._('Referente').': <b>'.$referente.'</b>';
    if ($c_telefono != '') {
        $body .= '
        <br>'._('Telefono azienda').': <b>'.$c_telefono.'</b>';
    }
    if ($c_email != '') {
        $body .= '
        <br>'._('Email').': <b>'.$c_email.'</b>';
    }
    $body .= '
        </td>
    </tr>';

//  Richiesta
$body .= '
    <tr>
        <td align="left" colspan="2" valign="top"><b>'._('Richiesta').':</b></td>
    </tr>
    <tr>
        <td colspan="2" align="left" valign="top" style="height:5mm;">'.nl2br($records[0]['richiesta']).'</td>
    </tr>';

//  Descrizione
if ($records[0]['descrizione_intervento'] != '') {
    $body .= '
    <tr>
        <td colspan="2" align="left" valign="top"><b>'._('Descrizione').':</b></td>
    </tr>
    <tr>
        <td colspan="2" valign="top" align="left" style="height:5mm;">'.nl2br($records[0]['descrizione_intervento']).'</td>
    </tr>';
}
$body .= '
</table>';

$totale = [];

// MATERIALE UTILIZZATO

// Conteggio articoli utilizzati
$rs2 = $dbo->fetchArray('SELECT *, (SELECT codice FROM mg_articoli WHERE id=idarticolo) AS codice_art, SUM(qta) AS sumqta FROM `mg_articoli_interventi` GROUP BY idgruppo HAVING idintervento='.prepare($idintervento)." AND NOT idarticolo='0' ORDER BY idarticolo ASC");
if (!empty($rs2)) {
    $body .= '
<table class="table_values" cellspacing="0" cellpadding="0" style="font-size:11px; table-layout:fixed; border-color:#aaa;">
    <col width="90"><col width="254"><col width="54"><col width="80"><col width="80"><col width="80">

    <tr>
        <td align="center" colspan="6" valign="middle" style="font-size:11pt;" bgcolor="#cccccc">
            <b>'.strtoupper(_('Materiale utilizzato')).'</b>
        </td>
    </tr>

    <tr>
        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Codice').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Descrizione').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Q.tà').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Prezzo listino').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Sconto').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Subtot. netto').'</b>
        </td>
    </tr>';

    $totale_articoli = [];

    foreach ($rs2 as $r) {
        $body .= '
    <tr>';

        // Codice
        $body .= '
        <td valign="top">
            '.$r['codice_art'].'
        </td>';

        // Descrizione
        $body .= '
        <td valign="top">
            '.$r['descrizione'].'
        </td>';

        // Quantità
        $body .= '
        <td align="center" valign="top">
            '.Translator::numberToLocale($r['sumqta'], 2).' '.$r['um'].'
        </td>';

        // Prezzo unitario
        $body .= '
        <td align="right" valign="top">
            '.($visualizza_costi ? Translator::numberToLocale($r['prezzo_vendita'], 2).' &euro;' : '-').'
        </td>';

        // Sconto unitario
        if ($r['sconto_unitario'] > 0) {
            $sconto = Translator::numberToLocale($r['sconto_unitario']).($r['tipo_sconto'] == 'PRC' ? '%' : ' &euro;');
        } else {
            $sconto = '-';
        }

        $body .= '
        <td align="center" valign="top">
            '.($visualizza_costi ? $sconto : '-').'
        </td>';

        // Netto
        $netto = ($r['prezzo_vendita'] - $r['sconto']) * $r['sumqta'];

        $body .= '
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
        $body .= '
    <tr>
        <td colspan="5" align="right">
            <b>'.strtoupper(_('Totale materiale utilizzato')).':</b>
        </td>

        <td align="right" bgcolor="#dddddd">
            <b>'.Translator::numberToLocale($totale_articoli, 2).' &euro;</b>
        </td>
    </tr>';
    }

    $body .= '
</table>';
}

// FINE MATERIALE UTILIZZATO

// Conteggio SPESE AGGIUNTIVE
$rs2 = $dbo->fetchArray('SELECT * FROM in_righe_interventi WHERE idintervento='.prepare($idintervento).' ORDER BY id ASC');
if (!empty($rs2)) {
    $body .= '
<table class="table_values" cellspacing="0" cellpadding="0" style="table-layout:fixed; border-color:#aaa; font-size:11px;">
    <col width="90"><col width="254"><col width="54"><col width="80"><col width="80"><col width="80">

    <tr>
        <td align="center" colspan="6" valign="middle" style="font-size:11pt;" bgcolor="#cccccc">
            <b>'.strtoupper(_('Spese aggiuntive')).'</b>
        </td>
    </tr>

    <tr>
        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b></b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Descrizione').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Q.tà').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Prezzo listino').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Sconto').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Subtot. netto').'</b>
        </td>
    </tr>';

    $totale_righe = [];

    foreach ($rs2 as $r) {
        // Articolo
        $body .= '
    <tr>
        <td></td>

        <td>
            '.nl2br($r['descrizione']).'
        </td>';

        // Quantità
        $body .= '
        <td align="center">
            '.Translator::numberToLocale($r['qta'], 2).'
        </td>';

        // Prezzo unitario

        $body .= '
        <td align="right" valign="top">
            '.($visualizza_costi ? Translator::numberToLocale($r['prezzo_vendita'], 2).' &euro;' : '-').'
        </td>';

        // Sconto unitario
        if ($r['sconto_unitario'] > 0) {
            $sconto = Translator::numberToLocale($r['sconto_unitario']).($r['tipo_sconto'] == 'PRC' ? '%' : ' &euro;');
        } else {
            $sconto = '-';
        }

        $body .= '
        <td align="center" valign="top">
            '.($visualizza_costi ? $sconto : '-').'
        </td>';

        // Prezzo totale
        $netto = ($r['prezzo_vendita'] - $r['sconto']) * $r['qta'];

        $body .= '
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
        $body .= '
    <tr>
        <td colspan="5" align="right">
            <b>'.strtoupper(_('Totale spese aggiuntive')).':</b>
        </td>

        <td align="right" bgcolor="#dddddd">
            <b>'.Translator::numberToLocale($totale_righe, 2).' &euro;</b>
        </td>
    </tr>';
    }

    $body .= '
</table>';
}

// FINE SPESE AGGIUNTIVE

// ORE TECNICI + FIRMA
$body .= '
<table class="table_values" cellspacing="0" cellpadding="0" style="table-layout:fixed;">
    <col width="362"><col width="80"><col width="70"><col width="70"><col width="74">

    <tr>
        <td align="center" colspan="5" valign="middle" style="font-size:11pt;" bgcolor="#cccccc">
            <b>'.strtoupper(_('Ore tecnici')).'</b>
        </td>
    </tr>';

// INTESTAZIONE ELENCO TECNICI
$body .= '
    <tr>
        <td align="center" style="font-size:8pt;" bgcolor="#dddddd">
            <b>'._('Tecnico').'</b>
        </td>

        <td align="center" style="font-size:8pt;" bgcolor="#dddddd">
            <b>'._('Data').'</b>
        </td>

        <td align="center" style="font-size:8pt;" bgcolor="#dddddd">
            <b>'._('Dalle').'</b>
        </td>

        <td align="center" style="font-size:8pt;" bgcolor="#dddddd">
            <b>'._('Alle').'</b>
        </td>

        <td align="center" style="font-size:8pt;" bgcolor="#dddddd">
            <b>'._('Sconto').'</b>
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
    $body .= '
    <tr>';

    // nome tecnico
    $body .= '
    	<td align="left">
    	    '.$r['ragione_sociale'].'
    	</td>';

    // data
    $body .= '
    	<td align="center">
            '.Translator::dateToLocale($r['orario_inizio'], '-').'
    	</td>';

    // ora inizio
    $body .= '
    	<td align="center">
            '.Translator::timeToLocale($r['orario_inizio'], '-').'
    	</td>';

    // ora fine
    $body .= '
    	<td align="center">
            '.Translator::timeToLocale($r['orario_fine'], '-').'
    	</td>';

    // Sconto
    $body .= '
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

$body .= '
</table>';

// ore lavorate
$body .= '
<table class="table_values" cellspacing="0" cellpadding="0" style="table-layout:fixed; font-size:11px;">
    <col width="90"><col width="326"><col width="80"><col width="80"><col width="80">

    <tr>
        <td style="font-size:8pt;" align="center" bgcolor="#dedede"></td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Descrizione').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Q.tà').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Prezzo listino').'</b>
        </td>

        <td style="font-size:8pt;" align="center" bgcolor="#dedede">
            <b>'._('Subtot. netto').'</b>
        </td>
    </tr>';

// Ore lavoro
$body .= '
    <tr>
        <td></td>

        <td>
            '._('Ore tecnici').'
        </td>

        <td align="center">
            '.Translator::numberToLocale($totale_ore, 2).' ore
        </td>';

if ($visualizza_costi) {
    $body .= '
        <td align="right">
            '.Translator::numberToLocale($totale_costo_ore, 2).' &euro;
        </td>

        <td align="right">
            '.Translator::numberToLocale($totale_manodopera, 2).' &euro;
        </td>';
} else {
    $body .= '
        <td align="right">-</td>
        <td align="right">-</td>';
}

$body .= '
    </tr>';

// Ore di viaggio
if ($totale_km > 0) {
    $body .= '
    <tr>
        <td></td>

        <td>
            '._('Km / viaggio').'
        </td>

        <td align="center">
            '.Translator::numberToLocale($totale_km, 2).' km
        </td>';

    if ($visualizza_costi) {
        $body .= '
        <td align="right">
        	'.Translator::numberToLocale($totale_costo_km, 2).' &euro;
        </td>

        <td align="right">
        	'.Translator::numberToLocale($totale_viaggio, 2).' &euro;
        </td>';
    } else {
        $body .= '
        <td align="right">-</td>
        <td align="right">-</td>';
    }
    $body .= '
    </tr>';
}

// Subtotale manodopera + viaggio
if ($visualizza_costi) {
    $body .= '
    <tr>
        <td colspan="4" align="right">
            <b>'.strtoupper(_('Totale intervento')).':</b>
        </td>

        <td align="right" bgcolor="#dddddd">
            <b>'.Translator::numberToLocale($totale_intervento, 2).' &euro;</b>
        </td>
    </tr>';
}

$body .= '
</table>';

$totale = sum($totale);

// TOTALE COSTI FINALI
if ($visualizza_costi) {
    $body .= '
<br>

<nobreak>
<table class="table_values" cellspacing="0" cellpadding="0" style="table-layout:fixed; font-size:11px;">
    <col width="630"><col width="80">';

    // Totale imponibile
    $body .= '
    <tr>
        <td valign="middle" align="right">
            <b>'.strtoupper(_('Imponibile')).':</b>
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

        $body .= '
    <tr>
        <td valign="middle" align="right">
            <b>'.strtoupper(_('Sconto incondizionato')).':</b>
        </td>

        <td align="right" bgcolor="#cccccc">
            <b>-'.Translator::numberToLocale($sconto, 2).' &euro;</b>
        </td>
    </tr>';

        // Imponibile scontato
        $body .= '
    <tr>
        <td valign="middle" align="right">
            <b>'.strtoupper(_('Imponibile scontato')).':</b>
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
    $body .= '
    <tr>
        <td valign="middle" align="right">
            <b>'.strtoupper(_('Iva')).' ('.Translator::numberToLocale($percentuale_iva, 0).'%):</b>
        </td>

        <td align="right" bgcolor="#cccccc">
            <b>'.Translator::numberToLocale($iva, 2).' &euro;</b>
        </td>
    </tr>';

    $totale = sum($totale, $iva);

    // TOTALE INTERVENTO
    $body .= '
    <tr>
    	<td valign="middle" align="right">
            <b>'.strtoupper(_('Totale intervento')).':</b>
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

$body .= '
<br>

<table border="0" cellspacing="0" cellpadding="0" style="table-layout:fixed;">
    <col width="454"><col width="280">
    <tr>
        <td align="left" valign="middle">
            <b>'._('Si dichiara che i lavori sono stati eseguiti ed i materiali installati').'.</b><br>
            '._('I dati del ricevente verrano trattati in base al D.lgs n. 196/2003').'.
        </td>
        <td align="center" valign="bottom" style="border:1px solid #888; height:20mm; font-size:8pt;">
            '.$firma.'<br>
            <i>('._('Timbro e firma leggibile').'.)</i>
        </td>
    </tr>
</table>';

$report_name = 'intervento_'.$idintervento.'.pdf';
