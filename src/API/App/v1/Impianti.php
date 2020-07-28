<?php

namespace API\App\v1;

use API\App\AppResource;
use Illuminate\Database\Eloquent\Builder;
use Modules\Impianti\Impianto;

class Impianti extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getMissingIDs('my_impianti', 'id', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $statement = Impianto::select('id')
            ->whereHas('anagrafica.tipi', function (Builder $query) {
                $query->where('descrizione', '=', 'Cliente');
            });

        // Filtro per data
        if ($last_sync_at) {
            $statement = $statement->where('updated_at', '>', $last_sync_at);
        }

        $results = $statement->get()
            ->pluck('id');

        return $results;
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT my_impianti.id,
            my_impianti.idanagrafica AS id_cliente,
            my_impianti.idsede AS id_sede,
            my_impianti.matricola,
            my_impianti.nome,
            my_impianti.descrizione,
            my_impianti.data AS data_installazione,
            my_impianti.proprietario,
            my_impianti.ubicazione,
            my_impianti.palazzo,
            my_impianti.scala,
            my_impianti.piano,
            my_impianti.interno,
            my_impianti.occupante,
            my_impianti_categorie.nome AS categoria
        FROM my_impianti
            LEFT JOIN my_impianti_categorie ON my_impianti_categorie.id = my_impianti.id_categoria
        WHERE my_impianti.id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
