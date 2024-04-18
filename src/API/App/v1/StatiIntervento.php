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

namespace API\App\v1;

use API\App\AppResource;

class StatiIntervento extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getDeleted('in_statiintervento', 'id', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = 'SELECT `in_statiintervento`.`id`, `in_statiintervento`.`updated_at` FROM `in_statiintervento`';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' WHERE `in_statiintervento`.`updated_at` > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT `in_statiintervento`.`id`,
            `in_statiintervento`.`codice`,
            `in_statiintervento_lang`.`title` AS `descrizione`,
            `in_statiintervento`.`colore`,
            `in_statiintervento`.`is_completato`
        FROM `in_statiintervento`
        LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND `in_statiintervento_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
        WHERE `in_statiintervento`.`id` = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
