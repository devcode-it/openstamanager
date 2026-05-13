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

$giorni_promemoria = setting('Intervallo di giorni in anticipo per invio promemoria scadenza');

// Filtro per scadenze selezionate (se presente in sessione)
$filtro_scadenze_selezionate = '';
if (!empty($_SESSION['scadenzario_selected_ids'])) {
    $ids_selezionati = implode(',', array_map(intval(...), $_SESSION['scadenzario_selected_ids']));
    $filtro_scadenze_selezionate = ' AND `co_scadenzario`.`id` IN ('.$ids_selezionati.')';
}

$r = $dbo->fetchOne('SELECT 
        `co_scadenzario`.*, 
        `co_documenti`.*,
        `an_anagrafiche`.`email`,
        `an_anagrafiche`.`pec`,
        `an_anagrafiche`.`ragione_sociale`,
        `an_referenti`.`nome`,
        `co_scadenzario`.`da_pagare` - `co_scadenzario`.`pagato` AS totale,
        `title` AS pagamento,
        (SELECT GROUP_CONCAT(CONCAT("<li>",DATE_FORMAT(IF(`co_scadenzario`.`data_concordata`, `co_scadenzario`.`data_concordata`, `co_scadenzario`.`scadenza`),"%d/%m/%Y")," - ",FORMAT(`da_pagare` - `pagato`,2),"€ - ",`descrizione`,"</li>") SEPARATOR "<br>") FROM `co_scadenzario` LEFT JOIN (SELECT `id`, `ref_documento` FROM `co_documenti`)as nota ON `co_scadenzario`.`iddocumento` = `nota`.`ref_documento` WHERE IF(`co_scadenzario`.`data_concordata`, `co_scadenzario`.`data_concordata`, `co_scadenzario`.`scadenza`) < NOW() AND `iddocumento`!=0 AND `nota`.`id` IS NULL AND `da_pagare`>`pagato` AND `id_anagrafica`=`co_documenti`.`id_anagrafica`'.$filtro_scadenze_selezionate.' ORDER BY IF(`co_scadenzario`.`data_concordata`, `co_scadenzario`.`data_concordata`, `co_scadenzario`.`scadenza`)) AS scadenze_fatture_scadute,
        (SELECT GROUP_CONCAT(CONCAT("<li>",DATE_FORMAT(IF(`co_scadenzario`.`data_concordata`, `co_scadenzario`.`data_concordata`, `co_scadenzario`.`scadenza`),"%d/%m/%Y")," - ",FORMAT(`da_pagare`,2),"€ - ",`descrizione`,"</li>") SEPARATOR "<br>") FROM `co_scadenzario` LEFT JOIN (SELECT `id`, `ref_documento` FROM `co_documenti`)as nota ON `co_scadenzario`.`iddocumento` = `nota`.`ref_documento` WHERE `iddocumento`!=0 AND `nota`.`id` IS NULL AND `da_pagare`>`pagato` AND `id_anagrafica`=`co_documenti`.`id_anagrafica`'.$filtro_scadenze_selezionate.' AND IF(`co_scadenzario`.`data_concordata`, `co_scadenzario`.`data_concordata`, `co_scadenzario`.`scadenza`) = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL '.prepare($giorni_promemoria).' DAY),"%Y-%m-%d") ORDER BY IF(`co_scadenzario`.`data_concordata`, `co_scadenzario`.`data_concordata`, `co_scadenzario`.`scadenza`)) AS scadenze_fatture_promemoria
    FROM `co_scadenzario`
        INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_scadenzario`.`iddocumento`
        LEFT JOIN `co_pagamenti` ON `co_pagamenti`.`id` = `co_documenti`.`id_pagamento`
        LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        LEFT JOIN `em_accounts` ON `em_accounts`.`id` = '.prepare($template['id_account']).'
        INNER JOIN `an_anagrafiche` ON `co_documenti`.`id_anagrafica` = `an_anagrafiche`.`id` 
        LEFT JOIN `an_referenti` ON `an_referenti`.`id_anagrafica` = `an_anagrafiche`.`id`
    WHERE 
        `co_scadenzario`.`da_pagare` > `co_scadenzario`.`pagato` AND `co_scadenzario`.`iddocumento` = (SELECT `iddocumento` FROM `co_scadenzario` s WHERE `id`='.prepare($id_record).')
    GROUP BY iddocumento');

$logo_azienda = str_replace(base_dir(), base_path_osm(), App::filepath('templates/base|custom|/logo_azienda.jpg'));

// Variabili da sostituire
return [
    'email' => $options['is_pec'] ? $r['pec'] : $r['email'],
    'id_anagrafica' => $r['id_anagrafica'],
    'ragione_sociale' => $r['ragione_sociale'],
    'numero' => empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'],
    'note' => $r['note'],
    'pagamento' => $r['pagamento'],
    'totale' => $r['totale'] ? Translator::numberToLocale(abs($r['totale'])) : 0,
    'data_scadenza' => Translator::dateToLocale($r['scadenza']),
    'data' => Translator::dateToLocale($r['data']),
    'logo_azienda' => !empty($logo_azienda) ? '<img src="'.$logo_azienda.'" />' : '',
    'nome_referente' => $r['nome'],
    'scadenze_fatture_scadute' => $r['scadenze_fatture_scadute'],
    'scadenze_fatture_promemoria' => $r['scadenze_fatture_promemoria'],
];
