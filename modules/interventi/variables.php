<?php

$r = $dbo->fetchOne('SELECT *,
    (SELECT MAX(orario_fine) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data_fine,
    (SELECT email FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=in_interventi.idanagrafica) AS email
FROM in_interventi WHERE id='.prepare($id_record));

// Variabili da sostituire
return [
    'email' => $r['email'],
    'numero' => $r['codice'],
    'richiesta' => $r['richiesta'],
    'descrizione' => $r['descrizione'],
    'data' => Translator::dateToLocale($r['data_richiesta']),
    'data richiesta' => Translator::dateToLocale($r['data_richiesta']),
    'data fine intervento' => empty($r['data_fine']) ? Translator::dateToLocale($r['data_richiesta']) : Translator::dateToLocale($r['data_fine']),
    'id_anagrafica' => $r['idanagrafica'],
];
