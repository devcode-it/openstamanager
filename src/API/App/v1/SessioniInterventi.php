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
use Auth;
use Carbon\Carbon;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;

class SessioniInterventi extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        // Periodo per selezionare interventi
        $mesi_precedenti = intval(setting('Mesi per lo storico delle Attività'));
        $today = new Carbon();
        $start = $today->copy()->subMonths($mesi_precedenti);
        $end = $today->copy()->addMonth(1);

        // Informazioni sull'utente
        $user = Auth::user();
        $id_tecnico = $user->id_anagrafica;

        // Elenco di interventi di interesse
        $risorsa_interventi = $this->getRisorsaInterventi();
        $interventi = $risorsa_interventi->getCleanupData($last_sync_at);

        // Elenco sessioni degli interventi da rimuovere
        $da_interventi = [];
        if (!empty($interventi)) {
            $query = 'SELECT in_interventi_tecnici.id
        FROM in_interventi_tecnici
            INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id
        WHERE
            in_interventi.id IN ('.implode(',', $interventi).')
            OR (orario_fine NOT BETWEEN :period_start AND :period_end)';
            $records = database()->fetchArray($query, [
                ':period_end' => $end,
                ':period_start' => $start,
            ]);
            $da_interventi = array_column($records, 'id');
        }

        $mancanti = $this->getMissingIDs('in_interventi_tecnici', 'id', $last_sync_at);

        $results = array_unique(array_merge($da_interventi, $mancanti));

        return $results;
    }

    public function getModifiedRecords($last_sync_at)
    {
        // Periodo per selezionare interventi
        $mesi_precedenti = intval(setting('Mesi per lo storico delle Attività'));
        $today = new Carbon();
        $start = $today->copy()->subMonths($mesi_precedenti);
        $end = $today->copy()->addMonth(1);

        // Informazioni sull'utente
        $user = Auth::user();
        $id_tecnico = $user->id_anagrafica;

        // Elenco di interventi di interesse
        $risorsa_interventi = $this->getRisorsaInterventi();
        $interventi = $risorsa_interventi->getModifiedRecords(null);
        if (empty($interventi)) {
            return [];
        }

        $id_interventi = array_keys($interventi);
        $query = 'SELECT in_interventi_tecnici.id
        FROM in_interventi_tecnici
            INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id
        WHERE
            in_interventi.id IN ('.implode(',', $id_interventi).')
            AND (orario_fine BETWEEN :period_start AND :period_end)';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND in_interventi_tecnici.updated_at > '.prepare($last_sync_at);
        }
        $records = database()->fetchArray($query, [
            ':period_start' => $start,
            ':period_end' => $end,
        ]);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = "SELECT id,
            idintervento AS id_intervento,
            idtecnico AS id_tecnico,
            idtipointervento AS id_tipo_intervento,
            orario_inizio,
            orario_fine,
            km,

            prezzo_ore_unitario AS prezzo_orario,
            IF(tipo_sconto = 'UNT', sconto_unitario, sconto_unitario * prezzo_ore_unitario / 100) AS sconto_orario,
            IF(tipo_sconto = 'PRC', sconto_unitario, 0) AS sconto_orario_percentuale,
            tipo_sconto AS tipo_sconto_orario,

            prezzo_km_unitario AS prezzo_chilometrico,
            IF(tipo_scontokm = 'UNT', scontokm_unitario, scontokm_unitario * prezzo_km_unitario / 100) AS sconto_chilometrico,
            IF(tipo_scontokm = 'PRC', scontokm_unitario, 0) AS sconto_chilometrico_percentuale,
            tipo_sconto AS tipo_sconto_chilometrico,

            prezzo_dirittochiamata AS prezzo_diritto_chiamata
        FROM in_interventi_tecnici
        WHERE in_interventi_tecnici.id = ".prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }

    public function createRecord($data)
    {
        // Informazioni sull'utente
        $user = Auth::user();
        $id_tecnico = $user->id_anagrafica;

        // Informazioni di base
        $intervento = Intervento::find($data['id_intervento']);
        $anagrafica = Anagrafica::find($id_tecnico);

        // Creazione della sessione
        $sessione = Sessione::build($intervento, $anagrafica, $data['orario_inizio'], $data['orario_fine']);

        $this->aggiornaSessione($sessione, $data);
        $sessione->save();

        return [
            'id' => $sessione->id,
        ];
    }

    public function updateRecord($data)
    {
        $sessione = Sessione::find($data['id']);

        $this->aggiornaSessione($sessione, $data);
        $sessione->save();

        return [];
    }

    public function deleteRecord($id)
    {
        $sessione = Sessione::find($id);
        $sessione->delete();
    }

    protected function getRisorsaInterventi()
    {
        return new Interventi();
    }

    /**
     * Aggiorna i dati della sessione sulla base dei dati caricati dall'applicazione.
     *
     * @param $sessione
     * @param $data
     *
     * @return array
     */
    protected function aggiornaSessione($sessione, $data)
    {
        $id_tipo = $data['id_tipo_intervento'];
        $sessione->setTipo($id_tipo);

        // Campi di base
        $sessione->orario_inizio = $data['orario_inizio'];
        $sessione->orario_fine = $data['orario_fine'];
        $sessione->km = $data['km'];

        // Prezzi
        $sessione->prezzo_ore_unitario = $data['prezzo_orario'];
        $sessione->prezzo_km_unitario = $data['prezzo_chilometrico'];
        $sessione->prezzo_dirittochiamata = $data['prezzo_diritto_chiamata'];

        // Sconto orario
        $sessione->sconto_unitario = $data['sconto_orario_percentuale'] ?: $data['sconto_orario'];
        $sessione->tipo_sconto = $data['tipo_sconto_orario'];

        // Sconto chilometrico
        $sessione->scontokm_unitario = $data['sconto_chilometrico_percentuale'] ?: $data['sconto_chilometrico'];
        $sessione->tipo_scontokm = $data['tipo_sconto_chilometrico'];

        return [];
    }
}
