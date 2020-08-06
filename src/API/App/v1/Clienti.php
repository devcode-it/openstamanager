<?php

namespace API\App\v1;

use API\App\AppResource;
use Auth;

class Clienti extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getDeleted('an_anagrafiche', 'idanagrafica', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $parameters = [];
        $query = "SELECT an_anagrafiche.idanagrafica AS id FROM an_anagrafiche
            INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche_anagrafiche.idanagrafica = an_anagrafiche.idanagrafica
            INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica = an_tipianagrafiche.idtipoanagrafica
        WHERE an_tipianagrafiche.descrizione = 'Cliente' AND an_anagrafiche.deleted_at IS NULL";

        $sincronizza_lavorati = setting('Sincronizza solo i Clienti per cui il Tecnico ha lavorato in passato');
        if (!empty($sincronizza_lavorati)) {
            $query .= '
                AND an_anagrafiche.idanagrafica IN (
                    SELECT idanagrafica FROM in_interventi
                        INNER JOIN in_interventi_tecnici ON in_interventi_tecnici.idintervento = in_interventi.id
                    WHERE in_interventi_tecnici.orario_fine BETWEEN :period_start AND :period_end
                        AND in_interventi_tecnici.idtecnico = :id_tecnico

                    UNION

                    SELECT idanagrafica FROM in_interventi
                        WHERE in_interventi.id NOT IN (
                            SELECT idintervento FROM in_interventi_tecnici
                        )
                )';

            $date = (new Interventi())->getDateDiInteresse();
            $id_tecnico = Auth::user()->id_anagrafica;
            $parameters = [
                ':period_start' => $date['start'],
                ':period_end' => $date['end'],
                ':id_tecnico' => $id_tecnico,
            ];
        }

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND an_anagrafiche.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query, $parameters);

        return array_column($records, 'id');
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
