<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Classe per gestire la connessione al database.
 *
 * @since 2.3
 */
class Database extends Util\Singleton
{
    /** @var \Illuminate\Database\Capsule\Manager Gestore di connessione Laravel */
    protected $capsule;

    /** @var string Nome del database */
    protected $database_name;

    /** @var bool Stato di connessione del database */
    protected $is_connected;
    /** @var bool Stato di installazione del database */
    protected $is_installed;

    /** @var string Versione corrente di MySQL */
    protected $mysql_version;

    /**
     * Costruisce la nuova connessione al database.
     * Ispirato dal framework open-source Medoo.
     *
     * @param string|array $server
     * @param string       $username
     * @param string       $password
     * @param string       $database_name
     * @param string       $charset
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

        $this->database_name = $database_name;

        if (!empty($host) && !empty($database_name)) {
            try {
                // Istanziamento di Eloquent
                $this->capsule = new Capsule();
                $this->capsule->addConnection([
                    'driver' => 'mysql',
                    'host' => $host,
                    'database' => $database_name,
                    'username' => $username,
                    'password' => $password,
                    'charset' => 'utf8',
                    'prefix' => '',
                    'port' => $port,
                ]);

                $this->is_connected = !empty($this->getPDO());

                // Impostazione del charset della comunicazione
                if (empty($charset) && version_compare($this->getMySQLVersion(), '5.5.3') >= 0) {
                    $this->getPDO()->exec("SET NAMES 'utf8mb4'");
                }

                // Fix per problemi di compatibilità delle password MySQL 4.1+ (da versione precedente)
                $this->getPDO()->exec('SET SESSION old_passwords = 0');
                //$this->getPDO()->exec('SET PASSWORD = PASSWORD('.$this->prepare($this->password).')');

                // Reset della modalità di esecuzione MySQL per la sessione corrente
                $this->getPDO()->exec("SET sql_mode = ''");

                $this->capsule->setAsGlobal();
                $this->capsule->bootEloquent();
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
     * @return \DebugBar\DataCollector\PDO\TraceablePDO|PDO
     */
    public function getPDO()
    {
        return $this->capsule->getConnection()->getPDO();
    }

    public function getCapsule()
    {
        return $this->capsule;
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
        return $this->is_connected;
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
            $this->is_installed = $this->tableExists('zz_modules');
        }

        return $this->is_installed;
    }

    /**
     * Restituisce la versione del DBMS MySQL in utilizzo.
     *
     * @since 2.3
     *
     * @return string
     */
    public function getMySQLVersion()
    {
        if (empty($this->mysql_version) && $this->isConnected()) {
            $ver = $this->fetchArray('SELECT VERSION()');
            if (!empty($ver[0]['VERSION()'])) {
                $this->mysql_version = explode('-', $ver[0]['VERSION()'])[0];
            }
        }

        return $this->mysql_version;
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
    public function query($query, $parameters = [], $signal = null, $options = [])
    {
        try {
            $statement = $this->getPDO()->prepare($query);
            $statement->execute($parameters);

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
    public function fetchArray($query, $parameters = [], $numeric = false, $options = [])
    {
        try {
            $mode = empty($numeric) ? PDO::FETCH_ASSOC : PDO::FETCH_NUM;

            $statement = $this->getPDO()->prepare($query);
            $statement->execute($parameters);

            $result = $statement->fetchAll($mode);

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
        return $this->fetchArray($query, [], true);
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
        return $this->fetchOne($query);
    }

    /**
     * Restituisce il primo elemento della selezione, strutturato in base ai nomi degli attributi.
     * Attenzione: aggiunge il LIMIT relativo a fine della query.
     *
     * @since 2.4
     *
     * @param string $query Query da eseguire
     *
     * @return array
     */
    public function fetchOne($query, $parameters = [])
    {
        if (!str_contains($query, 'LIMIT')) {
            $query .= ' LIMIT 1';
        }

        $result = $this->fetchArray($query, $parameters);

        if (isset($result[0])) {
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
     * @return int
     */
    public function fetchNum($query, $parameters = [])
    {
        $result = $this->fetchArray('SELECT COUNT(*) as `tot` FROM ('.$query.') AS `count`', $parameters);

        if (!empty($result)) {
            return $result[0]['tot'];
        }

        return 0;
    }

    public function tableExists($table)
    {
        if ($this->isConnected()) {
            return $this->capsule->schema()->hasTable($table);
        }

        return null;
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
            return $this->getPDO()->lastInsertId();
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
     * @return mixed
     */
    public function prepare($parameter)
    {
        return $this->getPDO()->quote($parameter);
    }

    /**
     * Prepara il campo per l'inserimento in uno statement SQL.
     *
     * @since 2.3
     *
     * @param string $value
     *
     * @return string
     */
    protected function quote($string)
    {
        $char = '`';

        return $char.str_replace([$char, '#'], '', $string).$char;
    }

    /**
     * Costruisce la query per l'INSERT definito dagli argomenti.
     *
     * @since 2.3
     *
     * @param string $table
     * @param array  $array
     *
     * @return string|array
     */
    public function insert($table, $array)
    {
        if (!is_string($table) || !is_array($array)) {
            throw new UnexpectedValueException();
        }

        if (!isset($array[0]) || !is_array($array[0])) {
            $array = [$array];
        }

        return Capsule::table($table)->insert($array);
    }

    /**
     * Costruisce la query per l'UPDATE definito dagli argomenti.
     *
     * @since 2.3
     *
     * @param string $table
     * @param array  $array
     * @param array  $conditions
     * @param bool   $return
     *
     * @return string|array
     */
    public function update($table, $array, $conditions, $return = false)
    {
        if (!is_string($table) || !is_array($array) || !is_array($conditions)) {
            throw new UnexpectedValueException();
        }

        // Valori da aggiornare
        $update = [];
        foreach ($array as $key => $value) {
            $update[] = $this->quote($key).' = '.$this->prepareValue($key, $value);
        }

        // Costruzione della query
        $query = 'UPDATE '.$this->quote($table).' SET '.implode($update, ', ').' WHERE '.$this->whereStatement($conditions);

        if (!empty($return)) {
            return $query;
        } else {
            return $this->query($query);
        }
    }

    /**
     * Costruisce la query per il SELECT definito dagli argomenti.
     *
     * @since 2.3
     *
     * @param string       $table
     * @param array        $array
     * @param array        $conditions
     * @param array        $order
     * @param string|array $limit
     * @param bool         $return
     *
     * @return string|array
     */
    public function select($table, $array = [], $conditions = [], $order = [], $limit = null, $return = false)
    {
        if (
            !is_string($table) ||
            (!empty($order) && !is_string($order) && !is_array($order)) ||
            (!empty($limit) && !is_string($limit) && !is_integer($limit) && !is_array($limit))
        ) {
            throw new UnexpectedValueException();
        }

        // Valori da ottenere
        $select = [];
        foreach ((array) $array as $key => $value) {
            $select[] = $value.(is_numeric($key) ? '' : ' AS '.$this->quote($key));
        }
        $select = !empty($select) ? $select : ['*'];

        // Costruzione della query
        $query = 'SELECT '.implode(', ', $select).' FROM '.$this->quote($table);

        // Condizioni di selezione
        $where = $this->whereStatement($conditions);
        if (!empty($where)) {
            $query .= ' WHERE '.$where;
        }

        // Impostazioni di ordinamento
        if (!empty($order)) {
            $list = [];
            $allow = ['ASC', 'DESC'];
            foreach ((array) $order as $key => $value) {
                if (is_numeric($key)) {
                    $key = $value;
                    $value = $allow[0];
                }

                $value = in_array($value, $allow) ? $value : $allow[0];
                $list[] = $this->quote($key).' '.$value;
            }

            $query .= ' ORDER BY '.implode(', ', $list);
        }

        // Eventuali limiti
        if (!empty($limit)) {
            $query .= ' LIMIT '.(is_array($limit) ? $limit[0].', '.$limit[1] : $limit);
        }

        if (!empty($return)) {
            return $query;
        } else {
            return $this->fetchArray($query);
        }
    }

    /**
     * Costruisce la query per il SELECT definito dagli argomenti (LIMIT 1).
     *
     * @since 2.4.1
     *
     * @param string $table
     * @param array  $array
     * @param array  $conditions
     * @param array  $order
     * @param bool   $return
     *
     * @return string|array
     */
    public function selectOne($table, $array = [], $conditions = [], $order = [], $return = false)
    {
        $limit = 1;

        $result = $this->select($table, $array, $conditions, $order, $limit, $return);

        if (!is_string($result) && isset($result[0])) {
            return $result[0];
        }

        return $result;
    }

    /**
     * Costruisce la query per l'DELETE definito dagli argomenti.
     *
     * @since 2.4.1
     *
     * @param string $table
     * @param array  $conditions
     * @param bool   $return
     *
     * @return string|array
     */
    public function delete($table, $conditions, $return = false)
    {
        if (!is_string($table) || !is_array($conditions)) {
            throw new UnexpectedValueException();
        }

        // Costruzione della query
        $query = 'DELETE FROM '.$this->quote($table).' WHERE '.$this->whereStatement($conditions);

        if (!empty($return)) {
            return $query;
        } else {
            return $this->query($query);
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

            $this->delete($table, $conditions);
        }
    }

    /**
     * Predispone una variabile per il relativo inserimento all'interno di uno statement SQL.
     *
     * @since 2.3
     *
     * @param string $value
     *
     * @return string
     */
    protected function prepareValue($field, $value)
    {
        $value = (is_null($value)) ? 'NULL' : $value;
        $value = is_bool($value) ? intval($value) : $value;

        if (!starts_with($field, '#')) {
            if ($value != 'NULL') {
                $value = $this->prepare($value);
            }
        }

        return $value;
    }

    /**
     * Predispone il contenuto di un array come clausola WHERE.
     *
     * @since 2.3
     *
     * @param string|array $where
     * @param bool         $and
     *
     * @return string
     */
    protected function whereStatement($where, $and = true)
    {
        $result = [];

        foreach ($where as $key => $value) {
            // Query personalizzata
            if (starts_with($key, '#')) {
                $result[] = $this->prepareValue($key, $value);
            } else {
                // Ulteriori livelli di complessità
                if (is_array($value) && in_array(strtoupper($key), ['AND', 'OR'])) {
                    $result[] = '('.$this->whereStatement($value, $key == 'AND').')';
                }
                // Condizione IN
                elseif (is_array($value)) {
                    if (!empty($value)) {
                        $in = [];
                        foreach ($value as $v) {
                            $in[] = $this->prepareValue($key, $v);
                        }

                        $result[] = $this->quote($key).' IN ('.implode(',', $in).')';
                    }
                }
                // Condizione LIKE
                elseif (str_contains($value, '%') || str_contains($value, '_')) {
                    $result[] = $this->quote($key).' LIKE '.$this->prepareValue($key, $value);
                }
                // Condizione BETWEEN
                elseif (str_contains($value, '|')) {
                    $pieces = explode('|', $value);
                    $result[] = $this->quote($key).' BETWEEN '.$this->prepareValue($key, $pieces[0]).' AND '.$this->prepareValue($key, $pieces[1]);
                }
                // Condizione di uguaglianza
                else {
                    $prepared = $this->prepareValue($key, $value);

                    if ($prepared == 'NULL') {
                        $result[] = $this->quote($key).' IS '.$prepared;
                    } else {
                        $result[] = $this->quote($key).' = '.$prepared;
                    }
                }
            }
        }

        $cond = !empty($and) ? 'AND' : 'OR';

        return implode(' '.$cond.' ', $result);
    }

    public function beginTransaction()
    {
        Capsule::beginTransaction();
    }

    public function commitTransaction()
    {
        Capsule::commit();
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
                $this->getPDO()->exec($queries[$i]);
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
        $options = array_merge([
            'session' => true,
            'level' => \Monolog\Logger::ERROR,
            'throw' => true,
        ], $options);

        if (!empty($options['session']) && !API::isAPIRequest()) {
            $msg = tr("Si è verificato un'errore").'.';

            if (Auth::check()) {
                $msg .= ' '.tr('Se il problema persiste siete pregati di chiedere assistenza tramite la sezione Bug').'. <a href="'.ROOTDIR.'/bug.php"><i class="fa fa-external-link"></i></a>';
            }

            $msg .= '<br><small>'.$e->getMessage().'</small>';

            flash()->error($msg);
        }

        $error = $e->getMessage().' - '.$message;

        if (!empty($options['throw'])) {
            throw new PDOException($error);
        } else {
            $logger = logger();

            $logger->addRecord($options['level'], $error);
        }
    }
}
