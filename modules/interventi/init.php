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
        IF((`in_interventi`.`id_sede_destinazione` = 0), `an_anagrafiche`.`id_zona`, `an_sedi`.`id_zona`) AS id_zona,
        `in_interventi`.`id_anagrafica` as id_anagrafica,
        `in_interventi`.`id_preventivo` as id_preventivo,
        `in_interventi`.`id_contratto` as id_contratto,
        `in_interventi`.`id_ordine` as id_ordine
    FROM 
        `in_interventi`
        INNER JOIN `an_anagrafiche` ON `in_interventi`.`id_anagrafica` = `an_anagrafiche`.`id`
        LEFT JOIN `an_sedi` ON `in_interventi`.`id_sede_destinazione` = `an_sedi`.`id`
        INNER JOIN `in_statiintervento` ON `in_interventi`.`id_stato` = `in_statiintervento`.`id`
    WHERE 
        `in_interventi`.`id`='.prepare($id_record));

    $is_anagrafica_deleted = !$intervento->anagrafica;
}
