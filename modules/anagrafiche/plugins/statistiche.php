<?php

include_once __DIR__.'/../../../core.php';

// Interventi
$rsi = $dbo->fetchArray('SELECT ragione_sociale, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data, (SELECT SUM(prezzo_ore_consuntivo+prezzo_km_consuntivo+prezzo_dirittochiamata) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS totale FROM in_interventi INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE in_interventi.idanagrafica='.prepare($id_record));

$totale_interventi = 0;
$data_start = strtotime("now");

for ($i = 0; $i < count($rsi); ++$i) {
    $totale_interventi += $rsi[$i]['totale'];

    // Calcolo data più bassa per la ricerca
    if (strtotime($rsi[$i]['data']) < $data_start) {
        $data_start = strtotime($rsi[$i]['data']);
    }
}
echo '
	<div class="row">
		<div class="col-xs-12 col-md-6">
			<div class="box box-info">
				<div class="box-header with-border">
					<h3 class="box-title">'._('Interventi').'</h3>
				</div>
				<div class="box-body">';
if (count($rsi) > 0) {
    echo '
					<p>'.str_replace(['_NUMBER_', '_EUR_'], [count($rsi), Translator::numberToLocale($totale_interventi)], _('Sono stati svolti <strong>_NUMBER_ interventi</strong> per un totale di _EUR_ &euro;')).'</p>
					<p><a href="'.$rootdir.'/controller.php?id_module='.Modules::getModule('Interventi')['id'].'&search_Ragione-sociale='.$rsi[0]['ragione_sociale'].'">'._('Visualizza').' <i class="fa fa-chevron-right"></i></a></p>';
} else {
    echo '
					<p>'._('Nessun intervento').'.</p>';
}

echo '
				</div>
			</div>
		</div>';

// Preventivi
$rsi = $dbo->fetchArray('SELECT data_accettazione AS data, ragione_sociale, budget FROM co_preventivi INNER JOIN an_anagrafiche ON co_preventivi.idanagrafica=an_anagrafiche.idanagrafica WHERE co_preventivi.idanagrafica='.prepare($id_record));

$totale_preventivi = 0;
$data_start = strtotime("now");

for ($i = 0; $i < count($rsi); ++$i) {
    $totale_preventivi += $rsi[$i]['budget'];

    // Calcolo data più bassa per la ricerca
    if (strtotime($rsi[$i]['data']) < $data_start) {
        $data_start = strtotime($rsi[$i]['data']);
    }
}
echo '
		<div class="col-xs-12 col-md-6">
			<div class="box box-info">
				<div class="box-header with-border">
					<h3 class="box-title">'._('Preventivi').'</h3>
				</div>
				<div class="box-body">';
if (count($rsi) > 0) {
    echo '
					<p>'.str_replace(['_NUMBER_', '_EUR_'], [count($rsi), Translator::numberToLocale($totale_preventivi)], _('Si è lavorato per <strong>_NUMBER_ preventivi</strong> per un totale di _EUR_ &euro;')).'</p>
					<p><a href="'.$rootdir.'/controller.php?id_module='.Modules::getModule('Preventivi')['id'].'&search_Cliente='.$rsi[0]['ragione_sociale'].'">'._('Visualizza').' <i class="fa fa-chevron-right"></i></a></p>';
} else {
    echo '
					<p>'._('Nessun preventivo').'.</p>';
}

echo '
				</div>
			</div>
		</div>
	</div>';

// Contratti
$rsi = $dbo->fetchArray('SELECT data_accettazione AS data, ragione_sociale, budget FROM co_contratti INNER JOIN an_anagrafiche ON co_contratti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_contratti.idanagrafica='.prepare($id_record));

$totale_contratti = 0;
$data_start = strtotime(date('Ymd'));

for ($i = 0; $i < count($rsi); ++$i) {
    $totale_contratti += $rsi[$i]['budget'];

    // Calcolo data più bassa per la ricerca
    if (strtotime($rsi[$i]['data']) < $data_start) {
        $data_start = strtotime($rsi[$i]['data']);
    }
}
echo '
	<div class="row">
		<div class="col-xs-12 col-md-6">
			<div class="box box-info">
				<div class="box-header with-border">
					<h3 class="box-title">'._('Contratti').'</h3>
				</div>
				<div class="box-body">';
if (count($rsi) > 0) {
    echo '
					<p>'.str_replace(['_NUMBER_', '_EUR_'], [count($rsi), Translator::numberToLocale($totale_contratti)], _('Si è lavorato per <strong>_NUMBER_ contratti</strong> per un totale di _EUR_ &euro;')).'</p>
					<p><a href="'.$rootdir.'/controller.php?id_module='.Modules::getModule('Contratti')['id'].'&search_Cliente='.$rsi[0]['ragione_sociale'].'">'._('Visualizza').' <i class="fa fa-chevron-right"></i></a></p>';
} else {
    echo '
					<p>'._('Nessun contratto').'.</p>';
}
echo '
				</div>
			</div>
		</div>';

// Fatture
echo '
		<div class="col-xs-12 col-md-6">
			<div class="box box-info">
				<div class="box-header with-border">
					<h3 class="box-title">'._('Fatture').'</h3>
				</div>
				<div class="box-body">';
// Fatture di vendita
$rsi = $dbo->fetchArray("SELECT data, ragione_sociale, (SELECT SUM(subtotale+iva) FROM co_righe_documenti WHERE iddocumento=co_documenti.id) AS totale FROM co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir='entrata') AND co_documenti.idanagrafica=".prepare($id_record));

$totale_fatture_vendita = 0;
$data_start = strtotime("now");

for ($i = 0; $i < count($rsi); ++$i) {
    $totale_fatture_vendita += $rsi[$i]['totale'];

    // Calcolo data più bassa per la ricerca
    if (strtotime($rsi[$i]['data']) < $data_start) {
        $data_start = strtotime($rsi[$i]['data']);
    }
}
if (count($rsi) > 0) {
    echo '
					<p>'.str_replace(['_NUMBER_', '_EUR_'], [count($rsi), Translator::numberToLocale($totale_fatture_vendita)], _('Sono state emesse <strong>_NUMBER_ fatture di vendita</strong> per un totale di _EUR_ &euro;')).'</p>
					<p><a href="'.$rootdir.'/controller.php?id_module='.Modules::getModule('Fatture di vendita')['id'].'&search_Ragione-sociale='.$rsi[0]['ragione_sociale'].'">'._('Visualizza').' <i class="fa fa-chevron-right"></i></a></p>';
} else {
    echo '
					<p>'._('Nessuna fattura di vendita').'.</p>';
}

echo '
                    <hr>';

// Fatture di acquisto
$rsi = $dbo->fetchArray("SELECT data, ragione_sociale, (SELECT SUM(subtotale+iva) FROM co_righe_documenti WHERE iddocumento=co_documenti.id) AS totale FROM co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir='uscita') AND co_documenti.idanagrafica=".prepare($id_record));

$totale_fatture_acquisto = 0;
$data_start = strtotime("now");

for ($i = 0; $i < count($rsi); ++$i) {
    $totale_fatture_acquisto += $rsi[$i]['totale'];

    // Calcolo data più bassa per la ricerca
    if (strtotime($rsi[$i]['data']) < $data_start) {
        $data_start = strtotime($rsi[$i]['data']);
    }
}
if (count($rsi) > 0) {
    echo '
					<p>'.str_replace(['_NUMBER_', '_EUR_'], [count($rsi), Translator::numberToLocale($totale_fatture_acquisto)], _('Sono state registrate <strong>_NUMBER_ fatture di acquisto</strong> per un totale di _EUR_ &euro;')).'</p>
					<p><a href="'.$rootdir.'/controller.php?id_module='.Modules::getModule('Fatture di acquisto')['id'].'&dir=uscita&search_Ragione-sociale='.$rsi[0]['ragione_sociale'].'">'._('Visualizza').' <i class="fa fa-chevron-right"></i></a></p>';
} else {
    echo '
					<p>'._('Nessuna fattura di acquisto').'.</p>';
}
echo '
				</div>
			</div>
		</div>
	</div>';
