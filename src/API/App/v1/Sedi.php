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

class Sedi extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getMissingIDs('an_sedi', 'id', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = 'SELECT 
            DISTINCT(`an_sedi`.`id`) AS id,
            `an_sedi`.`updated_at` 
        FROM
            `an_sedi`
            INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`id` = `an_sedi`.`id_anagrafica`
            INNER JOIN `an_tipi_anagrafiche_anagrafiche` ON `an_tipi_anagrafiche_anagrafiche`.`id_anagrafica` = `an_anagrafiche`.`id`
            INNER JOIN `an_tipi_anagrafiche` ON `an_tipi_anagrafiche_anagrafiche`.`id_tipo_anagrafica` = `an_tipi_anagrafiche`.`id`
            LEFT JOIN `an_tipi_anagrafiche_lang` ON (`an_tipi_anagrafiche`.`id`=`an_tipi_anagrafiche_lang`.`id_record` AND `an_tipi_anagrafiche_lang`.`id_lang`='.prepare(\Models\Locale::getDefault()->id).")
        WHERE 
            `an_tipi_anagrafiche_lang`.`title` = 'Cliente' AND `an_anagrafiche`.`deleted_at` IS NULL";

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
        $query = 'SELECT `an_sedi`.`id`,
            `an_sedi`.`id_anagrafica` AS id_cliente,
            `an_sedi`.`nome_sede` AS nome,
            `an_sedi`.`p_iva` AS partita_iva,
            `an_sedi`.`codice_fiscale`,
            `an_sedi`.`indirizzo`,
            `an_sedi`.`citta`,
            `an_sedi`.`cap`,
            `an_sedi`.`provincia`,
            `an_sedi`.`km`,
            IFNULL(`an_sedi`.`lat`, 0.00) AS latitudine,
            IFNULL(`an_sedi`.`lng`, 0.00) AS longitudine,
            `an_nazioni_lang`.`title` AS nazione,
            `an_sedi`.`telefono`,
            `an_sedi`.`cellulare`,
            `an_sedi`.`fax`,
            `an_sedi`.`email`
        FROM `an_sedi`
            LEFT JOIN `an_nazioni` ON `an_sedi`.`id_nazione` = `an_nazioni`.`id`
            LEFT JOIN `an_nazioni_lang` ON (`an_nazioni`.`id` = `an_nazioni_lang`.`id_record` AND `an_nazioni_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
        WHERE `an_sedi`.`id` = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }

    protected function authorizeRecord($id, $user)
    {
        if ($user->is_admin) {
            return true;
        }

        // Verifica che la sede appartenga a un cliente con cui il tecnico ha lavorato
        $count = database()->fetchOne(
            'SELECT COUNT(*) AS cnt FROM an_sedi
             WHERE an_sedi.id = '.prepare($id).'
             AND an_sedi.id_anagrafica IN (
                 SELECT DISTINCT in_interventi.id_anagrafica
                 FROM in_interventi
                 INNER JOIN in_interventi_tecnici ON in_interventi.id = in_interventi_tecnici.idintervento
                 WHERE in_interventi_tecnici.idtecnico = '.prepare($user->id_anagrafica).'
             )'
        );
        return $count['cnt'] > 0;
    }
}
