<?php

$r = $dbo->fetchOne('SELECT *,
    (SELECT email FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=dt_ddt.idanagrafica) AS email
FROM dt_ddt WHERE id='.prepare($id_record));

// Variabili da sostituire
return [
    'email' => $r['email'],
    'numero' => empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'],
    'descrizione' => $r['descrizione'],
    'data' => Translator::dateToLocale($r['data']),
];
