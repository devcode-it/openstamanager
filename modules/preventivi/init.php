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
            `co_stati_preventivi`.`is_fatturabile`,
            `co_stati_preventivi`.`is_pianificabile`,
            `co_stati_preventivi`.`is_bloccato`,
            `co_stati_preventivi`.`is_revisionabile`,
            `co_stati_preventivi_lang`.`title` AS stato
        FROM 
            `co_preventivi` 
            INNER JOIN `an_anagrafiche` ON `co_preventivi`.`id_anagrafica`=`an_anagrafiche`.`id`
            LEFT JOIN `co_stati_preventivi` ON `co_preventivi`.`id_stato`=`co_stati_preventivi`.`id`
            LEFT JOIN `co_stati_preventivi_lang` ON (`co_preventivi`.`id_stato`=`co_stati_preventivi_lang`.`id_record` AND `co_stati_preventivi_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
        WHERE 
            `co_preventivi`.`id`='.prepare($id_record));

    $elementi = $dbo->fetchArray('SELECT
        `co_documenti`.`id`,
        `co_documenti`.`data`,
        `co_documenti`.`numero`,
        `co_documenti`.`numero_esterno`,
        `co_tipi_documento_lang`.`title` AS tipo_documento,
        IF(`co_tipi_documento`.`dir` = \'entrata\', \'Fatture di vendita\', \'Fatture di acquisto\') AS modulo,
        GROUP_CONCAT(CONCAT(`co_righe_documenti`.`original_id`, " - ", `co_righe_documenti`.`qta`) SEPARATOR ", ") AS righe,
        `co_stati_documento_lang`.`title` AS stato_documento
    FROM
        `co_documenti`
        INNER JOIN `co_tipi_documento` ON `co_tipi_documento`.`id` = `co_documenti`.`id_tipo_documento`
        LEFT JOIN `co_tipi_documento_lang` ON (`co_tipi_documento_lang`.`id_record` = `co_tipi_documento`.`id` AND `co_tipi_documento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`id_documento` = `co_documenti`.`id`
        LEFT JOIN `co_stati_documento` ON `co_documenti`.`id_stato` = `co_stati_documento`.`id`
        LEFT JOIN `co_stati_documento_lang` ON (`co_stati_documento_lang`.`id_record` = `co_stati_documento`.`id` AND `co_stati_documento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE
        (`co_righe_documenti`.`original_document_id` = '.prepare($id_record).' AND `co_righe_documenti`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\')
        OR `co_righe_documenti`.`id_preventivo` = '.prepare($id_record).'
    GROUP BY `co_documenti`.`id`

    UNION

    SELECT
        `or_ordini`.`id`,
        `or_ordini`.`data`,
        `or_ordini`.`numero`,
        `or_ordini`.`numero_esterno`,
        `or_tipi_ordine_lang`.`title`,
        IF(`or_tipi_ordine`.`dir` = \'entrata\', \'Ordini cliente\', \'Ordini fornitore\') AS modulo,
        GROUP_CONCAT(CONCAT(`or_righe_ordini`.`original_id`, " - ", `or_righe_ordini`.`qta`) SEPARATOR ", ") AS righe,
        `or_stati_ordine_lang`.`title` AS stato_documento
    FROM
        `or_ordini`
        INNER JOIN `or_righe_ordini` ON `or_righe_ordini`.`id_ordine` = `or_ordini`.`id`
        INNER JOIN `or_tipi_ordine` ON `or_tipi_ordine`.`id` = `or_ordini`.`id_tipo_ordine`
        LEFT JOIN `or_tipi_ordine_lang` ON (`or_tipi_ordine_lang`.`id_record` = `or_tipi_ordine`.`id` AND `or_tipi_ordine_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        LEFT JOIN `or_stati_ordine` ON `or_ordini`.`id_stato` = `or_stati_ordine`.`id`
        LEFT JOIN `or_stati_ordine_lang` ON (`or_stati_ordine_lang`.`id_record` = `or_stati_ordine`.`id` AND `or_stati_ordine_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE
        (`or_righe_ordini`.`original_document_id` = '.prepare($id_record).' AND `or_righe_ordini`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\')
        OR `or_righe_ordini`.`id_preventivo` = '.prepare($id_record).'
    GROUP BY `or_ordini`.`id`

    UNION

    SELECT
        `dt_ddt`.`id`,
        `dt_ddt`.`data`,
        `dt_ddt`.`numero`,
        `dt_ddt`.`numero_esterno`,
        `dt_tipi_ddt_lang`.`title`,
        IF(`dt_tipi_ddt`.`dir` = \'entrata\', \'Ddt in uscita\', \'Ddt in entrata\') AS modulo,
        GROUP_CONCAT(CONCAT(`dt_righe_ddt`.`original_id`, " - ", `dt_righe_ddt`.`qta`) SEPARATOR ", ") AS righe,
        `dt_stati_ddt_lang`.`title` AS stato_documento
    FROM
        `dt_ddt`
        INNER JOIN `dt_righe_ddt` ON `dt_righe_ddt`.`id_ddt` = `dt_ddt`.`id`
        INNER JOIN `dt_tipi_ddt` ON `dt_tipi_ddt`.`id` = `dt_ddt`.`id_tipo_ddt`
        LEFT JOIN `dt_tipi_ddt_lang` ON (`dt_tipi_ddt_lang`.`id_record` = `dt_tipi_ddt`.`id` AND `dt_tipi_ddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        LEFT JOIN `dt_stati_ddt` ON `dt_ddt`.`id_stato` = `dt_stati_ddt`.`id`
        LEFT JOIN `dt_stati_ddt_lang` ON (`dt_stati_ddt_lang`.`id_record` = `dt_stati_ddt`.`id` AND `dt_stati_ddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE
        `dt_righe_ddt`.`original_document_id` = '.prepare($id_record).' AND `dt_righe_ddt`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\'
    GROUP BY `dt_ddt`.`id`

    UNION

    SELECT
        `in_interventi`.`id`,
        `in_interventi`.`data_richiesta`,
        `in_interventi`.`codice`,
        NULL,
        \'Attività\',
        \'Interventi\' AS modulo,
        GROUP_CONCAT(CONCAT(`in_righe_interventi`.`original_id`, " - ", `in_righe_interventi`.`qta`) SEPARATOR ", ") AS righe,
        `in_stati_intervento_lang`.`title` AS stato_documento
    FROM
        `in_interventi`
        LEFT JOIN `in_righe_interventi` ON `in_righe_interventi`.`id_intervento` = `in_interventi`.`id`
        LEFT JOIN `in_stati_intervento` ON `in_interventi`.`id_stato` = `in_stati_intervento`.`id`
        LEFT JOIN `in_stati_intervento_lang` ON (`in_stati_intervento_lang`.`id_record` = `in_stati_intervento`.`id` AND `in_stati_intervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE
        (`in_righe_interventi`.`original_document_id` = '.prepare($id_record).' AND `in_righe_interventi`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\')
        OR `in_interventi`.`id_preventivo` = '.prepare($id_record).'
    GROUP BY `in_interventi`.`id`

    UNION

    SELECT
        `co_contratti`.`id`,
        `co_contratti`.`data_bozza`,
        `co_contratti`.`numero`,
        NULL,
        \'Contratti\',
        \'Contratti\' AS modulo,
        GROUP_CONCAT(CONCAT(`co_righe_contratti`.`original_id`, " - ", `co_righe_contratti`.`qta`) SEPARATOR ", ") AS righe,
        `co_stati_contratti_lang`.`title` AS stato_documento
    FROM
        `co_contratti`
        INNER JOIN `co_righe_contratti` ON `co_righe_contratti`.`id_contratto` = `co_contratti`.`id`
        LEFT JOIN `co_stati_contratti` ON `co_contratti`.`id_stato` = `co_stati_contratti`.`id`
        LEFT JOIN `co_stati_contratti_lang` ON (`co_stati_contratti_lang`.`id_record` = `co_stati_contratti`.`id` AND `co_stati_contratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE
        `co_righe_contratti`.`original_document_id` = '.prepare($id_record).' AND `co_righe_contratti`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\'
    GROUP BY `co_contratti`.`id`

    ORDER BY `modulo`');

    $is_anagrafica_deleted = !$preventivo->anagrafica;
}
