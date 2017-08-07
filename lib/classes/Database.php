<?php

/**
 * Classe per gestire la connessione al database.
 *
 * @since 2.3
 */
class Database extends Util\Singleton
{
    protected $host;
    protected $port;
    protected $username;
    protected $password;
    protected $database_name;

    protected $charset;
    protected $option = [];

    protected static $connection;
    protected $pdo;

    protected $is_installed;
    protected $mysql_version;

    /**
     * Costruisce la nuova connessione al database.
     * Basato sul framework open source Medoo.
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
    protected function __construct($server, $username, $password, $database_name, $charset = 'utf8mb4', $option = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION])
    {
        if (is_array($server)) {
            $host = $server['host'];
            $port = !empty($server['port']) ? $server['port'] : null;
        } else {
            $temp = explode(':', $server);
            $host = $temp[0];
            $port = !empty($temp[1]) ? $temp[1] : null;
        }

        $this->host = $host;
        if (!empty($port) && is_int($port * 1)) {
            $this->port = $port;
        }

        $this->username = $username;
        $this->password = $password;
        $this->database_name = $database_name;

        $this->charset = $charset;
        $this->option = $option;

        if (!empty($this->host) && !empty($this->database_name)) {
            try {
                $this->pdo = new \DebugBar\DataCollector\PDO\TraceablePDO(new PDO(
                    'mysql:host='.$this->host.(!empty($this->port) ? ';port='.$this->port : '').';dbname='.$this->database_name,
                    $this->username,
                    $this->password,
                    $this->option
                ));

                $this->query("SET NAMES '".$this->charset."'");
                $this->query("SET sql_mode = ''");
            } catch (PDOException $e) {
                if ($e->getCode() == 1049 || $e->getCode() == 1044) {
                    $e = new PDOException(($e->getCode() == 1049) ? _('Database non esistente!') : _('Credenziali di accesso invalide!'));
                }

                $this->signal($e, _('Errore durante la connessione al database'), ['throw' => false, 'session' => false]);
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
    public static function getConnection($new = false)
    {
        $class = get_called_class(); // late-static-bound class name

        if (empty(parent::$instance[$class]) || !parent::$instance[$class]->isConnected() || $new) {
            global $db_host;
            global $db_username;
            global $db_password;
            global $db_name;

            parent::$instance[$class] = new self($db_host, $db_username, $db_password, $db_name);
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
        return $this->pdo;
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
        return !empty($this->pdo);
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
    public function query($query, $signal = null, $options = [])
    {
        try {
            $this->pdo->query($query);

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
    public function fetchArray($query, $numeric = false)
    {
        try {
            $mode = empty($numeric) ? PDO::FETCH_ASSOC : PDO::FETCH_NUM;

            $result = $this->pdo->query($query)->fetchAll($mode);

            return $result;
        } catch (PDOException $e) {
            $this->signal($e, $query);
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
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->signal($e, _("Impossibile ottenere l'ultimo identificativo creato"));
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
        return $this->pdo->quote($parameter);
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
        return '`'.str_replace('`', '', $string).'`';
    }

    /**
     * Costruisce la query per l'INSERT definito dagli argomenti.
     *
     * @since 2.3
     *
     * @param string $table
     * @param array  $array
     * @param bool   $return
     *
     * @return string|array
     */
    public function insert($table, $array, $return = false)
    {
        if (!is_string($table) || !is_array($array)) {
            throw new UnexpectedValueException();
        }

        if (!is_array($array[0])) {
            $array = [$array];
        }

        $keys = [];
        $temp = array_keys($array[0]);
        foreach ($temp as $value) {
            $keys[] = $this->quote($value);
        }

        $inserts = [];
        foreach ($array as $values) {
            foreach ($values as $key => $value) {
                $values[$key] = $this->getValue($value);
            }

            $inserts[] = '('.implode(array_values($values), ', ').')';
        }

        $query = 'INSERT INTO '.$this->quote($table).' ('.implode(',', $keys).') VALUES '.implode($inserts, ', ');

        if (!empty($return)) {
            return $query;
        } else {
            return $this->query($query);
        }
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

        $update = [];
        foreach ($array as $key => $value) {
            $update[] = $this->quote($key).' = '.$this->getValue($value);
        }

        $where = [];
        foreach ($conditions as $key => $value) {
            $where[] = $this->quote($key).' = '.$this->getValue($value);
        }

        $query = 'UPDATE '.$this->quote($table).' SET '.implode($update, ', ').' WHERE '.implode($where, ' AND ');

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
            (!empty($order) && !is_string($order) && !is_array($limit)) ||
            (!empty($limit) && !is_string($limit) && !is_array($limit))
        ) {
            throw new UnexpectedValueException();
        }

        $select = [];
        foreach ((array) $array as $key => $value) {
            $select[] = $value.(is_numeric($key) ? '' : 'AS '.$this->quote($key));
        }
        $select = !empty($select) ? $select : ['*'];

        $query = 'SELECT '.implode(', ', $select).' FROM '.$this->quote($table);

        $where = $this->whereStatement($conditions);
        if (!empty($where)) {
            $query .= ' WHERE '.$where;
        }

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
     * Predispone una variabile per il relativo inserimento all'interno di uno statement SQL.
     *
     * @since 2.3
     *
     * @param string $value
     *
     * @return string
     */
    protected function getValue($value)
    {
        $value = (is_null($value)) ? 'NULL' : $value;
        $value = is_bool($value) ? intval($value) : $value;

        if (starts_with($value, '#') && ends_with($value, '#')) {
            $value = substr($value, 1, -1);
        } elseif ($value != 'NULL') {
            $value = $this->prepare($value);
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

        if (is_array($where)) {
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    $key = strtoupper($key);
                    $key = (in_array($key, ['AND', 'OR'])) ? $key : 'AND';

                    $result[] = '('.$this->whereStatement($value, $key == 'AND').')';
                } elseif (starts_with($value, '#') && ends_with($value, '#')) {
                    $result[] = substr($value, 1, -1);
                } elseif (starts_with($value, '%') || ends_with($value, '%')) {
                    $result[] = $this->quote($key).' LIKE '.$this->getValue($value);
                } elseif (str_contains($value, '|')) {
                    $pieces = explode('|', $value);
                    $result[] = $this->quote($key).' BETWEEN '.$this->getValue($pieces[0]).' AND '.$this->getValue($pieces[1]);
                } else {
                    $result[] = $this->quote($key).' = '.$this->getValue($value);
                }
            }
        } else {
            $result[] = $where;
        }

        $cond = !empty($and) ? 'AND' : 'OR';

        return implode(' '.$cond.' ', $result);
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
                $this->pdo->query($queries[$i]);
            } catch (PDOException $e) {
                $this->signal($e, _('Aggiornamento fallito').': '.$queries[$i], [
                    'level' => \Monolog\Logger::EMERGENCY,
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
            $msg = _("Si è verificato un'errore").'.';

            if (Auth::check()) {
                $msg .= ' '._('Se il problema persiste siete pregati di chiedere assistenza tramite la sezione Bug').'. <a href="'.ROOTDIR.'/bug.php"><i class="fa fa-external-link"></i></a>';
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
