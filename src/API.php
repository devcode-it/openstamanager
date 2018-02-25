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

        $table = '';
        $select = '*';
        $where = [];
        $order = [];

        // Selezione personalizzata
        $select = !empty($request['display']) ? explode(',', substr($request['display'], 1, -1)) : $select;

        // Ricerca personalizzata
        foreach ((array) $request['filter'] as $key => $value) {
            // Rimozione delle parentesi
            $value = substr($value, 1, -1);

            // Individuazione della tipologia (array o string)
            $where[$key] = str_contains($value, ',') ? explode(',', $value) : $value;
        }

        // Ordinamento personalizzato
        foreach ((array) $request['order'] as $value) {
            $pieces = explode('|', $value);
            $order[] = empty($pieces[1]) ? $pieces[0] : [$pieces[0] => $pieces[1]];
        }

        // Paginazione automatica dell'API
        $page = (int) $request['page'] ?: 0;
        $length = Settings::get('Lunghezza pagine per API');

        $database = Database::getConnection();

        $kind = 'retrieve';
        $resources = self::getResources()[$kind];
        $resource = $request['resource'];

        if (in_array($resource, array_keys($resources))) {
            $dbo = $database;

            // Esecuzione delle operazioni personalizzate
            $filename = DOCROOT.'/modules/'.$resources[$resource].'/api/'.$kind.'.php';
            include $filename;
        } elseif (!in_array($resource, explode(',', Settings::get('Tabelle escluse per la sincronizzazione API automatica')))) {
            $table = $resource;

            // Individuazione della colonna AUTO_INCREMENT per l'ordinamento automatico
            if (empty($order)) {
                $order[] = $database->fetchArray('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '.prepare($table)." AND EXTRA LIKE '%AUTO_INCREMENT%' AND TABLE_SCHEMA = ".prepare($database->getDatabaseName()))[0]['COLUMN_NAME'];
            }
        }

        // Generazione automatica delle query
        if (empty($results) && !empty($table)) {
            try {
                // Date di interesse
                if (!empty($request['upd'])) {
                    $where['#updated_at'] = 'updated_at >= '.prepare($request['upd']);
                }
                if (!empty($request['crd'])) {
                    $where['#created_at'] = 'created_at >= '.prepare($request['crd']);
                }

                // Query per ottenere le informazioni
                $results = $database->select($table, $select, array_merge($where, [
                    'ORDER' => $order,
                    'LIMIT' => [$page * $length, $length],
                ]));

                // Informazioni aggiuntive
                $query = $database->select($table, $select, $where, true);
                $cont = $database->fetchArray('SELECT COUNT(*) as `records`, CEIL(COUNT(*) / '.$length.') as `pages` FROM ('.$query.') AS `count`');
                if (!empty($cont)) {
                    $results['records'] = $cont[0]['records'];
                    $results['pages'] = $cont[0]['pages'];
                }
            } catch (PDOException $e) {
                return self::error('internalError');
            }
        }

        return self::response($results);
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

        // Database
        $database = Database::getConnection();
        $dbo = $database;

        $database->query('START TRANSACTION');

        // Esecuzione delle operazioni
        $filename = DOCROOT.'/modules/'.$resources[$resource].'/api/'.$kind.'.php';
        include $filename;

        $database->query('COMMIT');

        return self::response($results);
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

            $operations = glob(DOCROOT.'/modules/*/api/{retrieve,create,update,delete}.php', GLOB_BRACE);
            foreach ($operations as $operation) {
                // Individua la tipologia e il modulo delle operazioni
                $module = basename(dirname(dirname($operation)));
                $kind = basename($operation, '.php');

                $resources[$kind] = (array) $resources[$kind];

                // Controllo sulla presenza di eventuali personalizzazioni
                $temp = str_replace('/api/', '/custom/api/', $operation);
                $operation = file_exists($temp) ? $temp : $operation;

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
     */
    public static function getRequest()
    {
        $request = [];

        if (self::isAPIRequest()) {
            $request = (array) json_decode(file_get_contents('php://input'), true);

            // Fallback nel caso la richiesta sia effettuata da browser
            if ($_SERVER['REQUEST_METHOD'] == 'GET' && empty($request)) {
                $request = Filter::getGET();
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
