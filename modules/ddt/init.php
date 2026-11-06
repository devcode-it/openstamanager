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

use Modules\Anagrafiche\Anagrafica;
use Modules\DDT\DDT;

$azienda = Anagrafica::find(setting('Azienda predefinita'));

$module_name = $module ? $module->name : '';

if ($module_name == 'Ddt in entrata') {
    $dir = 'uscita';
} else {
    $dir = 'entrata';
}

if (!empty($id_record)) {
    $ddt = DDT::find($id_record);

    $record = $dbo->fetchOne('SELECT
        `dt_ddt`.*,
        `dt_ddt`.`id` AS id_ddt,
        `dt_stati_ddt_lang`.`title` AS `stato`,
        `dt_stati_ddt`.`is_bloccato` AS `flag_completato`,
        `dt_tipi_ddt_lang`.`title` AS `descrizione_tipodoc`,
        `an_anagrafiche`.`tipo` AS tipo_anagrafica
    FROM `dt_ddt`
        INNER JOIN `dt_stati_ddt` ON `dt_ddt`.`id_stato`=`dt_stati_ddt`.`id`
        LEFT JOIN `dt_stati_ddt_lang` ON (`dt_stati_ddt_lang`.`id_record` = `dt_stati_ddt`.`id` AND `dt_stati_ddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        INNER JOIN `an_anagrafiche` ON `dt_ddt`.`id_anagrafica`=`an_anagrafiche`.`id`
        INNER JOIN `dt_tipi_ddt` ON `dt_ddt`.`id_tipo_ddt`=`dt_tipi_ddt`.`id`
        LEFT JOIN `dt_tipi_ddt_lang` ON (`dt_tipi_ddt_lang`.`id_record` = `dt_tipi_ddt`.`id` AND `dt_tipi_ddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE
        `dt_ddt`.`id`='.prepare($id_record));

    if (!empty($record)) {
        $record['id_porto'] = $record['id_porto'] ?: $dbo->fetchOne('SELECT `id` FROM `dt_porto` WHERE `predefined` = 1')['id'];
        $record['id_causale_t'] = $record['id_causale_t'] ?: $dbo->fetchOne('SELECT `id` FROM `dt_causale_t` WHERE `predefined` = 1')['id'];
        $record['id_spedizione'] = $record['id_spedizione'] ?: $dbo->fetchOne('SELECT `id` FROM `dt_spedizione` WHERE `predefined` = 1')['id'];
    }

    // Se la sede del ddt non è di mia competenza, blocco il ddt in modifica
    $field_name = ($dir == 'entrata') ? 'id_sede_partenza' : 'id_sede_destinazione';
    if (!AuthOSM::admin() && !in_array($record[$field_name], $user->sedi)) {
        $record['flag_completato'] = 1;
    }

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
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`id_documento` = `co_documenti`.`id`
        INNER JOIN `co_tipi_documento` ON `co_tipi_documento`.`id` = `co_documenti`.`id_tipo_documento`
        LEFT JOIN `co_tipi_documento_lang` ON (`co_tipi_documento_lang`.`id_record` = `co_tipi_documento`.`id` AND `co_tipi_documento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        LEFT JOIN `co_stati_documento` ON `co_documenti`.`id_stato` = `co_stati_documento`.`id`
        LEFT JOIN `co_stati_documento_lang` ON (`co_stati_documento_lang`.`id_record` = `co_stati_documento`.`id` AND `co_stati_documento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE
        (`co_righe_documenti`.`original_document_id` = '.prepare($id_record).' AND `co_righe_documenti`.`original_document_type` = \'Modules\\\\DDT\\\\DDT\')
        OR `co_righe_documenti`.`id_ddt` = '.prepare($id_record).'
    GROUP BY
        `co_documenti`.`id`

    UNION

    SELECT
        `in_interventi`.`id`,
        `in_interventi`.`data_richiesta`,
        `in_interventi`.`codice`,
        NULL,
        \'Attività\' AS tipo_documento,
        \'Interventi\' AS modulo,
        GROUP_CONCAT(CONCAT(`in_righe_interventi`.`original_id`, " - ", `in_righe_interventi`.`qta`) SEPARATOR ", ") AS righe,
        `in_stati_intervento_lang`.`title` AS stato_documento
    FROM
        `in_interventi`
        LEFT JOIN `in_righe_interventi` ON `in_righe_interventi`.`id_intervento` = `in_interventi`.`id`
        LEFT JOIN `in_stati_intervento` ON `in_interventi`.`id_stato` = `in_stati_intervento`.`id`
        LEFT JOIN `in_stati_intervento_lang` ON (`in_stati_intervento_lang`.`id_record` = `in_stati_intervento`.`id` AND `in_stati_intervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE
        `in_righe_interventi`.`original_document_id` = '.prepare($id_record).' AND `in_righe_interventi`.`original_document_type` = \'Modules\\\\DDT\\\\DDT\'
    GROUP BY
        `in_interventi`.`id`

    ORDER BY `modulo`');

    $is_anagrafica_deleted = !$ddt->anagrafica;
}
