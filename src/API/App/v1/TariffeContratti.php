<?php

namespace API\App\v1;

use API\App\AppResource;

class TariffeContratti extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        $query = 'SELECT CONCAT(idtipointervento, "-", idcontratto) AS id
        FROM co_contratti_tipiintervento
            INNER JOIN co_contratti ON co_contratti.id = co_contratti_tipiintervento.idcontratto
            INNER JOIN co_staticontratti ON co_staticontratti.id = co_contratti.idstato
        WHERE co_staticontratti.is_pianificabile = 0';
        if ($last_sync_at) {
            $query .= ' AND (co_contratti.updated_at > '.prepare($last_sync_at).' OR co_staticontratti.updated_at > '.prepare($last_sync_at).')';
        }
        $records = database()->fetchArray($query);

        $da_contratti = array_column($records, 'id');

        // Le associazioni Contratti - Tariffe per tipi non sono cancellabili a database
        // Per le ultime versioni, sono anzi sempre presenti!
        return $da_contratti;
    }

    public function getModifiedRecords($last_sync_at)
    {
        $query = 'SELECT CONCAT(idtipointervento, "-", idcontratto) AS id
            FROM co_contratti_tipiintervento
            INNER JOIN co_contratti ON co_contratti.id = co_contratti_tipiintervento.idcontratto
            INNER JOIN co_staticontratti ON co_staticontratti.id = co_contratti.idstato
        WHERE co_staticontratti.is_pianificabile = 1';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND co_contratti_tipiintervento.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
    }

    public function retrieveRecord($id)
    {
        $pieces = explode('-', $id);
        $id_tipo_intervento = $pieces[0];
        $id_contratto = $pieces[1];

        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT CONCAT(idtipointervento, "-", idcontratto) AS id,
            NULL AS id_tecnico,
            idtipointervento AS id_tipo_intervento,
            idcontratto AS id_contratto,
            costo_ore AS prezzo_orario,
            costo_km AS prezzo_chilometrico,
            costo_dirittochiamata AS prezzo_diritto_chiamata
        FROM co_contratti_tipiintervento
        WHERE co_contratti_tipiintervento.idtipointervento = '.prepare($id_tipo_intervento).' AND co_contratti_tipiintervento.idcontratto = '.prepare($id_contratto);

        $record = database()->fetchOne($query);

        return $record;
    }
}
