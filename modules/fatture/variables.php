<?php

$r = $dbo->fetchOne('SELECT co_documenti.*,
	an_anagrafiche.email,
    an_anagrafiche.idconto_cliente,
    an_anagrafiche.idconto_fornitore,
	an_anagrafiche.pec,
	an_anagrafiche.ragione_sociale,
	(SELECT pec FROM zz_smtps WHERE zz_smtps.id='.prepare($template['id_smtp']).') AS is_pec
FROM co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_documenti.id='.prepare($id_record));

$logo_azienda = str_replace(DOCROOT, BASEURL, App::filepath('templates/base|custom|/logo_azienda.jpg'));

//cliente
if ($r['idconto_cliente'] != '') {
    $conto = $r['idconto_cliente'];
    $conto_descrizione = $dbo->fetchOne('SELECT CONCAT ((SELECT numero FROM co_pianodeiconti2 WHERE id=co_pianodeiconti3.idpianodeiconti2), ".", numero, " ", descrizione) AS descrizione FROM co_pianodeiconti3 WHERE id='.prepare($conto))['descrizione'];
}
//Fornitore
elseif ($r['idconto_fornitore'] != '') {
    $conto = $r['idconto_fornitore'];
    $conto_descrizione = $dbo->fetchOne('SELECT CONCAT ((SELECT numero FROM co_pianodeiconti2 WHERE id=co_pianodeiconti3.idpianodeiconti2), ".", numero, " ", descrizione) AS descrizione FROM co_pianodeiconti3 WHERE id='.prepare($conto))['descrizione'];
}

$r_user = $dbo->fetchOne('SELECT * FROM an_anagrafiche WHERE idanagrafica='.Auth::user()['idanagrafica']);
$r_company = $dbo->fetchOne('SELECT * FROM an_anagrafiche WHERE idanagrafica='.prepare(setting('Azienda predefinita')));

// Variabili da sostituire
return [
    'email' => $r['is_pec'] ? $r['pec'] : $r['email'],
    'id_anagrafica' => $r['idanagrafica'],
    'ragione_sociale' => $r['ragione_sociale'],
    'numero' => empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'],
    'note' => $r['note'],
    'data' => Translator::dateToLocale($r['data']),
    'logo_azienda' => !empty($logo_azienda) ? '<img src="'.$logo_azienda.'" />' : '',
    'conto' => $conto,
    'conto_descrizione' => $conto_descrizione,
    'nome_utente' => $r_user['ragione_sociale'],
    'telefono_utente' => $r_user['cellulare'],
    'sito_web' => $r_company['sitoweb'],
];
