<?php

$r = $dbo->fetchOne('SELECT *,
    an_anagrafiche.ragione_sociale,
    an_anagrafiche.email
FROM co_preventivi INNER JOIN an_anagrafiche ON co_preventivi.idanagrafica=an_anagrafiche.idanagrafica WHERE co_preventivi.id='.prepare($id_record));

$revisione = $dbo->fetchNum('SELECT * FROM co_preventivi WHERE master_revision = (SELECT master_revision FROM co_preventivi WHERE id = '.prepare($id_record).') AND id < '.prepare($id_record)) + 1;

// Variabili da sostituire
return [
    'email' => $r['email'],
    'numero' => $r['numero'],
    'ragione_sociale' => $r['ragione_sociale'],
    'descrizione' => $r['descrizione'],
    'data' => Translator::dateToLocale($r['data_bozza']),
    'id_anagrafica' => $r['idanagrafica'],
    'revisione' => $revisione,
];
