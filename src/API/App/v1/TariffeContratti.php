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

class TariffeContratti extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        $query = 'SELECT CONCAT(idtipointervento, "-", idcontratto) AS id
        FROM co_contratti_tipiintervento
            INNER JOIN co_contratti ON co_contratti.id = co_contratti_tipiintervento.idcontratto
            INNER JOIN co_staticontratti ON co_staticontratti.id = co_contratti.idstato
        WHERE co_staticontratti.is_pianificabile = 0';
        if ($last_sync_at) {
            $query .= ' AND (co_contratti.updated_at > '.prepare($last_sync_at).' OR co_staticontratti.updated_at > '.prepare($last_sync_at).')';
        }
        $records = database()->fetchArray($query);

        $da_contratti = array_column($records, 'id');

        // Le associazioni Contratti - Tariffe per tipi non sono cancellabili a database
        // Per le ultime versioni, sono anzi sempre presenti!
        return $da_contratti;
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = 'SELECT
            CONCAT(idtipointervento, "-", idcontratto) AS id,
            co_contratti_tipiintervento.updated_at
        FROM co_contratti_tipiintervento
            INNER JOIN co_contratti ON co_contratti.id = co_contratti_tipiintervento.idcontratto
            INNER JOIN co_staticontratti ON co_staticontratti.id = co_contratti.idstato
        WHERE co_staticontratti.is_pianificabile = 1';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND co_contratti_tipiintervento.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        $pieces = explode('-', $id);
        $id_tipo_intervento = $pieces[0];
        $id_contratto = $pieces[1];

        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT CONCAT(idtipointervento, "-", idcontratto) AS id,
            NULL AS id_tecnico,
            idtipointervento AS id_tipo_intervento,
            idcontratto AS id_contratto,
            costo_ore AS prezzo_orario,
            costo_km AS prezzo_chilometrico,
            costo_dirittochiamata AS prezzo_diritto_chiamata
        FROM co_contratti_tipiintervento
        WHERE co_contratti_tipiintervento.idtipointervento = '.prepare($id_tipo_intervento).' AND co_contratti_tipiintervento.idcontratto = '.prepare($id_contratto);

        $record = database()->fetchOne($query);

        return $record;
    }
}
