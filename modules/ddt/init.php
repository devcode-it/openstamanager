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
        `dt_ddt`.`id` AS idddt,
        `dt_statiddt_lang`.`title` AS `stato`,
        `dt_statiddt`.`completato` AS `flag_completato`,
        `dt_tipiddt_lang`.`title` AS `descrizione_tipodoc`,
        `an_anagrafiche`.`tipo` AS tipo_anagrafica
    FROM `dt_ddt`
        INNER JOIN `dt_statiddt` ON `dt_ddt`.`idstatoddt`=`dt_statiddt`.`id`
        LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt_lang`.`id_record` = `dt_statiddt`.`id` AND `dt_statiddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        INNER JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica`=`an_anagrafiche`.`idanagrafica`
        INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt`=`dt_tipiddt`.`id`
        LEFT JOIN `dt_tipiddt_lang` ON (`dt_tipiddt_lang`.`id_record` = `dt_tipiddt`.`id` AND `dt_tipiddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE 
        `dt_ddt`.`id`='.prepare($id_record));

    if (!empty($record)) {
        $record['idporto'] = $record['idporto'] ?: $dbo->fetchOne('SELECT `id` FROM `dt_porto` WHERE `predefined` = 1')['id'];
        $record['idcausalet'] = $record['idcausalet'] ?: $dbo->fetchOne('SELECT `id` FROM `dt_causalet` WHERE `predefined` = 1')['id'];
        $record['idspedizione'] = $record['idspedizione'] ?: $dbo->fetchOne('SELECT `id` FROM `dt_spedizione` WHERE `predefined` = 1')['id'];
    }

    // Se la sede del ddt non è di mia competenza, blocco il ddt in modifica
    $field_name = ($dir == 'entrata') ? 'idsede_partenza' : 'idsede_destinazione';
    if (!Auth::admin() && !in_array($record[$field_name], $user->sedi)) {
        $record['flag_completato'] = 1;
    }

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
        `co_righe_documenti`.`idddt` = '.prepare($id_record).'
    GROUP BY 
        id

    UNION

    SELECT 
        `in_interventi`.`id`, 
        `in_interventi`.`data_richiesta`, 
        `in_interventi`.`codice`, 
        NULL, 
        \'Attività\' AS tipo_documento, 
        \'Interventi\' as modulo,
        GROUP_CONCAT(CONCAT(`original_id`, " - ", `qta`) SEPARATOR ", ") AS righe
    FROM 
        `in_interventi` 
        JOIN `in_righe_interventi` ON `in_righe_interventi`.`idintervento` = `in_interventi`.`id` 
    WHERE 
        (`in_righe_interventi`.`original_document_id` = '.prepare($id_record).' AND `in_righe_interventi`.`original_document_type` = \'Modules\\\\DDT\\\\DDT\')
    GROUP BY 
        id

    ORDER BY `data`');
}
