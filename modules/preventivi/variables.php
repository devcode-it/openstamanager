<?php

$rs = $dbo->fetchArray('SELECT *,
    (SELECT email FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=co_preventivi.idanagrafica) AS email
FROM co_preventivi WHERE id='.prepare($id_record));

// Risultato effettivo
$r = $rs[0];

// Variabili da sostituire
return [
    'email' => $r['email'],
    'numero' => $r['numero'],
    'descrizione' => $r['descrizione'],
    'data' => Translator::dateToLocale($r['data_bozza']),
];
