<?php

$r = $dbo->fetchOne('SELECT *,
    (SELECT email FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=co_documenti.idanagrafica) AS email,
    (SELECT pec FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=co_documenti.idanagrafica) AS pec
FROM co_documenti WHERE id='.prepare($id_record));

// Variabili da sostituire
return [
    'email' => $template['name'] == 'Fattura Elettronica' ? $r['pec'] : $r['email'],
    'id_anagrafica' => $r['idanagrafica'],
    'numero' => empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'],
    'note' => $r['note'],
    'data' => Translator::dateToLocale($r['data']),
];
