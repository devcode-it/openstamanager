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

include_once __DIR__.'/../../core.php';

$module = Modules::get('Fatture di vendita');
$id_module = $module['id'];

$module_query = '
SELECT
    numero_esterno,
    an_anagrafiche.ragione_sociale,
    SUM(prezzo_unitario*qta) as \'Totale\',
    provvigione_percentuale,
    provvigione
FROM
    `co_documenti`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`iva`) AS `iva` FROM `co_righe_documenti` GROUP BY `iddocumento`) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
	LEFT JOIN an_anagrafiche as agenti ON agenti.idanagrafica = co_documenti.idagente
    LEFT JOIN co_righe_documenti ON co_righe_documenti.iddocumento = co_documenti.id
WHERE
    1=1 AND provvigione > 0
GROUP BY
    co_documenti.id
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC';

if (!empty(get('date_start'))) {
    $module_query = str_replace('1=1', '1=1 AND DATE_FORMAT(`data`, "%Y%m%d") >= "'.date('Ymd', strtotime(get('date_start'))).'"', $module_query);

    $date_start = get('date_start');
}

if (!empty(get('date_end'))) {
    $module_query = str_replace('1=1', '1=1 AND DATE_FORMAT(`data`, "%Y%m%d") <= "'.date('Ymd', strtotime(get('date_end'))).'"', $module_query);

    $date_end = get('date_end');
}

$module_query = str_replace('1=1', '1=1 AND co_documenti.idstatodocumento IN (SELECT id FROM co_statidocumento WHERE descrizione = "Pagato")', $module_query);

if (get('is_emessa') == 'true' && get('is_parz_pagata') == 'true') {
    $module_query = str_replace('1=1 AND co_documenti.idstatodocumento IN (SELECT id FROM co_statidocumento WHERE descrizione = "Pagato")', '1=1 AND co_documenti.idstatodocumento IN (SELECT id FROM co_statidocumento WHERE descrizione = "Pagato" OR descrizione = "Emessa" OR descrizione = "Parzialmente pagato")', $module_query);
} elseif (get('is_emessa') == 'true') {
    $module_query = str_replace('1=1 AND co_documenti.idstatodocumento IN (SELECT id FROM co_statidocumento WHERE descrizione = "Pagato")', '1=1 AND co_documenti.idstatodocumento IN (SELECT id FROM co_statidocumento WHERE descrizione = "Pagato" OR descrizione = "Emessa")', $module_query);
} elseif (get('is_parz_pagata') == 'true') {
    $module_query = str_replace('1=1 AND co_documenti.idstatodocumento IN (SELECT id FROM co_statidocumento WHERE descrizione = "Pagato")', '1=1 AND co_documenti.idstatodocumento IN (SELECT id FROM co_statidocumento WHERE descrizione = "Pagato" OR descrizione = "Parzialmente pagato")', $module_query);
}

$module_query = str_replace('1=1', '1=1 AND co_documenti.idagente='.prepare($id_record), $module_query);
$records = $dbo->fetchArray($module_query);
