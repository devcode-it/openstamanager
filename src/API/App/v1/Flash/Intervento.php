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
    /**
     * Elenco risorse API.
     *
     * @var array
     */
    protected $risorse;

    /**
     * Verifica sulla presenza di conflitti.
     *
     * @var bool
     */
    protected $conflitti_rilevati = false;

    /**
     * Record da considerare per l'importazione.
     *
     * @var array
     */
    protected $records = [];

    /**
     * Response delle richieste.
     *
     * @var array
     */
    protected $response = [];

    public function __construct()
    {
        $this->risorse = [
            'cliente' => new Clienti(),
            'intervento' => new Interventi(),

            'righe' => new RigheInterventi(),
            'sessioni' => new SessioniInterventi(),
            'allegati' => new AllegatiInterventi(),
        ];
    }

    public function update($request)
    {
        // Controlli sui conflitti
        foreach ($request as $key => $record) {
            $records = $record;
            if (!isset($records[0])) {
                $records = [$records];
            }

            $this->processaRecords($key, $records);
        }

        // Messaggio di conflitto in caso di problematica riscontrata
        if ($this->conflitti_rilevati) {
            return [
                'status' => 200,
                'message' => 'CONFLICT',
            ];
        }

        // Salvataggio delle modifiche
        foreach ($this->records as $key => $records) {
            $this->importaRecords($key, $records);
        }

        return $this->forceToString($this->response);
    }

    protected function processaRecords($key, $request)
    {
        $records = [];

        // Controlli sui conflitti
        foreach ($request as $id => $record) {
            $risorsa = $this->risorse[$key];
            if (empty($risorsa) || empty($record)) {
                continue;
            }

            $includi = $this->verificaConflitti($record, $risorsa);
            if ($includi) {
                $records[$id] = $record;
            }
        }

        // Registrazione dei record individuati
        if (!empty($records)) {
            $this->records[$key] = $records;
        }
    }

    protected function importaRecords($key, $records)
    {
        $this->response[$key] = [];
        $risorsa = $this->risorse[$key];

        foreach ($records as $id => $record) {
            // Fix id_cliente per Intervento in caso di generazione da zero
            if ($risorsa instanceof Interventi && !empty($this->response['cliente'][$id]) && !empty($this->response['cliente'][$id]['id'])) {
                $record['id_cliente'] = $this->response['cliente'][$id]['id'];
            } elseif (!($risorsa instanceof Clienti) && !empty($this->response['intervento'][0]) && !empty($this->response['intervento'][0]['id'])) {
                $record['id_intervento'] = $this->response['intervento'][0]['id'];
            }

            $response = null;
            if (!empty($record['deleted_at'])) {
                $risorsa->deleteRecord($record['id']);
            } elseif (!empty($record['remote_id'])) {
                $response = $risorsa->updateRecord($record);
            } else {
                $response = $risorsa->createRecord($record);
            }

            $this->response[$key][$id] = $response;
        }
    }

    /**
     * @return bool
     */
    protected function verificaConflitti($record, $risorsa)
    {
        $ultima_modifica = new Carbon($record['updated_at']);
        $ultima_sincronizzazione = new Carbon($record['last_sync_at']);
        if (!empty($record['last_sync_at']) && !$ultima_modifica->greaterThan($ultima_sincronizzazione)) {
            return false;
        }

        $modifiche = $risorsa->getModifiedRecords($record['last_sync_at']);
        $modifiche = array_keys($modifiche);

        $this->conflitti_rilevati |= in_array($record['id'], $modifiche);

        return true;
    }

    /**
     * Converte i valori numerici in stringhe.
     *
     * @return array
     */
    protected function forceToString($list)
    {
        $result = [];
        // Fix per la gestione dei contenuti numerici
        foreach ($list as $key => $value) {
            if (is_numeric($value)) {
                $result[$key] = (string) $value;
            } elseif (is_array($value)) {
                $result[$key] = $this->forceToString($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
