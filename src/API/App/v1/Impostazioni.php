<?php

namespace API\App\v1;

use API\App\AppResource;

class Impostazioni extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return [];
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = "SELECT zz_settings.id FROM zz_settings WHERE (sezione = 'Applicazione'";

        // Aggiunta delle impostazioni esterne alla sezione Applicazione
        $impostazioni_esterne = $this->getImpostazioniEsterne();
        if (!empty($impostazioni_esterne)) {
            $impostazioni = [];
            foreach ($impostazioni_esterne as $imp) {
                $impostazioni[] = prepare($imp);
            }

            $query .= ' OR nome IN ('.implode(', ', $impostazioni).')';
        }
        $query .= ')';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND zz_settings.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT id AS id,
            nome,
            valore AS contenuto,
            tipo
        FROM zz_settings
        WHERE zz_settings.id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }

    protected function getImpostazioniEsterne()
    {
        return [
            'Mostra prezzi al tecnico',
            "Stato dell'attività alla chiusura",
            "Stato dell'attività dopo la firma",
        ];
    }
}
