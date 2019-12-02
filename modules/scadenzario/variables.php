<?php

$r = $dbo->fetchOne('SELECT co_scadenziario.*, co_documenti.*,
	an_anagrafiche.email,
	an_anagrafiche.pec,
	an_anagrafiche.ragione_sociale,
    co_scadenziario.da_pagare - co_scadenziario.pagato AS totale,
	(SELECT pec FROM em_accounts WHERE em_accounts.id='.prepare($template['id_account']).') AS is_pec,
	(SELECT descrizione FROM co_pagamenti WHERE co_pagamenti.id = co_documenti.idpagamento) AS pagamento
FROM co_scadenziario
    INNER JOIN co_documenti ON co_documenti.id = co_scadenziario.iddocumento
    INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica
WHERE co_scadenziario.pagato != co_scadenziario.da_pagare AND co_scadenziario.iddocumento = (SELECT iddocumento FROM co_scadenziario s WHERE id='.prepare($id_record).')');

$logo_azienda = str_replace(DOCROOT, ROOTDIR, App::filepath('templates/base|custom|/logo_azienda.jpg'));

// Variabili da sostituire
return [
    'email' => $r['is_pec'] ? $r['pec'] : $r['email'],
    'id_anagrafica' => $r['idanagrafica'],
    'ragione_sociale' => $r['ragione_sociale'],
    'numero' => empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'],
    'note' => $r['note'],
    'pagamento' => $r['pagamento'],
    'totale' => Translator::numberToLocale(abs($r['totale'])),
    'data_scadenza' => Translator::dateToLocale($r['scadenza']),
    'data' => Translator::dateToLocale($r['data']),
    'logo_azienda' => !empty($logo_azienda) ? '<img src="'.$logo_azienda.'" />' : '',
];
