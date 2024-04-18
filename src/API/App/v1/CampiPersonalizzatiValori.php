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
use Models\Module;

class CampiPersonalizzatiValori extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getMissingIDs('zz_field_record', 'id', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $module = (new Module())->getByField('title', 'Interventi', \Models\Locale::getPredefined()->id);

        $query = 'SELECT `zz_field_record`.`id`, `zz_field_record`.`updated_at` FROM `zz_field_record` INNER JOIN `zz_fields` ON `zz_field_record`.`id_field` = `zz_fields`.`id` WHERE id_module='.prepare($module->id_record).' AND `zz_fields`.`content` LIKE "%text%"';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND zz_field_record.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT 
            `zz_field_record`.`id` AS id,
            `zz_field_record`.`id_field`,
            `zz_field_record`.`id_record`,
            `zz_field_record`.`value`
        FROM 
            `zz_field_record`
        WHERE 
            `zz_field_record`.`id` = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }

    public function updateRecord($data)
    {
        $id = $data['id'];

        database()->query('UPDATE `zz_field_record` SET `value` = '.prepare($data['value']).' WHERE `id` = '.prepare($id));

        return [];
    }
}
