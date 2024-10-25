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
            INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `an_referenti`.`idanagrafica`
            INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
            INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` = `an_tipianagrafiche`.`id`
            LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche_lang`.`id_record` = `an_tipianagrafiche`.`id` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).")
        WHERE 
            `an_tipianagrafiche_lang`.`title` = 'Cliente' AND (an_anagrafiche.deleted_at IS NULL OR an_anagrafiche.idanagrafica IN(SELECT in_interventi.idanagrafica FROM in_interventi))";

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
            `idanagrafica` AS id_cliente,
            IF(`idsede` = 0, NULL, `idsede`) AS id_sede,
            `an_referenti`.`nome`,
            `an_mansioni`.`nome` AS mansione,
            `telefono`,
            `email`
        FROM 
            `an_referenti` 
            LEFT JOIN `an_mansioni` ON `an_referenti`.`idmansione`=`an_mansioni`.`id` 
        WHERE 
            `an_referenti`.`id` = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
