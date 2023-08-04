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
use Models\User;
use Modules\Checklists\Check;

class Checklists extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        // Periodo per selezionare interventi
        $mesi_precedenti = intval(setting('Mesi per lo storico delle Attività'));
        $today = new Carbon();
        $start = $today->copy()->subMonths($mesi_precedenti);
        $end = $today->copy()->addMonth();

        // Informazioni sull'utente
        $user = Auth::user();
        $id_tecnico = $user->id_anagrafica;

        // Elenco di interventi di interesse
        $risorsa_interventi = $this->getRisorsaInterventi();
        $interventi = $risorsa_interventi->getCleanupData($last_sync_at);

        // Elenco sessioni degli interventi da rimuovere
        $da_interventi = [];
        if (!empty($interventi)) {
            $query = 'SELECT zz_checks.id
        FROM zz_checks
            INNER JOIN in_interventi ON zz_checks.id_record = in_interventi.id
            INNER JOIN in_interventi_tecnici ON in_interventi_tecnici.idintervento = in_interventi.id
            INNER JOIN zz_modules ON zz_checks.id_module = zz_modules.id
            INNER JOIN zz_check_user ON zz_checks.id = zz_check_user.id_check
        WHERE
            zz_modules.name="Interventi"
            AND
            zz_check_user.id_utente = :id_tecnico
            AND
            in_interventi.id IN ('.implode(',', $interventi).')
            OR (orario_fine NOT BETWEEN :period_start AND :period_end)';
            $records = database()->fetchArray($query, [
                ':period_end' => $end,
                ':period_start' => $start,
                ':id_tecnico' => $user->id,
            ]);
            $da_interventi = array_column($records, 'id');
        }

        $mancanti = $this->getMissingIDs('zz_checks', 'id', $last_sync_at);

        $results = array_unique(array_merge($da_interventi, $mancanti));

        return $results;
    }

    public function getModifiedRecords($last_sync_at)
    {
        // Periodo per selezionare interventi
        $mesi_precedenti = intval(setting('Mesi per lo storico delle Attività'));
        $today = new Carbon();
        $start = $today->copy()->subMonths($mesi_precedenti);
        $end = $today->copy()->addMonth();

        // Elenco di interventi di interesse
        $risorsa_interventi = $this->getRisorsaInterventi();
        $interventi = $risorsa_interventi->getModifiedRecords(null);
        if (empty($interventi)) {
            return [];
        }

        $user = Auth::user();
        $id_tecnico = $user->id_anagrafica;

        $id_interventi = array_keys($interventi);
        $query = 'SELECT zz_checks.id
        FROM zz_checks
            INNER JOIN in_interventi ON zz_checks.id_record = in_interventi.id
            INNER JOIN in_interventi_tecnici ON in_interventi_tecnici.idintervento = in_interventi.id
            INNER JOIN zz_modules ON zz_checks.id_module = zz_modules.id
            INNER JOIN zz_check_user ON zz_checks.id = zz_check_user.id_check
        WHERE
            zz_modules.name="Interventi"
            AND zz_check_user.id_utente = :id_tecnico
            AND in_interventi.id IN ('.implode(',', $id_interventi).')
            AND (orario_fine BETWEEN :period_start AND :period_end)';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND zz_checks.updated_at > '.prepare($last_sync_at);
        }
        $records = database()->fetchArray($query, [
            ':period_start' => $start,
            ':period_end' => $end,
            ':id_tecnico' => $user->id,
        ]);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT zz_checks.id,
            zz_checks.id_record AS id_intervento,
            zz_checks.checked_at,
            zz_checks.content,
            zz_checks.note,
            IF(zz_checks.id_parent IS NULL, 0, zz_checks.id_parent) AS id_parent,
            zz_checks.checked_by,
            zz_checks.order AS ordine
        FROM zz_checks
        WHERE zz_checks.id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }

    public function updateRecord($data)
    {
        $check = Check::find($data['id']);

        $check->checked_at = (!empty($data['checked_at']) ? $data['checked_at'] : null);
        $check->content = $data['content'];
        $check->note = $data['note'];
        $user = User::where('idanagrafica', $data['checked_by'])->first();
        if (!empty($user)) {
            $check->checked_by = $user->id;
        }

        $check->save();

        return [];
    }

    protected function getRisorsaInterventi()
    {
        return new Interventi();
    }
}
