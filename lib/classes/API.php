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
        $user = Auth::user();

        if (!self::isAPIRequest() || empty($user)) {
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
        $table = '';

        $select = '*';
        // Selezione personalizzata
        $display = $request['display'];
        $select = !empty($display) ? explode(',', substr($display, 1, -1)) : $select;

        $where = [];
        // Ricerca personalizzata
        $filter = (array) $request['filter'];
        foreach ($filter as $key => $value) {
            $value = substr($value, 1, -1);
            $result = [];

            if (str_contains($value, ',')) {
                $or = [];

                $temp = explode(',', $value);
                foreach ($temp as $value) {
                    $or[] = [$key => $value];
                }

                $result[] = ['OR' => $or];
            } else {
                $result[$key] = $value;
            }

            $where[] = $result;
        }

        $order = [];
        // Ordinamento personalizzato
        $order_request = (array) $request['order'];
        foreach ($order_request as $value) {
            $pieces = explode('|', $value);
            $order[] = empty($pieces[1]) ? $pieces[0] : [$pieces[0] => $pieces[1]];
        }

        // Date di interesse
        $updated = $request['upd'];
        $created = $request['crd'];

        $dbo = Database::getConnection();

        $kind = 'retrieve';
        $resources = self::getResources()[$kind];
        $resource = $request['resource'];

        if (!in_array($resource, $resources)) {
            $excluded = explode(',', Settings::get('Tabelle escluse per la sincronizzazione API automatica'));
            if (!in_array($resource, $excluded)) {
                $table = $resource;

                if (empty($order)) {
                    $order[] = $dbo->fetchArray('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '.prepare($table)." AND EXTRA LIKE '%AUTO_INCREMENT%' AND TABLE_SCHEMA = ".prepare($dbo->getDatabaseName()))[0]['COLUMN_NAME'];
                }
            }
        } else {
            $filename = DOCROOT.'/modules/'.$resources[$resource].'/api/'.$kind.'.php';
            include $filename;
        }

        // Paginazione dell'API
        $page = (int) $request['page'] ?: 0;
        $length = Settings::get('Lunghezza pagine per API');

        // Generazione automatica delle query
        if (empty($results) && !empty($table)) {
            try {
                // Query per ottenere le informazioni
                $results = $dbo->select($table, $select, $where, $order, [$page * $length, $length]);

                // Informazioni aggiuntive
                $query = $dbo->select($table, $select, $where, $order, [], true);
                $cont = $dbo->fetchArray('SELECT COUNT(*) as `records`, CEIL(COUNT(*) / '.$length.') as `pages` FROM ('.$query.') AS `count`');
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
        $resources = self::getResources()[$kind];
        $resource = $request['resource'];

        if (!in_array($resource, array_keys($resources))) {
            return self::error('notFound');
        }

        // Database
        $dbo = Database::getConnection();

        $dbo->query('START TRANSACTION');

        $filename = DOCROOT.'/modules/'.$resources[$resource].'/api/'.$kind.'.php';
        include $filename;

        $dbo->query('COMMIT');

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
            if (!empty($operations)) {
                foreach ($operations as $operation) {
                    $module = basename(dirname(dirname($operation)));
                    $kind = basename($operation, '.php');

                    $resources[$kind] = (array) $resources[$kind];

                    $temp = str_replace('/api/', '/custom/api/', $operation);
                    $operation = file_exists($temp) ? $temp : $operation;

                    $api = include $operation;
                    $api = is_array($api) ? array_unique($api) : [];

                    $keys = array_keys($resources[$kind]);

                    $results = [];
                    foreach ($api as $value) {
                        $value .= in_array($value, $keys) ? $module : '';
                        $results[$value] = $module;
                    }

                    $resources[$kind] = array_merge($resources[$kind], $results);
                }
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
        if (!self::isCompatible()) {
            $array = [
                'status' => self::$status['incompatible']['code'],
            ];
        }

        if (empty($array['status'])) {
            $array['status'] = self::$status['ok']['code'];
        }

        if (empty($array['message'])) {
            $codes = array_column(self::$status, 'code');
            $messages = array_column(self::$status, 'message');

            $array['message'] = $messages[array_search($array['status'], $codes)];
        }

        $flags = JSON_FORCE_OBJECT;
        if (get('beautify') !== null) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($array, $flags);
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
        return slashes($_SERVER['SCRIPT_FILENAME']) == slashes(DOCROOT.'/api/index.php');
    }

    /**
     * Restituisce i parametri specificati dalla richiesta.
     */
    public static function getRequest()
    {
        return (array) json_decode(file_get_contents('php://input'), true);
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
