<?php

$rs = $dbo->fetchArray('SELECT *,
    (SELECT email FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=or_ordini.idanagrafica) AS email
FROM or_ordini WHERE id='.prepare($id_record));

// Risultato effettivo
$r = $rs[0];

// Variabili da sostituire
return [
    'email' => $r['email'],
    'numero' => empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'],
    'descrizione' => $r['descrizione'],
    'data' => Translator::dateToLocale($r['data']),
];
