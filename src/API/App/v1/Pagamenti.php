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

class Pagamenti extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getMissingIDs('co_pagamenti', 'id', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = 'SELECT `co_pagamenti`.`id`, `co_pagamenti`.`updated_at` FROM `co_pagamenti`';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' WHERE `co_pagamenti`.`updated_at` > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT `co_pagamenti`.`id`,
            `co_pagamenti_lang`.`title` AS `descrizione`
        FROM `co_pagamenti`
        LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
        WHERE `co_pagamenti`.`id` = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
