<?php

/**
 * Classe per la gestione delle API del progetto.
 *
 * @since 2.3
 */
class API extends \Util\Singleton
{
    /** @var array Elenco delle risorse disponibili suddivise per categoria */
    protected static $resources;

    /** @var array Stati previsti dall'API */
    protected static $status = [
        'ok' => [
            'code' => 200,
            'message' => 'OK',
        ],
        'internalError' => [
            'code' => 400,
            'message' => "Errore interno dell'API",
        ],
        'unauthorized' => [
            'code' => 401,
            'message' => 'Non autorizzato',
        ],
        'notFound' => [
            'code' => 404,
            'message' => 'Non trovato',
        ],
        'serverError' => [
            'code' => 500,
            'message' => 'Errore del server',
        ],
        'incompatible' => [
            'code' => 503,
            'message' => 'Servizio non disponibile',
        ],
    ];

    /**
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        if (!self::isAPIRequest() || (!Auth::check() && self::getRequest()['resource'] != 'login')) {
            throw new InvalidArgumentException();
        }
    }

    /**
     * Gestisce le richieste di informazioni riguardanti gli elementi esistenti.
     *
     * @param array $request
     *
     * @return string
     */
    public function retrieve($request)
    {
        $user = Auth::user();

        // Controllo sulla compatibilità dell'API
        if (!self::isCompatible()) {
            return self::response([
                'status' => self::$status['incompatible']['code'],
            ]);
        }

        $response = [];

        $table = '';
        $select = '*';
        $where = [];
        $order = [];

        // Selezione personalizzata
        $select = !empty($request['display']) ? explode(',', substr($request['display'], 1, -1)) : $select;

        // Ricerca personalizzata
        $values = isset($request['filter']) ? (array) $request['filter'] : [];
        foreach ($values as $key => $value) {
            // Rimozione delle parentesi
            $value = substr($value, 1, -1);

            // Individuazione della tipologia (array o string)
            $where[$key] = str_contains($value, ',') ? explode(',', $value) : $value;
        }

        // Ordinamento personalizzato
        $values = isset($request['order']) ? (array) $request['order'] : [];
        foreach ($values as $value) {
            $pieces = explode('|', $value);
            $order[] = empty($pieces[1]) ? $pieces[0] : [$pieces[0] => $pieces[1]];
        }

        // Paginazione automatica dell'API
        $page = isset($request['page']) ? (int) $request['page'] : 0;
        $length = setting('Lunghezza pagine per API');

        $dbo = $database = Database::getConnection();

        $kind = 'retrieve';
        $resources = self::getResources()[$kind];
        $resource = $request['resource'];

        try {
            if (in_array($resource, array_keys($resources))) {
                // Inclusione funzioni del modulo
                include_once App::filepath('modules/'.$resources[$resource].'|custom|', 'modutil.php');

                // Esecuzione delle operazioni personalizzate
                $filename = DOCROOT.'/modules/'.$resources[$resource].'/api/'.$kind.'.php';
                include $filename;
            } elseif (
                !in_array($resource, explode(',', setting('Tabelle escluse per la sincronizzazione API automatica')))
                && $database->tableExists($resource)
            ) {
                $table = $resource;

                // Individuazione della colonna AUTO_INCREMENT per l'ordinamento automatico
                if (empty($order)) {
                    $column = $database->fetchArray('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '.prepare($table)." AND EXTRA LIKE '%AUTO_INCREMENT%' AND TABLE_SCHEMA = ".prepare($database->getDatabaseName()));

                    if (!empty($column)) {
                        $order[] = $column[0]['COLUMN_NAME'];
                    }
                }
            } else {
                return self::error('notFound');
            }

            // Generazione automatica delle query
            if (!empty($table)) {
                // Date di interesse
                if (!empty($request['upd'])) {
                    $where['#updated_at'] = 'updated_at >= '.prepare($request['upd']);
                }
                if (!empty($request['crd'])) {
                    $where['#created_at'] = 'created_at >= '.prepare($request['crd']);
                }

                // Query per ottenere le informazioni
                $query = $database->select($table, $select, $where, $order, [], true);
            }

            $response['records'] = $database->fetchArray($query.' LIMIT '.($page * $length).', '.$length, $parameters);
            $count = $database->fetchNum($query, $parameters);

            $response['total-count'] = $count;
            $response['pages'] = ceil($count / $length);
        } catch (PDOException $e) {
            // Log dell'errore
            $logger = logger();
            $logger->addRecord(\Monolog\Logger::ERROR, $e);

            return self::error('internalError');
        }

        return self::response($response);
    }

    /**
     * Gestisce le richieste di creazione nuovi elementi.
     *
     * @param array $request
     *
     * @return string
     */
    public function create($request)
    {
        return $this->fileRequest($request, 'create');
    }

    /**
     * Gestisce le richieste di aggiornamento di elementi esistenti.
     *
     * @param array $request
     *
     * @return string
     */
    public function update($request)
    {
        return $this->fileRequest($request, 'update');
    }

    /**
     * Gestisce le richieste di eliminazione di elementi esistenti.
     *
     * @param array $request
     *
     * @return string
     */
    public function delete($request)
    {
        return $this->fileRequest($request, 'delete');
    }

    /**
     * Gestisce le richieste in modo generalizzato, con il relativo richiamo ai file specifici responsabili dell'operazione.
     *
     * @param array $request
     *
     * @return string
     */
    protected function fileRequest($request, $kind)
    {
        $user = Auth::user();
        $response = [];

        // Controllo sulla compatibilità dell'API
        if (!self::isCompatible()) {
            return self::response([
                'status' => self::$status['incompatible']['code'],
            ]);
        }

        $resources = self::getResources()[$kind];
        $resource = $request['resource'];

        if (!in_array($resource, array_keys($resources))) {
            return self::error('notFound');
        }

        // Inclusione funzioni del modulo
        include_once App::filepath('modules/'.$resources[$resource].'|custom|', 'modutil.php');

        // Database
        $dbo = $database = Database::getConnection();

        $database->beginTransaction();

        // Esecuzione delle operazioni
        $filename = DOCROOT.'/modules/'.$resources[$resource].'/api/'.$kind.'.php';
        include $filename;

        $database->commitTransaction();

        return self::response($response);
    }

    /**
     * Genera i contenuti di risposta nel caso si verifichi un errore.
     *
     * @param string|int $error
     *
     * @return string
     */
    public static function error($error)
    {
        $keys = array_keys(self::$status);
        $error = (in_array($error, $keys)) ? $error : 'serverError';

        $code = self::$status[$error]['code'];

        http_response_code($code);

        return self::response([
            'status' => $code,
        ]);
    }

    /**
     * Restituisce l'elenco delle risorse disponibili per l'API, suddivise per categoria.
     *
     * @return array
     */
    public static function getResources()
    {
        if (!is_array(self::$resources)) {
            $resources = [];

            // Ignore dei warning
            $resource = '';

            // File nativi
            $files = glob(DOCROOT.'/modules/*/api/{retrieve,create,update,delete}.php', GLOB_BRACE);

            // File personalizzati
            $custom_files = glob(DOCROOT.'/modules/*/custom/api/{retrieve,create,update,delete}.php', GLOB_BRACE);

            // Pulizia dei file nativi che sono stati personalizzati
            foreach ($custom_files as $key => $value) {
                $index = array_search(str_replace('custom/api/', 'api/', $value), $files);
                if ($index !== false) {
                    unset($files[$index]);
                }
            }

            $operations = array_merge($files, $custom_files);
            asort($operations);

            foreach ($operations as $operation) {
                // Individua la tipologia e il modulo delle operazioni
                $module = basename(dirname(dirname($operation)));
                $kind = basename($operation, '.php');

                $resources[$kind] = isset($resources[$kind]) ? (array) $resources[$kind] : [];

                // Individuazione delle operazioni
                $api = include $operation;
                $api = is_array($api) ? array_unique($api) : [];

                $keys = array_keys($resources[$kind]);

                // Registrazione delle operazioni individuate
                $results = [];
                foreach ($api as $value) {
                    $value .= in_array($value, $keys) ? $module : '';
                    $results[$value] = $module;
                }

                // Salvataggio delle operazioni
                $resources[$kind] = array_merge($resources[$kind], $results);
            }

            self::$resources = $resources;
        }

        return self::$resources;
    }

    /**
     * Formatta i contentuti della risposta secondo il formato JSON.
     *
     * @param array $array
     *
     * @return string
     */
    public static function response($array)
    {
        if (empty($array['custom'])) {
            // Agiunta dello status di default
            if (empty($array['status'])) {
                $array['status'] = self::$status['ok']['code'];
            }

            // Aggiunta del messaggio in base allo status
            if (empty($array['message'])) {
                $codes = array_column(self::$status, 'code');
                $messages = array_column(self::$status, 'message');

                $array['message'] = $messages[array_search($array['status'], $codes)];
            }

            $flags = JSON_FORCE_OBJECT;
            // Beautify forzato dei risultati
            if (get('beautify') !== null) {
                $flags |= JSON_PRETTY_PRINT;
            }

            $result = json_encode($array, $flags);
        } else {
            $result = $array['custom'];
        }

        return $result;
    }

    /**
     * Restituisce l'elenco degli stati dell'API.
     *
     * @return array
     */
    public static function getStatus()
    {
        return self::$status;
    }

    /**
     * Controlla se la richiesta effettuata è rivolta all'API.
     *
     * @return bool
     */
    public static function isAPIRequest()
    {
        return getURLPath() == slashes(ROOTDIR.'/api/index.php');
    }

    /**
     * Restituisce i parametri specificati dalla richiesta.
     *
     * @param bool $raw
     *
     * @return array
     */
    public static function getRequest($raw = false)
    {
        $request = [];

        if (self::isAPIRequest()) {
            $request = file_get_contents('php://input');

            if (empty($raw)) {
                $request = (array) json_decode($request, true);
                $request = Filter::sanitize($request);

                // Fallback per input standard vuoto (richiesta da browser o upload file)
                if (empty($request)) { // $_SERVER['REQUEST_METHOD'] == 'GET'
                    $request = Filter::getGET();
                }

                if (empty($request['token'])) {
                    $request['token'] = '';
                }
            }
        }

        return $request;
    }

    /**
     * Controlla se il database è compatibile con l'API.
     *
     * @return bool
     */
    public static function isCompatible()
    {
        $database = Database::getConnection();

        return version_compare($database->getMySQLVersion(), '5.6.5') >= 0;
    }
}
