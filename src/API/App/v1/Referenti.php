<?php

namespace API\App\v1;

use API\App\AppResource;
use API\Interfaces\RetrieveInterface;
use Carbon\Carbon;

class Referenti extends AppResource implements RetrieveInterface
{
    protected function getCleanupData()
    {
        return $this->getMissingIDs('an_referenti', 'id');
    }

    protected function getData($last_sync_at)
    {
        $query = "SELECT DISTINCT(an_referenti.id) AS id FROM an_referenti
            INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = an_referenti.idanagrafica
            INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche_anagrafiche.idanagrafica = an_anagrafiche.idanagrafica
            INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica = an_tipianagrafiche.idtipoanagrafica
        WHERE an_tipianagrafiche.descrizione = 'Cliente' AND an_anagrafiche.deleted_at IS NULL";

        // Filtro per data
        if ($last_sync_at) {
            $last_sync = new Carbon($last_sync_at);
            $query .= ' AND an_referenti.updated_at > '.prepare($last_sync);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
    }

    protected function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT id,
            idanagrafica AS id_cliente,
            IF(idsede = 0, NULL, idsede) AS id_sede,
            nome,
            mansione,
            telefono,
            email
        FROM an_referenti
        WHERE an_referenti.id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
