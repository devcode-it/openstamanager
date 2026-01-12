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
use Carbon\Carbon;
use Modules\Articoli\Articolo;

class MovimentiManuali extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        // Recupera l'ID utente loggato
        $user = auth_osm()->getUser();
        $id_utente = $user->id;

        // Restituisce i movimenti eliminati o non più appartenenti all'utente
        $query = 'SELECT `mg_movimenti`.`id`
            FROM `mg_movimenti`
            WHERE `mg_movimenti`.`idutente` = '.prepare($id_utente);

        if ($last_sync_at) {
            $query .= ' AND `mg_movimenti`.`updated_at` > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);
        $ids = array_column($records, 'id');

        return $this->getMissingIDs('mg_movimenti', 'id', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        // Recupera l'ID utente loggato
        $user = auth_osm()->getUser();
        $id_utente = $user->id;

        // Calcola le date dell'ultimo giorno (00:00 - 23:59)
        $oggi = Carbon::now();
        $inizio_giorno = $oggi->copy()->startOfDay()->format('Y-m-d H:i:s');
        $fine_giorno = $oggi->copy()->endOfDay()->format('Y-m-d H:i:s');

        // Query per recuperare i movimenti dell'utente loggato nell'ultimo giorno
        $query = 'SELECT `mg_movimenti`.`id`, `mg_movimenti`.`updated_at`
            FROM `mg_movimenti`
            WHERE `mg_movimenti`.`idutente` = '.prepare($id_utente).'
            AND `mg_movimenti`.`data` BETWEEN '.prepare($inizio_giorno).' AND '.prepare($fine_giorno);

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Recupera l'ID utente loggato per sicurezza
        $user = auth_osm()->getUser();
        $id_utente = $user->id;

        // Query per recuperare il dettaglio del movimento
        $query = 'SELECT
            `mg_movimenti`.`id`,
            `mg_movimenti`.`idarticolo` AS id_articolo,
            `mg_movimenti`.`qta`,
            `mg_movimenti`.`movimento` AS descrizione,
            `mg_movimenti`.`data`,
            `mg_movimenti`.`idsede` AS id_sede_azienda,
            `mg_movimenti`.`idutente` AS id_utente,
            `mg_movimenti`.`manuale`
        FROM
            `mg_movimenti`
            LEFT JOIN `mg_articoli` ON `mg_movimenti`.`idarticolo` = `mg_articoli`.`id`
            LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
        WHERE
            `mg_movimenti`.`id` = '.prepare($id).'
            AND `mg_movimenti`.`idutente` = '.prepare($id_utente);

        $record = database()->fetchOne($query);

        return $record;
    }

    public function createRecord($data)
    {
        $articolo = Articolo::find($data['id_articolo']);
        $data_movimento = new Carbon($data['data']);

        $id_sede = isset($data['id_sede_azienda']) && $data['id_sede_azienda'] !== null ? $data['id_sede_azienda'] : 0;

        $id_movimento = $articolo->movimenta($data['qta'], $data['descrizione'], $data_movimento, true, [
            'idsede' => $id_sede,
            'idutente' => auth_osm()->getUser()->id,
        ]);

        return [
            'id' => $id_movimento,
        ];
    }
}
