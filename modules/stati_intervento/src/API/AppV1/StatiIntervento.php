<?php

namespace Modules\StatiIntervento\API\AppV1;

use API\AppResource;
use Carbon\Carbon;

class StatiIntervento extends AppResource
{
    protected function getCleanupData()
    {
        return $this->getDeleted('in_statiintervento', 'idstatointervento');
    }

    protected function getData($last_sync_at)
    {
        $query = 'SELECT in_statiintervento.idstatointervento AS id FROM in_statiintervento';

        // Filtro per data
        if ($last_sync_at) {
            $last_sync = new Carbon($last_sync_at);
            $query .= ' WHERE in_statiintervento.updated_at > '.prepare($last_sync);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
    }

    protected function getDetails($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT in_statiintervento.idstatointervento AS id,
            in_statiintervento.codice,
            in_statiintervento.descrizione,
            in_statiintervento.colore,
            in_statiintervento.is_completato
        FROM in_statiintervento
        WHERE in_statiintervento.idstatointervento = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
