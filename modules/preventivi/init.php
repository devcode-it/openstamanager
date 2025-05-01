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

if (!empty($id_record)) {
    $preventivo = Modules\Preventivi\Preventivo::with('stato')->find($id_record);

    $record = $dbo->fetchOne('SELECT 
            `co_preventivi`.*,
            `an_anagrafiche`.`tipo` AS tipo_anagrafica,
            `co_statipreventivi`.`is_fatturabile`,
            `co_statipreventivi`.`is_pianificabile`,
            `co_statipreventivi`.`is_bloccato`,
            `co_statipreventivi`.`is_revisionabile`,
            `co_statipreventivi_lang`.`title` AS stato
        FROM 
            `co_preventivi` 
            INNER JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica`=`an_anagrafiche`.`idanagrafica`
            LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato`=`co_statipreventivi`.`id`
            LEFT JOIN `co_statipreventivi_lang` ON (`co_preventivi`.`idstato`=`co_statipreventivi_lang`.`id_record` AND `co_statipreventivi_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
        WHERE 
            `co_preventivi`.`id`='.prepare($id_record));

    // Fatture, ordini collegate a questo preventivo
    $elementi = $dbo->fetchArray('
    SELECT 
        `co_documenti`.`id`, 
        `co_documenti`.`data`, 
        `co_documenti`.`numero`, 
        `co_documenti`.`numero_esterno`, 
        `co_tipidocumento_lang`.`title` AS tipo_documento, 
        IF(`co_tipidocumento`.`dir` = \'entrata\', \'Fatture di vendita\', \'Fatture di acquisto\') AS modulo,
        GROUP_CONCAT(CONCAT(`co_righe_documenti`.`original_id`, " - ", `co_righe_documenti`.`qta`) SEPARATOR ", ") AS righe,
        `co_statidocumento_lang`.`title` AS stato_documento
    FROM `co_documenti` 
    INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` 
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') 
    INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id` 
    LEFT JOIN co_statidocumento ON co_documenti.idstatodocumento=co_statidocumento.id LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE `co_righe_documenti`.`idpreventivo` = '.prepare($id_record).'
    GROUP BY id

    UNION

    SELECT 
        `or_ordini`.`id`, 
        `or_ordini`.`data`, 
        `or_ordini`.`numero`, 
        `or_ordini`.`numero_esterno`, 
        `or_tipiordine_lang`.`title`, 
        IF(`or_tipiordine`.`dir` = \'entrata\', \'Ordini cliente\', \'Ordini fornitore\'),
        GROUP_CONCAT(CONCAT(`original_id`, " - ", `qta`) SEPARATOR ", ") AS righe,
        `or_statiordine_lang`.`title` AS stato_documento
    FROM `or_ordini` 
    JOIN `or_righe_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` 
    INNER JOIN `or_tipiordine` ON `or_tipiordine`.`id` = `or_ordini`.`idtipoordine` 
    LEFT JOIN `or_tipiordine_lang` ON (`or_tipiordine_lang`.`id_record` = `or_tipiordine`.`id` AND `or_tipiordine_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') 
    LEFT JOIN or_statiordine ON or_ordini.idstatoordine=or_statiordine.id LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id` = `or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE `or_righe_ordini`.`idpreventivo` = '.prepare($id_record).'
    GROUP BY id

    UNION

    SELECT 
        `dt_ddt`.`id`, 
        `dt_ddt`.`data`, 
        `dt_ddt`.`numero`, 
        `dt_ddt`.`numero_esterno`, 
        `dt_tipiddt_lang`.`title`, 
        IF(`dt_tipiddt`.`dir` = \'entrata\', \'Ddt in uscita\', \'Ddt in entrata\'),
        GROUP_CONCAT(CONCAT(`original_id`, " - ", `qta`) SEPARATOR ", ") AS righe,
        `dt_statiddt_lang`.`title` AS stato_documento
    FROM `dt_ddt` 
    JOIN `dt_righe_ddt` ON `dt_righe_ddt`.`idddt` = `dt_ddt`.`id` 
    INNER JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt` 
    LEFT JOIN `dt_tipiddt_lang` ON (`dt_tipiddt_lang`.`id_record` = `dt_tipiddt`.`id` AND `dt_tipiddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') 
    LEFT JOIN dt_statiddt ON dt_ddt.idstatoddt=dt_statiddt.id LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt`.`id` = `dt_statiddt_lang`.`id_record` AND `dt_statiddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE `dt_righe_ddt`.`original_document_id` = '.prepare($id_record).' AND `dt_righe_ddt`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\'
    GROUP BY id

    UNION

    SELECT 
        `in_interventi`.`id`, 
        `in_interventi`.`data_richiesta`, 
        `in_interventi`.`codice`, 
        NULL, 
        \'Attività\', 
        \'Interventi\' ,
        GROUP_CONCAT(CONCAT(`original_id`, " - ", `qta`) SEPARATOR ", ") AS righe,
        `in_statiintervento_lang`.`title` AS stato_documento
    FROM `in_interventi` 
    JOIN `in_righe_interventi` ON `in_righe_interventi`.`idintervento` = `in_interventi`.`id` 
    LEFT JOIN in_statiintervento ON in_interventi.idstatointervento=in_statiintervento.id LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND `in_statiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE (`in_righe_interventi`.`original_document_id` = '.prepare($preventivo->id).' AND `in_righe_interventi`.`original_document_type` = '.prepare($preventivo::class).') OR `in_interventi`.`id_preventivo` = '.prepare($id_record).'
    GROUP BY id

    UNION

    SELECT 
        `co_contratti`.`id`, 
        `co_contratti`.`data_bozza`, 
        `co_contratti`.`numero`, 
        NULL,
        \'Contratti\', 
        \'Contratti\' AS modulo,
        GROUP_CONCAT(CONCAT(`co_righe_contratti`.`original_id`, " - ", `co_righe_contratti`.`qta`) SEPARATOR ", ") AS righe,
        `co_staticontratti_lang`.`title` AS stato_documento
    FROM `co_contratti` 
    INNER JOIN `co_righe_contratti` ON `co_righe_contratti`.`idcontratto` = `co_contratti`.`id` 
    LEFT JOIN co_staticontratti ON co_contratti.idstato=co_staticontratti.id LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE `co_righe_contratti`.`original_document_id` = '.prepare($id_record).' AND `co_righe_contratti`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\'
    GROUP BY id
    
    ORDER BY `modulo`');

    $is_anagrafica_deleted = !$preventivo->anagrafica;
}
