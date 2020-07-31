<?php

namespace API\App\v1;

use API\App\AppResource;
use Illuminate\Database\Eloquent\Builder;
use Modules\Anagrafiche\Anagrafica;

class Tecnici extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getDeleted('an_anagrafiche', 'idanagrafica', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $statement = Anagrafica::select('idanagrafica')
            ->whereHas('tipi', function (Builder $query) {
                $query->where('descrizione', '=', 'Tecnico');
            });

        // Filtro per data
        if ($last_sync_at) {
            $statement = $statement->where('updated_at', '>', $last_sync_at);
        }

        $results = $statement->get()
            ->pluck('idanagrafica');

        return $results;
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT an_anagrafiche.idanagrafica AS id,
            an_anagrafiche.ragione_sociale
        FROM an_anagrafiche
        WHERE an_anagrafiche.idanagrafica = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
