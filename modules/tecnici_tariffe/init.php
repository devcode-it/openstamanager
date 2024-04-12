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
    $record = $dbo->fetchOne('SELECT `idanagrafica`, `ragione_sociale`, `colore` FROM `an_anagrafiche` WHERE `idanagrafica` = '.prepare($id_record));

    $tipi_interventi = $dbo->fetchArray('SELECT 
            `in_tariffe`.*,
            `in_tipiintervento_lang`.`name`,
            `in_tipiintervento`.`id`, 
            `in_tariffe`.`idtipointervento` AS esiste 
        FROM 
            `in_tipiintervento` 
            LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id` = `in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') 
            LEFT JOIN `in_tariffe` ON `in_tipiintervento`.`id` = `in_tariffe`.`idtipointervento` AND `in_tariffe`.`idtecnico` = '.prepare($id_record).' 
        WHERE 
            `in_tipiintervento`.`deleted_at` IS NULL 
        ORDER BY 
            `name`');
}
