<?php

namespace API\App\v1;

use API\App\AppResource;
use Carbon\Carbon;

class AliquoteIva extends AppResource
{
    protected function getCleanupData()
    {
        return $this->getDeleted('co_iva', 'id');
    }

    protected function getData($last_sync_at)
    {
        $query = 'SELECT co_iva.id FROM co_iva';

        // Filtro per data
        if ($last_sync_at) {
            $last_sync = new Carbon($last_sync_at);
            $query .= ' WHERE co_iva.updated_at > '.prepare($last_sync);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
    }

    protected function retrieveRecord($id)
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
