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

class Referenti extends AppResource implements RetrieveInterface
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getMissingIDs('an_referenti', 'id', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = 'SELECT 
            DISTINCT(`an_referenti`.`id`) AS id, 
            `an_referenti`.`updated_at` 
        FROM 
            `an_referenti`
            INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`id` = `an_referenti`.`id_anagrafica`
            INNER JOIN `an_tipi_anagrafiche_anagrafiche` ON `an_tipi_anagrafiche_anagrafiche`.`id_anagrafica` = `an_anagrafiche`.`id`
            INNER JOIN `an_tipi_anagrafiche` ON `an_tipi_anagrafiche_anagrafiche`.`id_tipo_anagrafica` = `an_tipi_anagrafiche`.`id`
            LEFT JOIN `an_tipi_anagrafiche_lang` ON (`an_tipi_anagrafiche_lang`.`id_record` = `an_tipi_anagrafiche`.`id` AND `an_tipi_anagrafiche_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).")
        WHERE 
            `an_tipi_anagrafiche_lang`.`title` = 'Cliente' AND (an_anagrafiche.deleted_at IS NULL OR an_anagrafiche.id IN(SELECT in_interventi.id_anagrafica FROM in_interventi))";

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND `an_referenti`.`updated_at` > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT `an_referenti`.`id`,
            `id_anagrafica` AS id_cliente,
            IF(`id_sede` = 0, NULL, `id_sede`) AS id_sede,
            `an_referenti`.`nome`,
            `an_mansioni`.`nome` AS mansione,
            `telefono`,
            `email`
        FROM 
            `an_referenti` 
            LEFT JOIN `an_mansioni` ON `an_referenti`.`id_mansione`=`an_mansioni`.`id` 
        WHERE 
            `an_referenti`.`id` = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }

    protected function authorizeRecord($id, $user)
    {
        if ($user->is_admin) {
            return true;
        }

        // Verifica che il referente appartenga a un cliente con cui il tecnico ha lavorato
        $count = database()->fetchOne(
            'SELECT COUNT(*) AS cnt FROM an_referenti
             WHERE an_referenti.id = '.prepare($id).'
             AND an_referenti.id_anagrafica IN (
                 SELECT DISTINCT in_interventi.id_anagrafica
                 FROM in_interventi
                 INNER JOIN in_interventi_tecnici ON in_interventi.id = in_interventi_tecnici.idintervento
                 WHERE in_interventi_tecnici.idtecnico = '.prepare($user->id_anagrafica).'
             )'
        );

        return $count['cnt'] > 0;
    }
}
