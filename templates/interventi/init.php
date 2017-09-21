<?php

include_once __DIR__.'/../../core.php';

include_once $docroot.'/modules/interventi/modutil.php';

$module_name = 'Interventi';

// carica intervento
$query = 'SELECT in_interventi.*, (SELECT numero FROM co_contratti WHERE id=(SELECT idcontratto FROM co_righe_contratti WHERE idintervento=in_interventi.id)) AS numero_contratto, (SELECT numero FROM co_preventivi WHERE id=(SELECT idpreventivo FROM co_preventivi_interventi WHERE idintervento=in_interventi.id ORDER BY idpreventivo DESC LIMIT 0,1)) AS numero_preventivo, (SELECT SUM(prezzo_dirittochiamata) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_dirittochiamata`, (SELECT SUM(km) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_km`, (SELECT SUM(ore*prezzo_ore_unitario) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_ore_consuntivo`, (SELECT SUM(prezzo_km_consuntivo) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_km_consuntivo`, in_interventi.descrizione AS `descrizione_intervento`, richiesta FROM in_interventi INNER JOIN in_tipiintervento ON in_interventi.idtipointervento=in_tipiintervento.idtipointervento WHERE id='.prepare($id_record).' '.Modules::getAdditionalsQuery('Interventi');
$records = $dbo->fetchArray($query);

$costi_intervento = get_costi_intervento($id_record);

$id_cliente = $records[0]['idanagrafica'];
$id_sede = $records[0]['idsede'];

// Sostituzioni specifiche
// Imposta numerointervento-data-numerocommessa su intestazione
$custom = [
    'intervento_numero' => $records[0]['codice'],
    'intervento_data' => Translator::dateToLocale($records[0]['data_richiesta']),
    'commessa_numero' => !empty($records[0]['numero_preventivo']) ? $records[0]['codice'] : '&nbsp;',
];
