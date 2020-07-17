<?php

namespace Modules\Impostazioni\API\AppV1;

use API\AppResource;
use Carbon\Carbon;

class Impostazioni extends AppResource
{
    protected function getCleanupData()
    {
        return [];
    }

    protected function getData($last_sync_at)
    {
        $query = 'SELECT zz_settings.id FROM zz_settings WHERE sezione = "Applicazione"';

        // Filtro per data
        if ($last_sync_at) {
            $last_sync = new Carbon($last_sync_at);
            $query .= ' AND zz_settings.updated_at > '.prepare($last_sync);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
    }

    protected function getDetails($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT id AS id,
            nome,
            valore AS contenuto,
            tipo
        FROM zz_settings
        WHERE zz_settings.id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
