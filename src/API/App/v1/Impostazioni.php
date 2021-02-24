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

class Impostazioni extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return [];
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = "SELECT zz_settings.id, zz_settings.updated_at FROM zz_settings WHERE (sezione = 'Applicazione'";

        // Aggiunta delle impostazioni esterne alla sezione Applicazione
        $impostazioni_esterne = $this->getImpostazioniEsterne();
        if (!empty($impostazioni_esterne)) {
            $impostazioni = [];
            foreach ($impostazioni_esterne as $imp) {
                $impostazioni[] = prepare($imp);
            }

            $query .= ' OR nome IN ('.implode(', ', $impostazioni).')';
        }
        $query .= ')';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND zz_settings.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT id AS id,
            nome,
            valore AS contenuto,
            tipo
        FROM zz_settings
        WHERE zz_settings.id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }

    protected function getImpostazioniEsterne()
    {
        return [
            'Mostra prezzi al tecnico',
            "Stato dell'attività alla chiusura",
            "Stato dell'attività dopo la firma",
        ];
    }
}
