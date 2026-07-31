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

use Modules\Anagrafiche\Anagrafica;

$r = $dbo->fetchOne('SELECT 
        `co_documenti`.*,
        `an_anagrafiche`.`pec`,
        IF ((`an_referenti`.`email` IS NOT NULL AND `an_referenti`.`email`!=""), `an_referenti`.`email`, `an_anagrafiche`.`email`) AS email,
        `an_anagrafiche`.`id_conto_cliente`,
        `an_anagrafiche`.`id_conto_fornitore`,
        `an_anagrafiche`.`ragione_sociale`,
        `an_referenti`.`nome`,
        `co_tipi_documento_lang`.`title` AS tipo_documento,
        `righe`.`totale`,
        (SELECT `pec` FROM `em_accounts` WHERE `em_accounts`.`id`='.prepare($template['id_account']).') AS is_pec
    FROM `co_documenti`
        INNER JOIN `an_anagrafiche` ON `co_documenti`.`id_anagrafica` = `an_anagrafiche`.`id`
        INNER JOIN `co_tipi_documento` ON `co_tipi_documento`.`id` = `co_documenti`.`id_tipo_documento`
        LEFT JOIN `co_tipi_documento_lang` ON (`co_tipi_documento_lang`.`id_record` = `co_tipi_documento`.`id` AND `co_tipi_documento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        LEFT JOIN `an_referenti` ON `an_referenti`.`id` = `co_documenti`.`id_referente`
        LEFT JOIN (SELECT id_documento, SUM(subtotale - sconto + iva) AS totale FROM `co_righe_documenti` GROUP BY id_documento) righe ON `co_documenti`.`id` = `righe`.`id_documento`
    WHERE 
        `co_documenti`.`id`='.prepare($id_record));

$banca = Modules\Banche\Banca::where('id_anagrafica', setting('Azienda predefinita'))
    ->where('predefined', 1)
    ->first();

$azienda = Modules\Anagrafiche\Anagrafica::find(setting('Azienda predefinita'));
$logo_azienda = $azienda->image;
if (empty($logo_azienda)) {
    $logo_azienda = str_replace(base_dir(), base_url(), App::filepath('templates/base|custom|/logo_azienda.jpg'));
    $logo_azienda = str_replace('\\', '/', $logo_azienda);
}

$r_user = Anagrafica::find(auth_osm()->getUser()['id_anagrafica']);
$r_company = Anagrafica::find(setting('Azienda predefinita'));

// Variabili da sostituire
return [
    'email' => $options['is_pec'] ? $r['pec'] : $r['email'],
    'id_anagrafica' => $r['id_anagrafica'],
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
    'sito_web' => $r_company['sito_web'],
    'nome_referente' => $r['nome'],
];
