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

class TariffeTecnici extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getMissingIDs('in_tariffe', 'id', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = 'SELECT id, updated_at FROM in_tariffe';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' WHERE updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT id,
            idtecnico AS id_tecnico,
            idtipointervento AS id_tipo_intervento,
            NULL AS id_contratto,
            costo_ore AS prezzo_orario,
            costo_km AS prezzo_chilometrico,
            costo_dirittochiamata AS prezzo_diritto_chiamata
        FROM in_tariffe
        WHERE id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
