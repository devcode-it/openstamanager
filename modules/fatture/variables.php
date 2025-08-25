<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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

$r = $dbo->fetchOne('SELECT 
        `co_documenti`.*,
        `an_anagrafiche`.`pec`,
        IF ((`an_referenti`.`email` IS NOT NULL AND `an_referenti`.`email`!=""), `an_referenti`.`email`, `an_anagrafiche`.`email`) AS email,
        `an_anagrafiche`.`idconto_cliente`,
        `an_anagrafiche`.`idconto_fornitore`,
        `an_anagrafiche`.`ragione_sociale`,
        `an_referenti`.`nome`,
        `co_tipidocumento_lang`.`title` AS tipo_documento,
        `righe`.`totale`,
        (SELECT `pec` FROM `em_accounts` WHERE `em_accounts`.`id`='.prepare($template['id_account']).') AS is_pec
    FROM `co_documenti`
        INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
        INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        LEFT JOIN `an_referenti` ON `an_referenti`.`id` = `co_documenti`.`idreferente`
        LEFT JOIN (SELECT iddocumento, SUM(subtotale - sconto + iva) AS totale FROM `co_righe_documenti` GROUP BY iddocumento) righe ON `co_documenti`.`id` = `righe`.`iddocumento`
    WHERE 
        `co_documenti`.`id`='.prepare($id_record));

$banca = Modules\Banche\Banca::where('id_anagrafica', setting('Azienda predefinita'))
    ->where('predefined', 1)
    ->first();

if (!empty(setting('Logo stampe'))) {
    $logo_azienda = base_url().'/'.Models\Upload::where('filename', setting('Logo stampe'))->first()->fileurl;
} else {
    $logo_azienda = str_replace(base_dir(), base_url(), App::filepath('templates/base|custom|/logo_azienda.jpg'));
    $logo_azienda = str_replace('\\', '/', $logo_azienda);
}

$r_user = $dbo->fetchOne('SELECT * FROM an_anagrafiche WHERE idanagrafica='.prepare(Auth::user()['idanagrafica']));
$r_company = $dbo->fetchOne('SELECT * FROM an_anagrafiche WHERE idanagrafica='.prepare(setting('Azienda predefinita')));

// Variabili da sostituire
return [
    'email' => $options['is_pec'] ? $r['pec'] : $r['email'],
    'id_anagrafica' => $r['idanagrafica'],
    'ragione_sociale' => $r['ragione_sociale'],
    'numero' => empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'],
    'tipo_documento' => $r['tipo_documento'],
    'iban' => $banca->iban,
    'totale' => moneyFormat($r['totale']),
    'note' => $r['note'],
    'data' => Translator::dateToLocale($r['data']),
    'logo_azienda' => !empty($logo_azienda) ? '<img src="'.$logo_azienda.'" />' : '',
    'nome_utente' => $r_user['ragione_sociale'],
    'telefono_utente' => $r_user['cellulare'],
    'sito_web' => $r_company['sitoweb'],
    'nome_referente' => $r['nome'],
];
