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

class Automezzi extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getMissingIDs('an_sedi', 'id', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        // Recupera l'ID utente loggato
        $user = auth_osm()->getUser();
        $id_utente = $user->id;

        // Query per recuperare gli automezzi collegati al tecnico tramite zz_user_sedi
        $query = 'SELECT
            DISTINCT(`an_sedi`.`id`) AS id,
            `an_sedi`.`updated_at`
        FROM
            `an_sedi`
            INNER JOIN `zz_user_sedi` ON `zz_user_sedi`.`idsede` = `an_sedi`.`id`
        WHERE
            `an_sedi`.`is_automezzo` = 1
            AND `an_sedi`.`deleted_at` IS NULL
            AND `zz_user_sedi`.`id_user` = '.prepare($id_utente);

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND `an_sedi`.`updated_at` > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT
            `an_sedi`.`id`,
            `an_sedi`.`nome`,
            `an_sedi`.`targa`,
            `an_sedi`.`descrizione`,
            `an_sedi`.`indirizzo`,
            `an_sedi`.`citta`,
            `an_sedi`.`cap`,
            `an_sedi`.`provincia`,
            IFNULL(`an_sedi`.`lat`, 0.00) AS latitudine,
            IFNULL(`an_sedi`.`lng`, 0.00) AS longitudine
        FROM
            `an_sedi`
        WHERE
            `an_sedi`.`id` = '.prepare($id).'
            AND `an_sedi`.`is_automezzo` = 1';

        $record = database()->fetchOne($query);

        return $record;
    }
}
