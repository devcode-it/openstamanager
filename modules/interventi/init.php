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

use Modules\Interventi\Intervento;

if (!empty($id_record)) {
    $intervento = Intervento::find($id_record);

    $record = $dbo->fetchOne('SELECT 
        `in_interventi`.*,
        `in_interventi`.`descrizione` AS descrizione,
        `in_interventi`.`codice` AS codice,
        `an_anagrafiche`.`tipo` AS tipo_anagrafica,
        `in_statiintervento`.`is_bloccato` AS flag_completato,
        `in_statiintervento`.`colore` AS colore,
        IF((`in_interventi`.`idsede_destinazione` = 0), `an_anagrafiche`.`idzona`, `an_sedi`.`idzona`) AS idzona,
        `in_interventi`.`idanagrafica` as idanagrafica,
        `in_interventi`.`id_preventivo` as idpreventivo,
        `in_interventi`.`id_contratto` as idcontratto,
        `in_interventi`.`id_ordine` as idordine
    FROM 
        `in_interventi`
        INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
        LEFT JOIN `an_sedi` ON `in_interventi`.`idsede_destinazione` = `an_sedi`.`id`
        INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento` = `in_statiintervento`.`id`
    WHERE 
        `in_interventi`.`id`='.prepare($id_record));

    $elementi = $dbo->fetchArray('SELECT 
        `co_documenti`.`id`, 
        `co_documenti`.`data`, 
        `co_documenti`.`numero`, 
        `co_documenti`.`numero_esterno`, 
        `co_tipidocumento_lang`.`title` AS tipo_documento, 
        IF(`co_tipidocumento`.`dir` = \'entrata\', \'Fatture di vendita\', \'Fatture di acquisto\') AS modulo,
        GROUP_CONCAT(CONCAT(`original_id`, " - ", `qta`) SEPARATOR ", ") AS righe
    FROM 
        `co_documenti` 
    INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id` 
    INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` 
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') 
    WHERE 
        (`co_righe_documenti`.`original_document_id` = '.prepare($id_record).' AND `co_righe_documenti`.`original_document_type` = \'Modules\\\\Interventi\\\\Intervento\')
    GROUP BY 
        id
    ORDER BY `modulo`');

    $is_anagrafica_deleted = !$intervento->anagrafica;
}
