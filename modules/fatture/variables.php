<?php

$r = $dbo->fetchOne('SELECT co_documenti.*,
	an_anagrafiche.email,
	an_anagrafiche.pec,
	an_anagrafiche.ragione_sociale,
	(SELECT pec FROM zz_smtps WHERE zz_smtps.id='.prepare($template['id_smtp']).') AS is_pec
FROM co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_documenti.id='.prepare($id_record));

// Variabili da sostituire
return [
    'email' => $r['is_pec'] ? $r['pec'] : $r['email'],
    'id_anagrafica' => $r['idanagrafica'],
    'ragione_sociale' => $r['ragione_sociale'],
    'numero' => empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'],
    'note' => $r['note'],
    'data' => Translator::dateToLocale($r['data']),
];
