<?php

/**
 * Classe per la gestione delle API del progetto.
 *
 * @since 2.3
 */
class API extends \Util\Singleton
{
    protected static $resources;

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
    ];

    public function __construct($token)
    {
        $user = Auth::user();

        if (!self::isAPIRequest() || empty($user)) {
            throw new InvalidArgumentException();
        }
    }

    public function retrieve($resource)
    {
        $table = '';

        $select = '*';
        // Selezione personalizzata
        $display = filter('display');
        $select = !empty($display) ? explode(',', substr($display, 1, -1)) : $select;

        $where = [];
        // Ricerca personalizzata
        $filter = (array) filter('filter');
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
        $order_request = (array) filter('order');
        foreach ($order_request as $value) {
            $pieces = explode('|', $value);
            $order[] = empty($pieces[1]) ? $pieces[0] : [$pieces[0] => $pieces[1]];
        }

        // Date di interesse
        $updated = filter('upd');
        $created = filter('crd');

        $dbo = Database::getConnection();

        $kind = 'retrieve';
        $resources = self::getResources()[$kind];

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
        $page = (int) filter('page') ?: 0;
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

    public function create($resource)
    {
        return $this->fileRequest($resource, 'create');
    }

    public function update($resource)
    {
        return $this->fileRequest($resource, 'update');
    }

    public function delete($resource)
    {
        return $this->fileRequest($resource, 'delete');
    }

    protected function fileRequest($resource, $kind)
    {
        $resources = self::getResources()[$kind];

        if (!in_array($resource, array_keys($resources))) {
            return self::error('notFound');
        }

        // Database
        $dbo = Database::getConnection();

        $dbo->query('START TRANSACTION');

        // Variabili GET e POST
        $post = Filter::getPOST();
        $get = Filter::getGET();

        $filename = DOCROOT.'/modules/'.$resources[$resource].'/api/'.$kind.'.php';
        include $filename;

        $dbo->query('COMMIT');

        return self::response($results);
    }

    public static function error($error)
    {
        $keys = array_keys(self::$status);
        $error = (in_array($error, $keys)) ? $error : end($keys);

        return self::response([
            'status' => self::$status[$error]['code'],
            'message' => self::$status[$error]['message'],
        ]);
    }

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

    public static function response($array)
    {
        if (empty($array['status'])) {
            $array['status'] = self::$status['ok']['code'];
            $array['message'] = self::$status['ok']['message'];
        }

        $flags = JSON_FORCE_OBJECT;
        if (filter('beautify') !== null) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($array, $flags);
    }

    public static function isAPIRequest()
    {
        return slashes($_SERVER['SCRIPT_FILENAME']) == slashes(DOCROOT.'/api/index.php');
    }
}
