<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace API\App\v1;

use API\App\AppResource;
use Modules\Anagrafiche\Anagrafica;

class Clienti extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getDeleted('an_anagrafiche', 'idanagrafica', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $parameters = [];
        $query = "SELECT
            an_anagrafiche.idanagrafica AS id,
            an_anagrafiche.updated_at
        FROM an_anagrafiche
            INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche_anagrafiche.idanagrafica = an_anagrafiche.idanagrafica
            INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica = an_tipianagrafiche.idtipoanagrafica
        WHERE an_tipianagrafiche.descrizione = 'Cliente' AND an_anagrafiche.deleted_at IS NULL";

        $sincronizza_lavorati = setting('Sincronizza solo i Clienti per cui il Tecnico ha lavorato in passato');
        if (!empty($sincronizza_lavorati)) {
            // Elenco di interventi di interesse
            $risorsa_interventi = $this->getRisorsaInterventi();
            $interventi = $risorsa_interventi->getModifiedRecords(null);
            if (empty($interventi)) {
                return [];
            }

            $id_interventi = array_keys($interventi);
            $query .= '
                AND an_anagrafiche.idanagrafica IN (
                    SELECT idanagrafica FROM in_interventi
                    WHERE in_interventi.id IN ('.implode(',', $id_interventi).')
                )';
        }

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND an_anagrafiche.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query, $parameters);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT an_anagrafiche.idanagrafica AS id,
            an_anagrafiche.ragione_sociale,
            an_anagrafiche.tipo,
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
            an_anagrafiche.deleted_at,
            an_anagrafiche.idtipointervento_default AS id_tipo_intervento_default
        FROM an_anagrafiche
            LEFT OUTER JOIN an_nazioni ON an_anagrafiche.id_nazione = an_nazioni.id
        WHERE an_anagrafiche.idanagrafica = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }

    public function createRecord($data)
    {
        $ragione_sociale = $data['ragione_sociale'];
        $id_tipo = [1];

        $anagrafica = Anagrafica::build($ragione_sociale, null, null, $id_tipo);
        $id_record = $anagrafica->id;

        $anagrafica->ragione_sociale = $data['ragione_sociale'];
        $anagrafica->tipo = $data['tipo'];
        $anagrafica->piva = $data['partita_iva'];
        $anagrafica->codice_fiscale = $data['codice_fiscale'];
        $anagrafica->indirizzo = $data['indirizzo'];
        $anagrafica->cap = $data['cap'];
        $anagrafica->citta = $data['citta'];
        $anagrafica->provincia = $data['provincia'];
        $anagrafica->telefono = $data['telefono'];
        $anagrafica->cellulare = $data['cellulare'];
        $anagrafica->email = $data['email'];

        $anagrafica->save();

        return [
            'id' => $id_record,
        ];
    }

    public function updateRecord($data)
    {
        $anagrafica = Anagrafica::find($data['id']);

        $this->aggiornaRecord($anagrafica, $data);
        $anagrafica->save();

        return [];
    }

    protected function getRisorsaInterventi()
    {
        return new Interventi();
    }

    protected function aggiornaRecord($record, $data)
    {
        $database = database();

        // Aggiornamento anagrafica
        $record->ragione_sociale = $data['ragione_sociale'];
        $record->tipo = $data['tipo'];
        $record->piva = $data['partita_iva'];
        $record->codice_fiscale = $data['codice_fiscale'];
        $record->indirizzo = $data['indirizzo'];
        $record->cap = $data['cap'];
        $record->citta = $data['citta'];
        $record->provincia = $data['provincia'];
        $record->telefono = $data['telefono'];
        $record->cellulare = $data['cellulare'];
        $record->email = $data['email'];
    }
}
