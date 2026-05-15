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

class TipiIntervento extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getMissingIDs('in_tipi_intervento', 'id', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $user = $this->getUser();
        $database = database();

        $query = 'SELECT DISTINCT `in_tipi_intervento`.`id`, `in_tipi_intervento`.`updated_at`
                  FROM `in_tipi_intervento`
                  LEFT JOIN `in_tipi_intervento_groups` ON `in_tipi_intervento_groups`.`id_tipo_intervento` = `in_tipi_intervento`.`id`';
        $where = [];

        // Filtro per data
        if ($last_sync_at) {
            $where[] = '`in_tipi_intervento`.`updated_at` > '.prepare($last_sync_at);
        }

        // Filtro per gruppo utente: sincronizza solo i tipi intervento che hanno il gruppo utente dell'utente loggato
        // oppure che non hanno nessun gruppo utente associato
        $id_gruppo = $user['id_gruppo'];
        $where[] = '(`in_tipi_intervento_groups`.`id_gruppo` = '.prepare($id_gruppo).' OR `in_tipi_intervento_groups`.`id_gruppo` IS NULL)';

        if (!empty($where)) {
            $query .= ' WHERE '.implode(' AND ', $where);
        }

        $records = $database->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        $database = database();

        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT
            `in_tipi_intervento`.`id`,
            `in_tipi_intervento_lang`.`title` AS `descrizione`,
            `costo_orario` AS prezzo_orario,
            `costo_km` AS prezzo_chilometrico,
            `costo_diritto_chiamata` AS prezzo_diritto_chiamata
        FROM
            `in_tipi_intervento`
            LEFT JOIN `in_tipi_intervento_lang` ON (`in_tipi_intervento`.`id` = `in_tipi_intervento_lang`.`id_record` AND `in_tipi_intervento_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
        WHERE
            `in_tipi_intervento`.`id` = '.prepare($id);

        $record = $database->fetchOne($query);

        // Recupero i tipi anagrafiche collegati al tipo intervento
        $tipi_anagrafiche = $database->fetchArray('SELECT `tipo` FROM `in_tipi_intervento_tipologie` WHERE `id_tipo_intervento` = '.prepare($id));

        // Costruisce un array con indici numerici sequenziali per garantire la serializzazione come array JSON
        $tipi = [];
        foreach ($tipi_anagrafiche as $row) {
            $tipi[] = $row['tipo'];
        }

        // Assicura che venga sempre restituito un array JSON, non un oggetto
        $record['tipi_anagrafiche'] = $tipi;

        return $record;
    }
}
