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

namespace API\App\v1\Flash;

use API\App\v1\AllegatiInterventi;
use API\App\v1\Clienti;
use API\App\v1\Interventi;
use API\App\v1\RigheInterventi;
use API\App\v1\SessioniInterventi;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Carbon\Carbon;

class Intervento extends Resource implements UpdateInterface
{
    public function update($request)
    {
        // Elenco risorse API
        $risorse = [
            'cliente' => new Clienti(),
            'intervento' => new Interventi(),

            'righe' => new RigheInterventi(),
            'sessioni' => new SessioniInterventi(),
            'allegati' => new AllegatiInterventi(),
        ];
        $sezioni = array_keys($risorse);

        // Generazione record semplificati
        $records = [];
        foreach ($sezioni as $sezione) {
            if (isset($request[$sezione][0])) {
                foreach ($request[$sezione] as $record) {
                    $records[] = [$record, $risorse[$sezione]];
                }
            } elseif (!empty($request[$sezione])) {
                $records[] = [$request[$sezione], $risorse[$sezione]];
            }
        }

        // Controlli sui conflitti
        $conflict = false;
        foreach ($records as $key => [$record, $risorsa]) {
            $ultima_modifica = new Carbon($record['updated_at']);
            $ultima_sincronizzazione = new Carbon($record['last_sync_at']);
            if (!empty($record['last_sync_at']) && !$ultima_modifica->greaterThan($ultima_sincronizzazione)) {
                unset($records[$key]);
                continue;
            }

            $modifiche = $risorsa->getModifiedRecords($record['last_sync_at']);
            $modifiche = array_keys($modifiche);

            if (in_array($record['id'], $modifiche)) {
                $conflict = true;
                break;
            }
        }

        // Messaggio di conflitto in caso di problematica riscontrata
        if ($conflict) {
            return [
                'status' => 200,
                'message' => 'CONFLICT',
            ];
        }

        // Salvataggio delle modifiche
        foreach ($records as [$record, $risorsa]) {
            if (!empty($record['deleted_at'])) {
                $risorsa->deleteRecord($record['id']);
            } elseif (!empty($record['remote_id'])) {
                $risorsa->updateRecord($record);
            } else {
                $risorsa->createRecord($record);
            }
        }

        return [];
    }
}
