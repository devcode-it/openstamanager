<?php

namespace API\App\v1;

use API\App\AppResource;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Modules\Impianti\Impianto;

class Impianti extends AppResource
{
    protected function getCleanupData()
    {
        return $this->getMissingIDs('my_impianti', 'id');
    }

    protected function getData($last_sync_at)
    {
        $statement = Impianto::select('id')
            ->whereHas('anagrafica.tipi', function (Builder $query) {
                $query->where('descrizione', '=', 'Cliente');
            });

        // Filtro per data
        if ($last_sync_at) {
            $last_sync = new Carbon($last_sync_at);
            $statement = $statement->where('updated_at', '>', $last_sync);
        }

        $results = $statement->get()
            ->pluck('id');

        return $results;
    }

    protected function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT my_impianti.id,
            my_impianti.idanagrafica AS id_anagrafica,
            my_impianti.matricola,
            my_impianti.nome,
            my_impianti.descrizione
        FROM my_impianti
        WHERE my_impianti.id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
