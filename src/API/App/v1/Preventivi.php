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

class Preventivi extends AppResource implements RetrieveInterface
{
    public function getCleanupData($last_sync_at)
    {
        $query = 'SELECT DISTINCT(co_preventivi.id) AS id FROM co_preventivi
            INNER JOIN co_statipreventivi ON co_statipreventivi.id = co_preventivi.idstato
        WHERE co_statipreventivi.is_pianificabile = 0';
        if ($last_sync_at) {
            $query .= ' AND (co_preventivi.updated_at > '.prepare($last_sync_at).' OR co_statipreventivi.updated_at > '.prepare($last_sync_at).')';
        }
        $records = database()->fetchArray($query);

        $da_stati = array_column($records, 'id');
        $mancanti = $this->getMissingIDs('co_preventivi', 'id', $last_sync_at);

        $results = array_unique(array_merge($da_stati, $mancanti));

        return $results;
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = "SELECT DISTINCT(co_preventivi.id) AS id, co_preventivi.updated_at FROM co_preventivi
            INNER JOIN co_statipreventivi ON co_statipreventivi.id = co_preventivi.idstato
            INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = co_preventivi.idanagrafica
            INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche_anagrafiche.idanagrafica = an_anagrafiche.idanagrafica
            INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica = an_tipianagrafiche.idtipoanagrafica
        WHERE an_tipianagrafiche.descrizione = 'Cliente' AND co_statipreventivi.is_pianificabile = 1 AND an_anagrafiche.deleted_at IS NULL";

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND co_preventivi.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT co_preventivi.id,
            co_preventivi.idanagrafica AS id_cliente,
            IF(co_preventivi.idsede = 0, NULL, co_preventivi.idsede) AS id_sede,
            co_preventivi.nome,
            co_preventivi.numero,
            co_preventivi.data_bozza,
            co_statipreventivi.descrizione AS stato
        FROM co_preventivi
            INNER JOIN co_statipreventivi ON co_statipreventivi.id = co_preventivi.idstato
        WHERE co_preventivi.id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
