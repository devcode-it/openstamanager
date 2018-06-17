<?php

use Medoo\Medoo;

/**
 * Classe per gestire la connessione al database.
 *
 * @since 2.3
 */
class Database extends Util\Singleton
{
    /** @var string Nome del database */
    protected $database_name;

    /** @var Medoo\Medoo Classe per la gestione dei dati tracciabile */
    protected $database;

    /** @var bool Stato di installazione del database */
    protected $is_installed;

    /**
     * Costruisce la nuova connessione al database.
     * Ispirato dal framework open-source Medoo.
     *
     * @param string|array $server
     * @param string       $username
     * @param string       $password
     * @param string       $database_name
     * @param string       $charset
     * @param array        $option
     *
     * @since 2.3
     *
     * @return Database
     */
    protected function __construct($server, $username, $password, $database_name, $charset = null)
    {
        if (is_array($server)) {
            $host = $server['host'];
            $port = !empty($server['port']) ? $server['port'] : null;
        } else {
            $temp = explode(':', $server);
            $host = $temp[0];
            $port = !empty($temp[1]) ? $temp[1] : null;
        }

        // Possibilità di specificare una porta per il servizio MySQL diversa dalla standard 3306
        $port = !empty(App::getConfig()['port']) ? App::getConfig()['port'] : $port;

        $this->host = $host;
        if (!empty($port) && is_int($port * 1)) {
            $port = $port;
        } else {
            $port = 3306;
        }

        if (!empty($host) && !empty($database_name)) {
            try {
                $this->database = new Medoo([
                    // required
                    'database_type' => 'mysql',
                    'database_name' => $database_name,
                    'server' => $host,
                    'port' => $port,
                    'username' => $username,
                    'password' => $password,
                    'charset' => 'utf8',

                    'logging' => $debug,

                    'option' => [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    ],
                ]);

                $this->database_name = $database_name;

                if (empty($charset) && version_compare($this->getMySQLVersion(), '5.5.3') >= 0) {
                    $charset = 'utf8mb4';
                }

                // Fix per problemi di compatibilità delle password MySQL 4.1+ (da versione precedente)
                $this->database->query('SET SESSION old_passwords = 0');
                //$this->database->query('SET PASSWORD = PASSWORD('.$this->prepare($password).')');

                // Impostazione del charset della comunicazione
                if (!empty($charset)) {
                    $this->database->query("SET NAMES '".$charset."'");
                }

                // Reset della modalità di esecuzione MySQL per la sessione corrente
                //$this->database->query("SET sql_mode = ''");
            } catch (PDOException $e) {
                if ($e->getCode() == 1049 || $e->getCode() == 1044) {
                    $e = new PDOException(($e->getCode() == 1049) ? tr('Database non esistente!') : tr('Credenziali di accesso invalide!'));
                }

                $this->signal($e, tr('Errore durante la connessione al database'), ['throw' => false, 'session' => false]);
            }
        }
    }

    /**
     * Restituisce la connessione attiva al database, creandola nel caso non esista.
     *
     * @since 2.3
     *
     * @return Database
     */
    public static function getConnection($new = false, $data = [])
    {
        $class = get_called_class();

        if (empty(parent::$instance[$class]) || !parent::$instance[$class]->isConnected() || $new) {
            $config = App::getConfig();

            // Sostituzione degli eventuali valori aggiuntivi
            $config = array_merge($config, $data);

            parent::$instance[$class] = new self($config['db_host'], $config['db_username'], $config['db_password'], $config['db_name']);
        }

        return parent::$instance[$class];
    }

    public static function getInstance()
    {
        return self::getConnection();
    }

    /**
     * Restituisce l'oggetto PDO artefice della connessione.
     *
     * @since 2.3
     *
     * @return \DebugBar\DataCollector\PDO\TraceablePDO
     */
    public function getPDO()
    {
        return $this->database->pdo;
    }

    /**
     * Controlla se la connessione è valida e andata a buon fine.
     *
     * @since 2.3
     *
     * @return bool
     */
    public function isConnected()
    {
        return !empty($this->getPDO());
    }

    /**
     * Controlla se il database necessario al progetto esiste.
     *
     * @since 2.3
     *
     * @return bool
     */
    public function isInstalled()
    {
        if (empty($this->is_installed)) {
            $this->is_installed = $this->isConnected() && $this->fetchNum("SHOW TABLES LIKE 'zz_modules'");
        }

        return $this->is_installed;
    }

    /**
     * Restituisce la versione del DBMS MySQL in utilizzo.
     *
     * @since 2.3
     *
     * @return int
     */
    public function getMySQLVersion()
    {
        if ($this->isConnected()) {
            return $this->database->info()['version'];
        }
    }

    /**
     * Restituisce il nome del database a cui si è connessi.
     *
     * @since 2.3
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->database_name;
    }

    /**
     * Esegue la query indicata, restituendo l'identificativo della nuova entità se si tratta di una query di inserimento.
     *
     * @since 2.0
     *
     * @param string $query Query da eseguire
     *
     * @return int
     */
    public function query($query, $signal = null, $options = [])
    {
        try {
            $this->database->query($query);

            $id = $this->lastInsertedID();
            if ($id == 0) {
                return 1;
            } else {
                return $id;
            }
        } catch (PDOException $e) {
            $signal = empty($signal) ? $query : $signal;
            $this->signal($e, $signal, $options);
        }
    }

    /**
     * Restituisce un'array strutturato in base ai nomi degli attributi della selezione.
     *
     * @since 2.0
     *
     * @param string $query Query da eseguire
     *
     * @return array
     */
    public function fetchArray($query, $numeric = false, $options = [])
    {
        try {
            $mode = empty($numeric) ? PDO::FETCH_ASSOC : PDO::FETCH_NUM;

            $result = $this->database->query($query)->fetchAll($mode);

            return $result;
        } catch (PDOException $e) {
            $this->signal($e, $query, $options);
        }
    }

    /**
     * Restituisce un'array strutturato in base agli indici degli attributi della selezione.
     *
     * @since 2.0
     * @deprecated 2.3
     *
     * @param string $query Query da eseguire
     *
     * @return array
     */
    public function fetchRows($query)
    {
        return $this->fetchArray($query, true);
    }

    /**
     * Restituisce il primo elemento della selezione, strutturato in base ai nomi degli attributi.
     *
     * @since 2.0
     * @deprecated 2.3
     *
     * @param string $query Query da eseguire
     *
     * @return array
     */
    public function fetchRow($query)
    {
        $result = $this->fetchArray($query);
        if (is_array($result)) {
            return $result[0];
        }

        return $result;
    }

    /**
     * Restituisce il numero dei risultati della selezione.
     *
     * @since 2.0
     *
     * @param string $query Query da eseguire
     *
     * @return array
     */
    public function fetchNum($query)
    {
        $result = $this->fetchArray($query);
        if (is_array($result)) {
            return count($result);
        }

        return $result;
    }

    /**
     * Restituisce l'identificativo dell'ultimo elemento inserito.
     *
     * @since 2.0
     * @deprecated 2.3
     *
     * @return int
     */
    public function last_inserted_id()
    {
        return $this->lastInsertedID();
    }

    /**
     * Restituisce l'identificativo dell'ultimo elemento inserito.
     *
     * @since 2.3
     *
     * @return int
     */
    public function lastInsertedID()
    {
        try {
            return $this->database->id();
        } catch (PDOException $e) {
            $this->signal($e, tr("Impossibile ottenere l'ultimo identificativo creato"));
        }
    }

    /**
     * Prepara il parametro inserito per l'inserimento in una query SQL.
     * Attenzione: protezione di base contro SQL Injection.
     *
     * @param string $parameter
     *
     * @since 2.3
     *
     * @return string
     */
    public function prepare($parameter)
    {
        return $this->database->quote($parameter);
    }

    /**
     * Costruisce la query per l'INSERT definito dagli argomenti.
     *
     * @since 2.3
     *
     * @param string $table
     * @param array  $data
     *
     * @return int|array
     */
    public function insert($table, $data)
    {
        try {
            $this->database->insert($table, $data);

            return $this->database->id();
        } catch (PDOException $e) {
            $this->signal($e, $this->database->last());
        }
    }

    /**
     * Costruisce la query per l'UPDATE definito dagli argomenti.
     *
     * @since 2.3
     *
     * @param string $table
     * @param array  $data
     * @param array  $conditions
     *
     * @return string|PDOStatement
     */
    public function update($table, $data, $conditions)
    {
        try {
            return $this->database->update($table, $data, $conditions);
        } catch (PDOException $e) {
            $this->signal($e, $this->database->last());
        }
    }

    /**
     * Costruisce la query per il SELECT definito dagli argomenti.
     *
     * @since 2.3
     *
     * @param string $table
     * @param array  $fields
     * @param array  $conditions
     * @param bool   $return
     *
     * @return string|array
     */
    public function select($table, $fields = [], $conditions = [], $return = false)
    {
        try {
            if (empty($return)) {
                $result = $this->database->select($table, $fields, $conditions);
            } else {
                ob_start();
                $this->database->debug()->select($table, $fields, $conditions);
                $result = ob_get_clean();
            }

            return $result;
        } catch (PDOException $e) {
            $this->signal($e, $this->database->last());
        }
    }

    /**
     * Sincronizza i valori indicati associati alle condizioni, rimuovendo le combinazioni aggiuntive e inserendo quelle non ancora presenti.
     *
     * @since 2.3
     *
     * @param string $table
     * @param array  $conditions Condizioni di sincronizzazione
     * @param array  $list       Valori da sincronizzare
     */
    public function sync($table, $conditions, $list)
    {
        if (
            !is_string($table) ||
            !is_array($conditions) ||
            !is_array($list)
            ) {
            throw new UnexpectedValueException();
        }

        $field = key($list);
        $sync = array_unique((array) current($list));

        if (!empty($field)) {
            $results = array_column($this->select($table, $field, $conditions), $field);

            $detachs = array_unique(array_diff($results, $sync));
            $this->detach($table, $conditions, [$field => $detachs]);

            $this->attach($table, $conditions, $list);
        }
    }

    /**
     * Inserisce le le combinazioni tra i valori indicati e le condizioni.
     *
     * @since 2.3
     *
     * @param string $table
     * @param array  $conditions Condizioni
     * @param array  $list       Valori da aggiungere
     */
    public function attach($table, $conditions, $list)
    {
        if (
            !is_string($table) ||
            !is_array($conditions) ||
            !is_array($list)
            ) {
            throw new UnexpectedValueException();
        }

        $field = key($list);
        $sync = array_unique((array) current($list));

        if (!empty($field)) {
            $results = array_column($this->select($table, $field, $conditions), $field);

            $inserts = array_unique(array_diff($sync, $results));
            foreach ($inserts as $insert) {
                $this->insert($table, array_merge($conditions, [$field => $insert]));
            }
        }
    }

    /**
     * Rimuove le le combinazioni tra i valori indicati e le condizioni.
     *
     * @since 2.3
     *
     * @param string $table
     * @param array  $conditions Condizioni
     * @param array  $list       Valori da rimuovere
     */
    public function detach($table, $conditions, $list)
    {
        if (
            !is_string($table) ||
            !is_array($conditions) ||
            !is_array($list)
            ) {
            throw new UnexpectedValueException();
        }

        $field = key($list);
        $sync = array_unique((array) current($list));

        if (!empty($field) && !empty($sync)) {
            $conditions[$field] = $sync;

            $database->delete($table, $conditions);
        }
    }

    /**
     * Esegue le query interne ad un file .sql.
     *
     * @since 2.0
     *
     * @param string $filename  Percorso per raggiungere il file delle query
     * @param string $delimiter Delimitatore delle query
     */
    public function multiQuery($filename, $start = 0)
    {
        $queries = readSQLFile($filename, ';');
        $end = count($queries);

        for ($i = $start; $i < $end; ++$i) {
            try {
                $this->database->query($queries[$i]);
            } catch (PDOException $e) {
                $this->signal($e, $queries[$i], [
                    'throw' => false,
                ]);

                return $i;
            }
        }

        return true;
    }

    /**
     * Aggiunge informazioni alla struttura di base dell'erroe o dell'eccezione intercettata.
     *
     * @since 2.3
     */
    protected function signal($e, $message, $options = [])
    {
        global $logger;

        $options = array_merge([
            'session' => true,
            'level' => \Monolog\Logger::ERROR,
            'throw' => true,
        ], $options);

        if (!empty($options['session'])) {
            $msg = tr("Si è verificato un'errore").'.';

            if (Auth::check()) {
                $msg .= ' '.tr('Se il problema persiste siete pregati di chiedere assistenza tramite la sezione Bug').'. <a href="'.ROOTDIR.'/bug.php"><i class="fa fa-external-link"></i></a>';
            }

            $msg .= '<br><small>'.$e->getMessage().'</small>';

            $_SESSION['errors'][] = $msg;
        }

        $error = $e->getMessage().' - '.$message;

        if (!empty($options['throw'])) {
            throw new PDOException($error);
        } else {
            $logger->addRecord($options['level'], $error);
        }
    }
}
