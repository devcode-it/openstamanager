<?php

namespace API\App\v1;

use API\App\AppResource;
use Illuminate\Database\Eloquent\Builder;
use Modules\Anagrafiche\Anagrafica;

class Clienti extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getDeleted('an_anagrafiche', 'idanagrafica', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $statement = Anagrafica::select('idanagrafica')
            ->whereHas('tipi', function (Builder $query) {
                $query->where('descrizione', '=', 'Cliente');
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
            an_anagrafiche.ragione_sociale,
            an_anagrafiche.piva AS partita_iva,
            an_anagrafiche.codice_fiscale,
            an_anagrafiche.indirizzo,
            an_anagrafiche.indirizzo2,
            an_anagrafiche.citta,
            an_anagrafiche.cap,
            an_anagrafiche.provincia,
            an_anagrafiche.km,
            IFNULL(an_anagrafiche.lat, 0.00) AS latitudine,
            IFNULL(an_anagrafiche.lng, 0.00) AS longitudine,
            an_nazioni.nome AS nazione,
            an_anagrafiche.fax,
            an_anagrafiche.telefono,
            an_anagrafiche.cellulare,
            an_anagrafiche.email,
            an_anagrafiche.sitoweb AS sito_web,
            an_anagrafiche.note,
            an_anagrafiche.deleted_at
        FROM an_anagrafiche
            LEFT OUTER JOIN an_nazioni ON an_anagrafiche.id_nazione = an_nazioni.id
        WHERE an_anagrafiche.idanagrafica = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
