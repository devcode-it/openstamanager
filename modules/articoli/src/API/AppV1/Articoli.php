<?php

namespace Modules\Articoli\API\AppV1;

use API\AppResource;
use Carbon\Carbon;

class Articoli extends AppResource
{
    protected function getCleanupData()
    {
        return $this->getDeleted('mg_articoli', 'id');
    }

    protected function getData($last_sync_at)
    {
        $query = 'SELECT mg_articoli.id FROM mg_articoli WHERE deleted_at IS NULL';

        // Filtro per data
        if ($last_sync_at) {
            $last_sync = new Carbon($last_sync_at);
            $query .= ' AND mg_articoli.updated_at > '.prepare($last_sync);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
    }

    protected function getDetails($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT mg_articoli.id AS id,
            mg_articoli.codice,
            mg_articoli.descrizione,
            mg_articoli.prezzo_vendita,
            mg_articoli.prezzo_acquisto,
            mg_articoli.qta,
            mg_articoli.um,
            (SELECT nome FROM mg_categorie WHERE id = mg_articoli.id_categoria) AS categoria,
            (SELECT nome FROM mg_categorie WHERE id = mg_articoli.id_sottocategoria) AS sottocategoria
        FROM mg_articoli
        WHERE mg_articoli.id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
