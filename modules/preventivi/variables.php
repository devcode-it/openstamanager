<?php

$r = $dbo->fetchOne('SELECT *,
    (SELECT email FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=co_preventivi.idanagrafica) AS email
FROM co_preventivi WHERE id='.prepare($id_record));

// Variabili da sostituire
return [
    'email' => $r['email'],
    'numero' => $r['numero'],
    'descrizione' => $r['descrizione'],
    'data' => Translator::dateToLocale($r['data_bozza']),
    'id_anagrafica' => $r['idanagrafica'],
];
