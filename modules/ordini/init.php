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

use Modules\Ordini\Ordine;

include_once __DIR__.'/../../core.php';

if (!empty($id_record)) {
    $ordine = Ordine::find($id_record);

    $record = $dbo->fetchOne('SELECT 
            `or_ordini`.*,
            `or_ordini`.`note`,
            `or_ordini`.`idpagamento`,
            `or_ordini`.`id` AS idordine,
            `or_ordini`.`idagente` AS idagente,
            `or_ordini`.`idstatoordine` AS idstatoordine,
            `or_statiordine_lang`.`title` AS stato,
            `or_tipiordine_lang`.`title` AS descrizione_tipodoc,
            `an_anagrafiche`.`tipo` AS tipo_anagrafica,
            `or_statiordine`.`completato` AS flag_completato
        FROM 
            `or_ordini` 
            LEFT JOIN `or_statiordine` ON `or_ordini`.`idstatoordine`=`or_statiordine`.`id`
            LEFT JOIN `or_statiordine_lang` ON (`or_statiordine_lang`.`id_record`=`or_statiordine`.`id` AND `or_statiordine_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
            INNER JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica`=`an_anagrafiche`.`idanagrafica`
            INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine`=`or_tipiordine`.`id`
            LEFT JOIN `or_tipiordine_lang` ON (`or_tipiordine_lang`.`id_record`=`or_tipiordine`.`id` AND `or_tipiordine_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
        WHERE 
            `or_ordini`.`id`='.prepare($id_record));

    $elementi = $dbo->fetchArray('
        SELECT 
            `co_documenti`.`id`, 
            `co_documenti`.`data`, 
            `co_documenti`.`numero`, 
            `co_documenti`.`numero_esterno`, 
            `co_tipidocumento_lang`.`title` AS tipo_documento, 
            IF(`co_tipidocumento`.`dir` = \'entrata\', \'Fatture di vendita\', \'Fatture di acquisto\') AS modulo,
            GROUP_CONCAT(CONCAT(`original_id`, " - ", `qta`) SEPARATOR ", ") AS righe
        FROM 
            `co_documenti` 
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` 
            LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') 
            INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`
        WHERE 
            `co_righe_documenti`.`idordine` = '.prepare($id_record).'
        GROUP BY id

    UNION 
        SELECT 
            `dt_ddt`.`id`, 
            `dt_ddt`.`data`, 
            `dt_ddt`.`numero`, 
            `dt_ddt`.`numero_esterno`, 
            `dt_tipiddt_lang`.`title` AS tipo_documento, 
            IF(`dt_tipiddt`.`dir` = \'entrata\', \'Ddt in uscita\', \'Ddt in entrata\') as modulo,
            GROUP_CONCAT(CONCAT(`original_id`, " - ", `qta`) SEPARATOR ", ") AS righe
        FROM 
            `dt_ddt` 
            INNER JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt` 
            LEFT JOIN `dt_tipiddt_lang` ON (`dt_tipiddt_lang`.`id_record` = `dt_tipiddt`.`id` AND `dt_tipiddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') 
            INNER JOIN `dt_righe_ddt` ON `dt_righe_ddt`.`idddt` = `dt_ddt`.`id` 
        WHERE 
            `dt_righe_ddt`.`idordine` = '.prepare($id_record).'
        GROUP BY id

    UNION

    SELECT 
        `or_ordini`.`id`, 
        `or_ordini`.`data`, 
        `or_ordini`.`numero`, 
        `or_ordini`.`numero_esterno`, 
        `or_tipiordine_lang`.`title`, 
        IF(`or_tipiordine`.`dir` = \'entrata\', \'Ordini cliente\', \'Ordini fornitore\') as modulo,
        GROUP_CONCAT(CONCAT(`original_id`, " - ", `qta`) SEPARATOR ", ") AS righe
    FROM 
        `or_ordini` 
        INNER JOIN `or_righe_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` 
        INNER JOIN `or_tipiordine` ON `or_tipiordine`.`id` = `or_ordini`.`idtipoordine` 
        LEFT JOIN `or_tipiordine_lang` ON (`or_tipiordine_lang`.`id_record` = `or_tipiordine`.`id` AND `or_tipiordine_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') 
    WHERE 
        (`or_righe_ordini`.`original_document_id` = '.prepare($id_record).' AND `or_righe_ordini`.`original_document_type` = \'Modules\\\\Ordini\\\\Ordine\')
    GROUP BY 
        id

    UNION

    SELECT 
        `in_interventi`.`id`, 
        `in_interventi`.`data_richiesta`, 
        `in_interventi`.`codice`, 
        NULL, 
        \'Attività\', 
        \'Interventi\' as modulo,
        GROUP_CONCAT(CONCAT(`original_id`, " - ", `qta`) SEPARATOR ", ") AS righe
    FROM `in_interventi` 
    JOIN `in_righe_interventi` ON `in_righe_interventi`.`idintervento` = `in_interventi`.`id` 
    WHERE (`in_righe_interventi`.`original_document_id` = '.prepare($id_record).' AND `in_righe_interventi`.`original_document_type` = \'Modules\\\\Ordini\\\\Ordine\') OR `in_interventi`.`id_ordine` = '.prepare($id_record).'
    GROUP BY id

    ORDER BY 
        `modulo`');

    $is_anagrafica_deleted = !$ordine->anagrafica;
}
