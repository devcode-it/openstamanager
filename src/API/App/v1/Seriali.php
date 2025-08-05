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

class Seriali extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getMissingIDs('mg_prodotti', 'id', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = 'SELECT `mg_prodotti`.`id`, `mg_prodotti`.`updated_at` FROM `mg_prodotti`';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' WHERE mg_prodotti.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT
            `mg_prodotti`.`id` AS id,
            `mg_prodotti`.`id_articolo`,
            `mg_prodotti`.`serial`,
            `mg_prodotti`.`id_riga_intervento`,
            `mg_prodotti`.`dir`
        FROM
            `mg_prodotti`

        WHERE
            `mg_prodotti`.`id` = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
