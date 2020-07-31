<?php

namespace API\App\v1;

use API\App\AppResource;

class AliquoteIva extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getDeleted('co_iva', 'id', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = 'SELECT co_iva.id FROM co_iva WHERE deleted_at IS NULL';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND co_iva.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT co_iva.id,
            co_iva.descrizione,
            co_iva.percentuale
        FROM co_iva
        WHERE co_iva.id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
