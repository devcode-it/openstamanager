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

class Articoli extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getDeleted('mg_articoli', 'id', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = 'SELECT `mg_articoli`.`id`, `mg_articoli`.`updated_at` FROM `mg_articoli` WHERE `deleted_at` IS NULL';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND mg_articoli.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT 
            `mg_articoli`.`id` AS id,
            `mg_articoli`.`codice`,
            `mg_articoli`.`barcode`,
            `mg_articoli_lang`.`title`,
            `mg_articoli`.`prezzo_vendita`,
            `mg_articoli`.`prezzo_acquisto`,
            `mg_articoli`.`qta`,
            `mg_articoli`.`um`,
            `mg_articoli`.`idiva_vendita` AS id_iva,
            `categoria_lang`.`title` AS categoria,
            `sottocategoria_lang`.`title` AS sottocategoria
        FROM 
            `mg_articoli` 
            LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).') 
            LEFT JOIN `mg_categorie` as categoria ON (`mg_articoli`.`id_categoria` = `categoria`.`id`) 
            LEFT JOIN `mg_categorie_lang` as categoria_lang ON (`categoria`.`id` = `categoria_lang`.`id_record` AND `categoria_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).') 
            LEFT JOIN `mg_categorie` as sottocategoria ON (`mg_articoli`.`id_sottocategoria` = `sottocategoria`.`id`) 
            LEFT JOIN `mg_categorie_lang` as sottocategoria_lang ON (`sottocategoria`.`id` = `sottocategoria_lang`.`id_record` AND `sottocategoria_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
        WHERE 
            `mg_articoli`.`id` = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
