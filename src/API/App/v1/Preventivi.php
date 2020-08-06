<?php

namespace API\App\v1;

use API\App\AppResource;
use API\Interfaces\RetrieveInterface;

class Preventivi extends AppResource implements RetrieveInterface
{
    public function getCleanupData($last_sync_at)
    {
        $query = 'SELECT DISTINCT(co_preventivi.id) AS id FROM co_preventivi
            INNER JOIN co_statipreventivi ON co_statipreventivi.id = co_preventivi.idstato
        WHERE co_statipreventivi.is_pianificabile = 0';
        if ($last_sync_at) {
            $query .= ' AND (co_preventivi.updated_at > '.prepare($last_sync_at).' OR co_statipreventivi.updated_at > '.prepare($last_sync_at).')';
        }
        $records = database()->fetchArray($query);

        $da_stati = array_column($records, 'id');
        $mancanti = $this->getMissingIDs('co_preventivi', 'id', $last_sync_at);

        $results = array_unique(array_merge($da_stati, $mancanti));

        return $results;
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = "SELECT DISTINCT(co_preventivi.id) AS id FROM co_preventivi
            INNER JOIN co_statipreventivi ON co_statipreventivi.id = co_preventivi.idstato
            INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = co_preventivi.idanagrafica
            INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche_anagrafiche.idanagrafica = an_anagrafiche.idanagrafica
            INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica = an_tipianagrafiche.idtipoanagrafica
        WHERE an_tipianagrafiche.descrizione = 'Cliente' AND co_statipreventivi.is_pianificabile = 1 AND an_anagrafiche.deleted_at IS NULL";

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND co_preventivi.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT co_preventivi.id,
            co_preventivi.idanagrafica AS id_cliente,
            IF(co_preventivi.idsede = 0, NULL, co_preventivi.idsede) AS id_sede,
            co_preventivi.nome,
            co_preventivi.numero,
            co_preventivi.data_bozza,
            co_statipreventivi.descrizione AS stato
        FROM co_preventivi
            INNER JOIN co_statipreventivi ON co_statipreventivi.id = co_preventivi.idstato
        WHERE co_preventivi.id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
