<?php

namespace API\App;

use API\Interfaces\CreateInterface;
use API\Interfaces\DeleteInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;

/**
 * Risorsa di base per la gestione delle operazioni standard di comunicazione con l'applicazione.
 */
abstract class AppResource extends Resource implements RetrieveInterface, CreateInterface, UpdateInterface, DeleteInterface
{
    public function retrieve($request)
    {
        $id = $request['id'];
        $last_sync_at = $request['last_sync_at'] == 'undefined' ? null : $request['last_sync_at'];

        // Gestione delle operazioni di cleanup
        if (strpos($request['resource'], 'cleanup') !== false) {
            $list = $this->getCleanupData();
            $list = $this->forceToString($list);

            return [
                'records' => $list,
            ];
        }

        // Gestione dell'enumerazione dei record modificati
        if (!isset($id)) {
            $list = $this->getData($last_sync_at);
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

    public function update($request)
    {
        $data = $request['data'];
        $response_data = $this->updateRecord($data);
        $response_data = $this->forceToString($response_data);

        return [
            'data' => $response_data,
        ];
    }

    public function delete($request)
    {
        $id = $request['id'];
        $this->deleteRecord($id);
    }

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
     * @param string $table_name Tabella da analizzare
     * @param string $column     Colonna di tipo AUTO_INCREMENT della tabella
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function getMissingIDs($table_name, $column)
    {
        $database = database();
        $db_name = $database->getDatabaseName();

        // Ottiene il valore successivo della colonna di tipo AUTO_INCREMENT
        $auto_inc = $database->fetchOne('SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '.prepare($table_name).' AND TABLE_SCHEMA = '.prepare($db_name))['AUTO_INCREMENT'];

        // Ottiene i vuoti all'interno della sequenza AUTO_INCREMENT
        $steps = $database->fetchArray('SELECT (t1.'.$column.' + 1) as start, (SELECT MIN(t3.'.$column.') - 1 FROM '.$table_name.' t3 WHERE t3.'.$column.' > t1.'.$column.') as end FROM '.$table_name.' t1 WHERE NOT EXISTS (SELECT t2.'.$column.' FROM '.$table_name.' t2 WHERE t2.'.$column.' = t1.'.$column.' + 1) ORDER BY start');

        $total = [];
        foreach ($steps as $step) {
            if ($step['end'] == null) {
                $step['end'] = $auto_inc - 1;
            }

            if ($step['end'] >= $step['start']) {
                $total = array_merge($total, range($step['start'], $step['end']));
            }
        }

        return $total;
    }

    /**
     * @param string $table_name Tabella da analizzare
     * @param string $column     Colonna di tipo AUTO_INCREMENT della tabella
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function getDeleted($table_name, $column)
    {
        $database = database();

        $query = 'SELECT '.$column.' AS id FROM '.$table_name.' WHERE deleted_at IS NOT NULL';
        $results = $database->fetchArray($query);

        return array_column($results, 'id');
    }

    /**
     * Restituisce un array contenente gli ID dei record eliminati.
     *
     * @return array
     */
    abstract protected function getCleanupData();

    /**
     * Restituisce un array contenente gli ID dei record modificati e da sincronizzare.
     *
     * @param string $last_sync_at
     *
     * @return array
     */
    abstract protected function getData($last_sync_at);

    /**
     * Restituisce i dettagli relativi a un singolo record identificato tramite ID.
     *
     * @param string $id
     *
     * @return array
     */
    abstract protected function retrieveRecord($id);

    /**
     * Crea un nuovo record relativo alla risorsa, restituendo l'ID relativo ed eventuali campi da aggiornare in remoto.
     *
     * @param array $data
     *
     * @return array
     */
    protected function createRecord($data)
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
    protected function updateRecord($data)
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
    protected function deleteRecord($id)
    {
    }
}
