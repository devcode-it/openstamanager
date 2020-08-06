<?php

namespace API\App;

use API\Interfaces\CreateInterface;
use API\Interfaces\DeleteInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Carbon\Carbon;
use Exception;

/**
 * Risorsa di base per la gestione delle operazioni standard di comunicazione con l'applicazione.
 * Implementa le operazioni di *retrieve* in tre fasi, e rende disponibile l'espansione per operazioni di *create*, *update* e *delete*.
 */
abstract class AppResource extends Resource implements RetrieveInterface, CreateInterface, UpdateInterface, DeleteInterface
{
    /**
     * Gestisce le operazioni di *retrieve* in tre fasi:
     * - Cleanup (elenco di record da rimuovere nell'applicazione);
     * - Record modificati (elenco di record che sono stati aggiornati nel gestionale);
     * - Dettagli del record.
     *
     * @param $request
     *
     * @return array[]
     */
    public function retrieve($request)
    {
        $id = $request['id'];
        $last_sync_at = $request['last_sync_at'] && $request['last_sync_at'] != 'undefined' ? new Carbon($request['last_sync_at']) : null;

        // Gestione delle operazioni di cleanup
        if (strpos($request['resource'], 'cleanup') !== false) {
            $list = [];
            if (!empty($last_sync_at)) {
                $list = $this->getCleanupData($last_sync_at);
            }
            $list = $this->forceToString($list);

            return [
                'records' => $list,
            ];
        }

        // Gestione dell'enumerazione dei record modificati
        if (!isset($id)) {
            $list = $this->getModifiedRecords($last_sync_at);
            $list = $this->forceToString($list);

            return [
                'records' => $list,
            ];
        }

        // Gestione della visualizzazione dei dettagli del record
        $details = $this->retrieveRecord($id);
        $details = $this->forceToString($details);

        return [
            'record' => $details,
        ];
    }

    /**
     * Gestisce la richiesta di creazione di un record, delegando le operazioni relative a *createRecord* e forzando i risultati in formato stringa.
     *
     * @param $request
     *
     * @return array
     */
    public function create($request)
    {
        $data = $request['data'];
        $response_data = $this->createRecord($data);
        $response_data = $this->forceToString($response_data);

        return [
            'id' => $response_data['id'],
            'data' => $response_data,
        ];
    }

    /**
     * Gestisce la richiesta di modifica di un record, delegando le operazioni relative a *updateRecord* e forzando i risultati in formato stringa.
     *
     * @param $request
     *
     * @return array
     */
    public function update($request)
    {
        $data = $request['data'];
        $response_data = $this->updateRecord($data);
        $response_data = $this->forceToString($response_data);

        return [
            'data' => $response_data,
        ];
    }

    /**
     * Gestisce la richiesta di eliminazione di un record, delegando le operazioni relative a *deleteRecord*.
     *
     * @param $request
     */
    public function delete($request)
    {
        $id = $request['id'];
        $this->deleteRecord($id);
    }

    /**
     * Restituisce un array contenente gli ID dei record eliminati.
     *
     * @param $last_sync
     *
     * @return array
     */
    abstract public function getCleanupData($last_sync);

    /**
     * Restituisce un array contenente gli ID dei record modificati e da sincronizzare.
     *
     * @param string $last_sync_at
     *
     * @return array
     */
    abstract public function getModifiedRecords($last_sync_at);

    /**
     * Restituisce i dettagli relativi a un singolo record identificato tramite ID.
     *
     * @param string $id
     *
     * @return array
     */
    abstract public function retrieveRecord($id);

    /**
     * Crea un nuovo record relativo alla risorsa, restituendo l'ID relativo ed eventuali campi da aggiornare in remoto.
     *
     * @param array $data
     *
     * @return array
     */
    public function createRecord($data)
    {
        return [];
    }

    /**
     * Aggiorna un record relativo alla risorsa, restituendo eventuali campi da aggiornare in remoto.
     *
     * @param array $data
     *
     * @return array
     */
    public function updateRecord($data)
    {
        return [];
    }

    /**
     * Elimina un record relativo alla risorsa.
     *
     * @param string $id
     *
     * @return void
     */
    public function deleteRecord($id)
    {
    }

    /**
     * Converte i valori numerici in stringhe.
     *
     * @param $list
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

    /**
     * Restituisce gli ID dei potenziali record mancanti, sulla base della colonna indicata e a partire da *last_sync_at*.
     *
     * @param string $table_name   Tabella da analizzare
     * @param string $column       Colonna di tipo AUTO_INCREMENT della tabella
     * @param null   $last_sync_at
     *
     * @throws Exception
     *
     * @return array
     */
    protected function getMissingIDs($table_name, $column, $last_sync_at = null)
    {
        $database = database();
        $db_name = $database->getDatabaseName();

        // Ottiene il valore successivo della colonna di tipo AUTO_INCREMENT
        $next_autoincrement = $database->fetchOne('SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '.prepare($table_name).' AND TABLE_SCHEMA = '.prepare($db_name))['AUTO_INCREMENT'];

        /*
        // Ottiene l'ultimo record con data precedente a quella impostata
        $last_id = null;
        if ($last_sync_at) {
            $last_record = $database->fetchOne('SELECT '.$column.' AS id FROM '.$table_name.' WHERE created_at <= '.prepare($last_sync_at).' ORDER BY '.$column.' DESC');
            $last_id = $last_record['id'];
        }
        */

        // Ottiene i vuoti all'interno della sequenza AUTO_INCREMENT
        $query = 'SELECT (t1.'.$column.' + 1) AS start, (SELECT MIN(t3.'.$column.') - 1 FROM '.$table_name.' t3 WHERE t3.'.$column.' > t1.'.$column.') AS end FROM '.$table_name.' t1 WHERE NOT EXISTS (SELECT t2.'.$column.' FROM '.$table_name.' t2 WHERE t2.'.$column.' = t1.'.$column.' + 1)';
        /*
        if ($last_id) {
            $query .= ' AND t1.'.$column.' >= '.prepare($last_id);
        }
        */
        $query .= ' ORDER BY start';
        $steps = $database->fetchArray($query);

        // Gestione dell'eliminazione dei primi record della tabella
        $exists_first = $database->fetchNum('SELECT * FROM '.$table_name.' WHERE '.$column.' = 1');
        if (!$exists_first) {
            $first = $database->fetchOne('SELECT MIN('.$column.') AS min FROM '.$table_name);
            $steps[] = [
                'start' => 1,
                'end' => $first['min'] - 1,
            ];
        }

        $total = [];
        foreach ($steps as $step) {
            if ($step['end'] == null) {
                $step['end'] = $next_autoincrement - 1;
            }

            if ($step['end'] >= $step['start']) {
                $total = array_merge($total, range($step['start'], $step['end']));
            }
        }

        return $total;
    }

    /**
     * Restituisce gli ID dei record con campo *deleted_at* maggiore di *last_sync_at*.
     *
     * @param string $table_name   Tabella da analizzare
     * @param string $column       Colonna di tipo AUTO_INCREMENT della tabella
     * @param null   $last_sync_at
     *
     * @throws Exception
     *
     * @return array
     */
    protected function getDeleted($table_name, $column, $last_sync_at = null)
    {
        $query = 'SELECT '.$column.' AS id FROM '.$table_name.' WHERE deleted_at';
        if ($last_sync_at) {
            $query .= ' > '.prepare($last_sync_at);
        } else {
            $query .= ' IS NOT NULL';
        }

        $results = database()->fetchArray($query);

        return array_column($results, 'id');
    }
}
