<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

class TipiIntervento extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getMissingIDs('in_tipiintervento', 'idtipointervento', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = 'SELECT in_tipiintervento.idtipointervento AS id FROM in_tipiintervento';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' WHERE in_tipiintervento.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT in_tipiintervento.idtipointervento AS id,
            in_tipiintervento.descrizione,
            costo_orario AS prezzo_orario,
            costo_km AS prezzo_chilometrico,
            costo_diritto_chiamata AS prezzo_diritto_chiamata
        FROM in_tipiintervento
        WHERE in_tipiintervento.idtipointervento = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
