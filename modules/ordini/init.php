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
            `or_ordini`.`id_pagamento`,
            `or_ordini`.`id` AS id_ordine,
            `or_ordini`.`id_agente` AS id_agente,
            `or_ordini`.`id_stato` AS id_stato,
            `or_stati_ordine_lang`.`title` AS stato,
            `or_tipiordine_lang`.`title` AS descrizione_tipodoc,
            `an_anagrafiche`.`tipo` AS tipo_anagrafica,
            `or_stati_ordine`.`is_bloccato` AS flag_completato
        FROM
            `or_ordini`
            LEFT JOIN `or_stati_ordine` ON `or_ordini`.`id_stato`=`or_stati_ordine`.`id`
            LEFT JOIN `or_stati_ordine_lang` ON (`or_stati_ordine_lang`.`id_record`=`or_stati_ordine`.`id` AND `or_stati_ordine_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
            INNER JOIN `an_anagrafiche` ON `or_ordini`.`id_anagrafica`=`an_anagrafiche`.`id`
            INNER JOIN `or_tipiordine` ON `or_ordini`.`id_tipo_ordine`=`or_tipiordine`.`id`
            LEFT JOIN `or_tipiordine_lang` ON (`or_tipiordine_lang`.`id_record`=`or_tipiordine`.`id` AND `or_tipiordine_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
        WHERE
            `or_ordini`.`id`='.prepare($id_record));

    $is_anagrafica_deleted = !$ordine->anagrafica;
}
