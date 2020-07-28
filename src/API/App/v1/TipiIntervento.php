<?php

namespace API\App\v1;

use API\App\AppResource;

class TipiIntervento extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getMissingIDs('in_tipiintervento', 'idtipointervento', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = 'SELECT in_tipiintervento.idtipointervento AS id FROM in_tipiintervento';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' WHERE in_tipiintervento.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT in_tipiintervento.idtipointervento AS id,
            in_tipiintervento.descrizione,
            costo_orario AS prezzo_orario,
            costo_km AS prezzo_chilometrico,
            costo_diritto_chiamata AS prezzo_diritto_chiamata
        FROM in_tipiintervento
        WHERE in_tipiintervento.idtipointervento = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
