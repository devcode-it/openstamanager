<?php

$r = $dbo->fetchOne('SELECT *,
    (SELECT email FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=or_ordini.idanagrafica) AS email
FROM or_ordini WHERE id='.prepare($id_record));

// Variabili da sostituire
return [
    'email' => $r['email'],
	'id_anagrafica' => $r['idanagrafica'],
    'numero' => empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'],
    'note' => $r['note'],
    'data' => Translator::dateToLocale($r['data']),
];
