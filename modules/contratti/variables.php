<?php

$r = $dbo->fetchOne('SELECT *,
    an_anagrafiche.email,
    an_anagrafiche.ragione_sociale
FROM co_contratti INNER JOIN an_anagrafiche ON co_contratti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_contratti.id='.prepare($id_record));

// Variabili da sostituire
return [
    'email' => $r['email'],
    'ragione_sociale' => $r['ragione_sociale'],
    'numero' => $r['numero'],
    'descrizione' => $r['descrizione'],
    'data' => Translator::dateToLocale($r['data_bozza']),
    'id_anagrafica' => $r['idanagrafica'],
];
