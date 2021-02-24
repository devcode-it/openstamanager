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
use API\Interfaces\RetrieveInterface;

class Contratti extends AppResource implements RetrieveInterface
{
    public function getCleanupData($last_sync_at)
    {
        $query = 'SELECT DISTINCT(co_contratti.id) AS id FROM co_contratti
            INNER JOIN co_staticontratti ON co_staticontratti.id = co_contratti.idstato
        WHERE co_staticontratti.is_pianificabile = 0';
        if ($last_sync_at) {
            $query .= ' AND (co_contratti.updated_at > '.prepare($last_sync_at).' OR co_staticontratti.updated_at > '.prepare($last_sync_at).')';
        }
        $records = database()->fetchArray($query);

        $da_stati = array_column($records, 'id');
        $mancanti = $this->getMissingIDs('co_contratti', 'id', $last_sync_at);

        $results = array_unique(array_merge($da_stati, $mancanti));

        return $results;
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = "SELECT
            DISTINCT(co_contratti.id) AS id,
            co_contratti.updated_at
        FROM co_contratti
            INNER JOIN co_staticontratti ON co_staticontratti.id = co_contratti.idstato
            INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = co_contratti.idanagrafica
            INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche_anagrafiche.idanagrafica = an_anagrafiche.idanagrafica
            INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica = an_tipianagrafiche.idtipoanagrafica
        WHERE an_tipianagrafiche.descrizione = 'Cliente' AND co_staticontratti.is_pianificabile = 1 AND an_anagrafiche.deleted_at IS NULL";

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND co_contratti.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT co_contratti.id,
            co_contratti.idanagrafica AS id_cliente,
            IF(co_contratti.idsede = 0, NULL, co_contratti.idsede) AS id_sede,
            co_contratti.nome,
            co_contratti.numero,
            co_contratti.data_bozza,
            co_staticontratti.descrizione AS stato
        FROM co_contratti
            INNER JOIN co_staticontratti ON co_staticontratti.id = co_contratti.idstato
        WHERE co_contratti.id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
