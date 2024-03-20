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

$r = $dbo->fetchOne('SELECT `co_scadenziario`.*, `co_documenti`.*,
	`an_anagrafiche`.`email`,
	`an_anagrafiche`.`pec`,
	`an_anagrafiche`.`ragione_sociale`,
    `an_referenti`.`nome`,
    `co_scadenziario`.`da_pagare` - `co_scadenziario`.`pagato` AS totale,
	`pec`,
	`name` AS pagamento,
    (SELECT GROUP_CONCAT(CONCAT("<li>",DATE_FORMAT(`scadenza`,"%d/%m/%Y")," - ",FORMAT(`da_pagare`,2),"€ - ",`descrizione`,"</li>") SEPARATOR "<br>") FROM `co_scadenziario` LEFT JOIN (SELECT `id`, `ref_documento` FROM `co_documenti`)as nota ON `co_scadenziario`.`iddocumento` = `nota`.`ref_documento` WHERE `scadenza` < NOW() AND `iddocumento`!=0 AND `nota`.`id` IS NULL AND `da_pagare`>`pagato` AND `idanagrafica`=`co_documenti`.`idanagrafica` ORDER BY `scadenza`) AS scadenze_fatture_scadute
FROM `co_scadenziario`
    INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_scadenziario`.`iddocumento`
    LEFT JOIN `co_pagamenti` ON `co_pagamenti`.`id` = `co_documenti`.`idpagamento`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
    LEFT JOIN `em_accounts` ON `em_accounts`.`id` = '.prepare($template['id_account']).'
    INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` 
    LEFT JOIN `an_referenti` ON `an_referenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
WHERE 
    `co_scadenziario`.`da_pagare` > `co_scadenziario`.`pagato` AND `co_scadenziario`.`iddocumento` = (SELECT `iddocumento` FROM `co_scadenziario` s WHERE `id`='.prepare($id_record).')');

$logo_azienda = str_replace(base_dir(), base_path(), App::filepath('templates/base|custom|/logo_azienda.jpg'));

// Variabili da sostituire
return [
    'email' => $options['is_pec'] ? $r['pec'] : $r['email'],
    'id_anagrafica' => $r['idanagrafica'],
    'ragione_sociale' => $r['ragione_sociale'],
    'numero' => empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'],
    'note' => $r['note'],
    'pagamento' => $r['pagamento'],
    'totale' => Translator::numberToLocale(abs($r['totale'])),
    'data_scadenza' => Translator::dateToLocale($r['scadenza']),
    'data' => Translator::dateToLocale($r['data']),
    'logo_azienda' => !empty($logo_azienda) ? '<img src="'.$logo_azienda.'" />' : '',
    'nome_referente' => $r['nome'],
    'scadenze_fatture_scadute' => $r['scadenze_fatture_scadute'],
];
