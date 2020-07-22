<?php

namespace API;

use API\Interfaces\RetrieveInterface;

/**
 * Risorsa di base per la gestione delle operazioni standard di comunicazione con l'applicazione.
 */
abstract class AppResource extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        $id = $request['id'];
        $last_sync_at = $request['last_sync_at'] == 'undefined' ? null : $request['last_sync_at'];

        // Gestione delle operazioni di cleanup
        if (strpos($request['resource'], 'cleanup') !== false) {
            $list = $this->getCleanupData();

            return [
                'records' => $list,
            ];
        }

        // Gestione dell'enumerazione dei record modificati
        if (!isset($id)) {
            $list = $this->getData($last_sync_at);

            return [
                'records' => $list,
            ];
        }

        // Gestione della visualizzazione dei dettagli del record
        $details = $this->getDetails($id);

        // Fix per la gestione dei contenuti numerici
        foreach ($details as $key => $value) {
            if (is_numeric($value)) {
                $details[$key] = (string) $value;
            }
        }

        return [
            'record' => $details,
        ];
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
    abstract protected function getDetails($id);
}
