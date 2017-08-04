<?php

include_once __DIR__.'/../../core.php';

include_once $docroot.'/modules/interventi/modutil.php';

$module_name = 'Interventi';

// carica intervento
$query = 'SELECT in_interventi.*, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idclientefinale) AS clientefinale, (SELECT numero FROM co_preventivi WHERE id=(SELECT idpreventivo FROM co_preventivi_interventi WHERE idintervento=in_interventi.id ORDER BY idpreventivo DESC LIMIT 0,1)) AS numero_preventivo, (SELECT SUM(prezzo_dirittochiamata) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_dirittochiamata`, (SELECT SUM(km) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_km`, (SELECT SUM(ore*prezzo_ore_unitario) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_ore_consuntivo`, (SELECT SUM(prezzo_km_consuntivo) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_km_consuntivo`, in_interventi.descrizione AS `descrizione_intervento`, richiesta FROM in_interventi INNER JOIN in_tipiintervento ON in_interventi.idtipointervento=in_tipiintervento.idtipointervento WHERE id='.prepare($idintervento).' '.Modules::getAdditionalsQuery('Interventi');
$records = $dbo->fetchArray($query);

$id_anagrafica = $records[0]['idanagrafica'];
$id_cliente = $records[0]['idanagrafica'];
$id_sede = $records[0]['idsede'];

// Leggo il nome del referente se selezionato da menu a tendina
if (!empty($records[0]['idreferente'])) {
    $rs2 = $dbo->fetchArray('SELECT * FROM an_referenti WHERE id='.prepare($records[0]['idreferente']));
    $referente = $rs2[0]['nome'];
} else {
    $referente = $records[0]['referente'].' '.$records[0]['telefono_referente'];
}

// Sostituzioni specifiche
// Imposta numerointervento-data-numerocommessa su intestazione
$report = str_replace('$intervento_numero$', $records[0]['codice'], $report);
$report = str_replace('$intervento_data$', Translator::dateToLocale($records[0]['data_richiesta']), $report);

if ($records[0]['numero_preventivo']) {
    $report = str_replace('$commessa_numero$', $records[0]['codice'], $report);
} else {
    $report = str_replace('$commessa_numero$', '&nbsp;', $report);
}
