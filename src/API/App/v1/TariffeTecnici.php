<?php

namespace API\App\v1;

use API\App\AppResource;
use Illuminate\Database\Eloquent\Builder;
use Modules\Anagrafiche\Anagrafica;

class TariffeTecnici extends AppResource
{
    protected function getCleanupData($last_sync_at)
    {
        return $this->getMissingIDs('in_tariffe', 'id', $last_sync_at);
    }

    protected function getModifiedRecords($last_sync_at)
    {
        $query = 'SELECT id FROM in_tariffe';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' WHERE updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
    }

    protected function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT id,
            idtecnico AS id_tecnico,
            idtipointervento AS id_tipo_intervento,
            NULL AS id_contratto,
            costo_ore AS prezzo_orario,
            costo_km AS prezzo_chilometrico,
            costo_dirittochiamata AS prezzo_diritto_chiamata
        FROM in_tariffe
        WHERE id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
