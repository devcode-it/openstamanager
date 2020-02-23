<?php

$r = $dbo->fetchOne('SELECT *,
    an_anagrafiche.ragione_sociale,
    an_anagrafiche.email
FROM co_preventivi INNER JOIN an_anagrafiche ON co_preventivi.idanagrafica=an_anagrafiche.idanagrafica WHERE co_preventivi.id='.prepare($id_record));

// Variabili da sostituire
return [
    'email' => $r['email'],
    'numero' => $r['numero'],
    'ragione_sociale' => $r['ragione_sociale'],
    'descrizione' => $r['descrizione'],
    'data' => Translator::dateToLocale($r['data_bozza']),
    'id_anagrafica' => $r['idanagrafica'],
];
