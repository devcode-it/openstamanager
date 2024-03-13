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

$dir = get('dir');
$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

// Raggruppamento

$raggruppamenti = $dbo->fetchArray('
    SELECT 
        `data_competenza`, 
        DATE_FORMAT(`data_competenza`, \'%m-%Y\') AS periodo,
        SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
        SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS imponibile
    FROM 
        `co_iva`
        LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(\App::getLang()).')
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id` 
        INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` 
        INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        INNER JOIN `zz_segments` ON `co_documenti`.`id_segment` = `zz_segments`.`id`
    WHERE 
        `co_tipidocumento`.`dir` = '.prepare($dir).' AND `co_righe_documenti`.`is_descrizione` = 0 AND `idstatodocumento` NOT IN (SELECT `id_record` FROM `co_statidocumento_lang` WHERE `name` = "Bozza" OR `name` = "Annullata") AND `co_documenti`.`data_competenza` >= '.prepare($date_start).' AND `co_documenti`.`data_competenza` <= '.prepare($date_end).' AND `autofatture` = 0
    GROUP BY 
        `periodo`
    ORDER BY
        `periodo` ASC');
