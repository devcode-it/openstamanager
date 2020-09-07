<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

$r = $dbo->fetchOne('SELECT co_documenti.*,
	an_anagrafiche.email,
    an_anagrafiche.idconto_cliente,
    an_anagrafiche.idconto_fornitore,
	an_anagrafiche.pec,
	an_anagrafiche.ragione_sociale,
	co_tipidocumento.descrizione AS tipo_documento,
	(SELECT pec FROM em_accounts WHERE em_accounts.id='.prepare($template['id_account']).') AS is_pec
FROM co_documenti
    INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica
    INNER JOIN co_tipidocumento ON co_tipidocumento.id=co_documenti.idtipodocumento
WHERE co_documenti.id='.prepare($id_record));

if (!empty(setting('Logo stampe'))) {
    $logo_azienda = BASEURL.'/'.Models\Upload::where('filename', setting('Logo stampe'))->first()->fileurl;
} else {
    $logo_azienda = str_replace(DOCROOT, BASEURL, App::filepath('templates/base|custom|/logo_azienda.jpg'));
    $logo_azienda = str_replace('\\', '/', $logo_azienda);
}

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

$r_user = $dbo->fetchOne('SELECT * FROM an_anagrafiche WHERE idanagrafica='.prepare(Auth::user()['idanagrafica']));
$r_company = $dbo->fetchOne('SELECT * FROM an_anagrafiche WHERE idanagrafica='.prepare(setting('Azienda predefinita')));

// Variabili da sostituire
return [
    'email' => $r['is_pec'] ? $r['pec'] : $r['email'],
    'id_anagrafica' => $r['idanagrafica'],
    'ragione_sociale' => $r['ragione_sociale'],
    'numero' => empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'],
    'tipo_documento' => $r['tipo_documento'],
    'note' => $r['note'],
    'data' => Translator::dateToLocale($r['data']),
    'logo_azienda' => !empty($logo_azienda) ? '<img src="'.$logo_azienda.'" />' : '',
    'conto' => $conto,
    'conto_descrizione' => $conto_descrizione,
    'nome_utente' => $r_user['ragione_sociale'],
    'telefono_utente' => $r_user['cellulare'],
    'sito_web' => $r_company['sitoweb'],
];
