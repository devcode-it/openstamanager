<?php

include_once __DIR__.'/../../core.php';

$module_name = 'Interventi';

// carica info ordine servizio
$idintervento = save($_GET['idintervento']);
$query = "SELECT *, (SELECT CONCAT_WS('-', codice, ragione_sociale ) FROM an_anagrafiche WHERE idanagrafica=(SELECT idtecnico FROM in_interventi_tecnici WHERE idintervento=co_ordiniservizio.idintervento LIMIT 0,1)) AS tecnico, (SELECT data FROM in_interventi WHERE id=co_ordiniservizio.idintervento) AS data_intervento FROM co_ordiniservizio WHERE idintervento=".prepare($idintervento).' '.Modules::getAdditionalsQuery('Interventi');
$rs = $dbo->fetchArray($query);
$idcliente = $rs[0]['idanagrafica'];
$data_intervento = $rs[0]['data_intervento'];

$copia_centrale = $rs[0]['copia_centrale'];
$copia_cliente = $rs[0]['copia_cliente'];
$copia_amministratore = $rs[0]['copia_amministratore'];
$funzionamento_in_sicurezza = $rs[0]['funzionamento_in_sicurezza'];

// carica report html
$report = file_get_contents($docroot.'/templates/interventi_ordiniservizio/intervento.html');
$body = file_get_contents($docroot.'/templates/interventi_ordiniservizio/intervento_body.html');

include_once $docroot.'/templates/pdfgen_variables.php';

/*
    Dati intervento
*/
$body .= "<table class=\"table_values\" cellspacing=\"0\" border=\"0\" cellpadding=\"0\" style=\"width:100%; table-layout:fixed; border-color:#aaa;\">\n";

// Titolo
$body .= "<tr><td align=\"center\" colspan=\"2\" valign=\"middle\" style=\"font-size:14pt;\" bgcolor=\"#dddddd\"><b>Programmazione della manutenzione periodica</b></td></tr>\n";

// Titolo "ordine di servizio" e tecnico
$body .= "<tr>\n";
$body .= '<td style="width:50%;">ORDINE DI SERVIZIO Num. '.$rs[0]['id']."</td>\n";
$body .= '<td style="width:50%;">TECNICO: '.$rs[0]['tecnico']."</td>\n";
$body .= "</tr>\n";

$body .= "</table>\n\n\n";

/*
    Dati intestazione doppia
*/
// Info contratto
$rs2 = $dbo->fetchArray('SELECT * FROM co_contratti WHERE id='.prepare($rs[0]['idcontratto']));
$body .= "<table class=\"table_values\" cellspacing=\"0\" border=\"0\" cellpadding=\"0\" style=\"width:100%; table-layout:fixed; border-color:#aaa;\">\n";

// Informazioni a sinistra
$body .= "<tr>\n";
$body .= "<td style=\"width:50%;\" valign=\"top\">\n";
$body .= '	<b>Contratto num. '.$rs2[0]['numero'].":</b><br/>\n";
$body .= '	durata dal '.Translator::dateToLocale($rs2[0]['data_accettazione']).' al '.Translator::dateToLocale($rs2[0]['data_conclusione'])."<br/>\n";
$body .= '	Tipologia: '.$rs2[0]['nome']."<br/><br/>\n";

// Info impianto
$rs3 = $dbo->fetchArray('SELECT * FROM my_impianti WHERE id='.prepare($rs[0]['id']));
$body .= "	<b>Impianto:</b><br/>\n";
$body .= '	Matricola: '.$rs3[0]['matricola']."<br/>\n";
$body .= '	Tipologia: '.$rs3[0]['nome']."<br/>\n";
$body .= '	Data di installazione: '.Translator::dateToLocale($rs3[0]['data'])."<br/>\n";
$body .= '	Ubicazione: '.$rs3[0]['ubicazione']."<br/>\n";
$body .= '	Scala: '.$rs3[0]['scala']."<br/>\n";
$body .= '	Piano: '.$rs3[0]['piano']."<br/><br/>\n";

$body .= "	<b>Lavori da eseguire nel periodo:</b><br/>\n";
$body .= '	dal 01/'.date('m/Y', strtotime($rs[0]['data_scadenza'])).' al '.date('t/m/Y', strtotime($rs[0]['data_scadenza']))."<br/><br/>\n";
$body .= "</td>\n";

/*
    Info cliente
*/
$body .= "<td style=\"width:50%;\" valign=\"top\">\n";

// Sede impianto
$ripeti = true;
$rs2 = $dbo->fetchArray('SELECT * FROM an_sedi WHERE id=(SELECT idsede FROM my_impianti WHERE id='.prepare($rs[0]['id']).')');

if ($rs2[0]['indirizzo'] != '') {
    $body .= "	 <b>Indirizzo impianto:</b><br/>\n";
    $body .= '	'.$rs2[0]['nomesede']."<br/>\n";
    $body .= '	'.$rs2[0]['indirizzo']."<br/>\n";
    $body .= '	'.$rs2[0]['cap'].' '.$rs2[0]['citta'].' '.$rs2[0]['provincia']."<br/><br/>\n";
    $ripeti = false;
}

$rs2 = $dbo->fetchArray('SELECT * FROM an_anagrafiche WHERE idanagrafica=(SELECT idanagrafica FROM in_interventi WHERE id='.prepare($idintervento).')');

if ($ripeti) {
    $body .= "	<b>Indirizzo impianto:</b><br/>\n";
    $body .= '	'.$rs2[0]['indirizzo']."<br/>\n";
    $body .= '	'.$rs2[0]['cap'].' '.$rs2[0]['citta'].' '.$rs2[0]['provincia']."<br/>\n";
    $body .= '	Telefono: '.$rs2[0]['telefono']."<br/>\n";
    $body .= '	Email: '.$rs2[0]['email']."<br/><br/>\n";

    $body .= "	<b>Cliente:</b><br/>\n";
    $body .= '	'.$rs2[0]['indirizzo']."<br/>\n";
    $body .= '	'.$rs2[0]['cap'].' '.$rs2[0]['citta'].' '.$rs2[0]['provincia']."<br/>\n";
    $body .= '	Telefono: '.$rs2[0]['telefono']."<br/>\n";
    $body .= '	Email: '.$rs2[0]['email']."<br/>\n";
} else {
    $body .= "	<b>Cliente</b><br/>\n";
    $body .= '	'.$rs2[0]['ragione_sociale']."<br/>\n";
    $body .= '	'.$rs2[0]['indirizzo']."<br/>\n";
    $body .= '	'.$rs2[0]['cap'].' '.$rs2[0]['citta'].' '.$rs2[0]['provincia']."<br/>\n";
    $body .= '	Telefono: '.$rs2[0]['telefono']."<br/>\n";
    $body .= '	Email: '.$rs2[0]['email']."<br/>\n";
}

$body .= "</td></tr>\n";
$body .= "</table><br/>\n\n\n";

/*
    Elenco voci di servizio
*/
$rs = $dbo->fetchArray('SELECT * FROM co_ordiniservizio_vociservizio WHERE idordineservizio=(SELECT id FROM co_ordiniservizio WHERE idintervento='.prepare($idintervento).' LIMIT 0,1) ORDER BY categoria ASC');

$body .= "<table class=\"table_values\" cellspacing=\"0\" border=\"0\" cellpadding=\"10\" style=\"width:100%; table-layout:fixed; border-color:#aaa;\">\n";
$body .= "<col width='60'><col width='16'><col width='16'><col width='77'>\n";
$body .= "<tr><td style='background:#ccc; width:60mm;'><b>VERIFICHE</b></td>\n";
$body .= "<td style='background:#ccc; width:16mm;' align='center'><small><small><b>ESEGUITO</b></small></small></td>\n";
$body .= "<td style='background:#ccc; width:16mm;' align='center'><small><small><b>NON&nbsp;ESEGUITO</b></small></small></td>\n";
$body .= "<td style='background:#ccc; width:77mm;'><b>NOTE</b></td></tr>\n";

$prev_cat = '';

for ($i = 0; $i < sizeof($rs); ++$i) {
    if ($rs[$i]['eseguito'] == '1') {
        $eseguito_si = "<span style='font-size:24px;'>x</span>";
        $eseguito_no = '';
    } elseif ($rs[$i]['eseguito'] == '-1') {
        $eseguito_si = '';
        $eseguito_no = "<span style='font-size:24px;'>x</span>";
    } else {
        $eseguito_si = '';
        $eseguito_no = '';
    }

    if ($prev_cat != $rs[$i]['categoria']) {
        $body .= "<tr style='background:#ddd;'><td colspan='4'><b>".$rs[$i]['categoria']."</b></td></tr>\n";
    }

    $body .= "<tr><td valign='top'>".$rs[$i]['voce']."&nbsp; </td>\n";
    $body .= "<td align='center' valign='top'>".$eseguito_si."&nbsp; </td>\n";
    $body .= "<td align='center' valign='top'>".$eseguito_no."&nbsp; </td>\n";
    $body .= "<td align='left' valign='top'>".$rs[$i]['note']."&nbsp; </td></tr>\n";

    $prev_cat = $rs[$i]['categoria'];
}

$body .= "</table><br/>\n\n\n";

/*
    Spunte e note
*/
$body .= "<table cellspacing=\"0\" border=\"0\" cellpadding=\"10\" style=\"width:100%; table-layout:fixed;\">\n";

// Copia centrale
if ($copia_centrale == '1') {
    $copia_centrale = 'S&Igrave;';
} else {
    $copia_centrale = 'NO';
}
$body .= "<tr><td style=\"width:62mm;\">\n";
$body .= "	Consegnata copia in centrale: <b>$copia_centrale</b>";
$body .= "</td>\n";

// Copia cliente
if ($copia_cliente == '1') {
    $copia_cliente = 'S&Igrave;';
} else {
    $copia_cliente = 'NO';
}
$body .= "<td style=\"width:62mm;\" align=\"center\">\n";
$body .= "	al cliente: <b>$copia_cliente</b>";
$body .= "</td>\n";

// Copia amministratore
if ($copia_amministratore == '1') {
    $copia_amministratore = 'S&Igrave;';
} else {
    $copia_amministratore = 'NO';
}
$body .= "<td style=\"width:62mm;\" align=\"right\">\n";
$body .= "	all'amministratore: <b>$copia_amministratore</b>";
$body .= "</td></tr>\n";

// Funzionamento in sicurezza
if ($funzionamento_in_sicurezza == '1') {
    $funzionamento_in_sicurezza = 'S&Igrave;';
} else {
    $funzionamento_in_sicurezza = 'NO';
}
$body .= "<tr><td colspan=\"3\" style=\"width:62mm;\">\n";
$body .= '	<br/>In data '.Translator::dateToLocale($data_intervento)." l'impianto pu&ograve; funzionare in sicurezza: <b>$funzionamento_in_sicurezza</b>";
$body .= "</td></tr>\n";

$body .= "</table>\n\n\n";

$report_name = 'ordine_servizio_intervento_'.$idintervento.'.pdf';
