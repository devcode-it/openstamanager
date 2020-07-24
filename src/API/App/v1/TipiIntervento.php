<?php

namespace API\App\v1;

use API\App\AppResource;
use Carbon\Carbon;

class TipiIntervento extends AppResource
{
    protected function getCleanupData()
    {
        return $this->getMissingIDs('in_tipiintervento', 'idtipointervento');
    }

    protected function getData($last_sync_at)
    {
        $query = 'SELECT in_tipiintervento.idtipointervento AS id FROM in_tipiintervento';

        // Filtro per data
        if ($last_sync_at) {
            $last_sync = new Carbon($last_sync_at);
            $query .= ' WHERE in_tipiintervento.updated_at > '.prepare($last_sync);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
    }

    protected function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT in_tipiintervento.idtipointervento AS id,
            in_tipiintervento.descrizione
        FROM in_tipiintervento
        WHERE in_tipiintervento.idtipointervento = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
