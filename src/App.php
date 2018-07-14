<?php

/**
 * Classe per la gestione delle utenze.
 *
 * @since 2.4
 */
class App
{
    /** @var array Identificativo del modulo corrente */
    protected static $current_module;
    /** @var int Identificativo dell'elemento corrente */
    protected static $current_element;

    /** @var bool Stato di debug */
    protected static $config = [];

    /** @var array Elenco degli assets del progetto */
    protected static $assets = [
        // CSS
        'css' => [
            'app.min.css',
            'style.min.css',
            'themes.min.css',
        ],

        // Print CSS
        'print' => [
            'print.min.css',
        ],

        // JS
        'js' => [
            'app.min.js',
            'custom.min.js',
            'i18n/parsleyjs/|lang|.min.js',
            'i18n/select2/|lang|.min.js',
            'i18n/moment/|lang|.min.js',
            'i18n/fullcalendar/|lang|.min.js',
        ],
    ];

    /**
     * Restituisce il modulo attualmente in utilizzo.
     *
     * @return array
     */
    public static function getCurrentModule()
    {
        $id = filter('id_module');

        if (empty(self::$current_module) && !empty($id)) {
            self::$current_module = Modules::get($id);
        }

        return self::$current_module;
    }

    /**
     * Restituisce l'identificativo dell'elemento attualmente in utilizzo.
     *
     * @return int
     */
    public static function getCurrentElement()
    {
        if (empty(self::$current_element)) {
            self::$current_element = intval(filter('id_record'));
        }

        return self::$current_element;
    }

    /**
     * Restituisce la configurazione di default del progetto.
     *
     * @return array
     */
    protected static function getDefaultConfig()
    {
        if (file_exists(DOCROOT.'/config.example.php')) {
            include DOCROOT.'/config.example.php';
        }

        $db_host = '';
        $db_username = '';
        $db_password = '';
        $db_name = '';
        $port = '';

        return get_defined_vars();
    }

    /**
     * Restituisce la configurazione dell'installazione in utilizzo del progetto.
     *
     * @return array
     */
    public static function getConfig()
    {
        if (empty(self::$config['db_host'])) {
            if (file_exists(DOCROOT.'/config.inc.php')) {
                include DOCROOT.'/config.inc.php';

                $config = get_defined_vars();
            } else {
                $config = [];
            }

            $defaultConfig = self::getDefaultConfig();

            $result = array_merge($defaultConfig, $config);

            // Operazioni di normalizzazione sulla configurazione
            $result['debug'] = isset(self::$config['debug']) ? self::$config['debug'] : !empty($result['debug']);

            self::$config = $result;
        }

        return self::$config;
    }

    /**
     * Imposta e restituisce lo stato di debug del progetto.
     *
     * @param bool $value
     *
     * @return bool
     */
    public static function debug($value = null)
    {
        if (is_bool($value)) {
            self::$config['debug'] = $value;
        }

        return self::$config['debug'];
    }

    /**
     * Individua i percorsi di base necessari per il funzionamento del gestionale.
     * <b>Attenzione<b>: questo metodo deve essere eseguito all'interno di un file nella cartella principale del progetto per permettere il corretto funzionamento degli URL.
     *
     * @return array
     */
    public static function definePaths($docroot)
    {
        if (!defined('DOCROOT')) {
            // Individuazione di $rootdir
            $rootdir = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/')).'/';
            if (strrpos($rootdir, '/'.basename($docroot).'/') !== false) {
                $rootdir = substr($rootdir, 0, strrpos($rootdir, '/'.basename($docroot).'/')).'/'.basename($docroot);
            } else {
                $rootdir = '/';
            }
            $rootdir = rtrim($rootdir, '/');
            $rootdir = str_replace('%2F', '/', rawurlencode($rootdir));

            // Individuazione di $baseurl
            $baseurl = (isHTTPS(true) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$rootdir;

            // Impostazione delle variabili globali
            define('DOCROOT', $docroot);
            define('ROOTDIR', $rootdir);
            define('BASEURL', $baseurl);
        }
    }

    /**
     * Individua i percorsi principali del progetto.
     *
     * @return array
     */
    public static function getPaths()
    {
        $assets = ROOTDIR.'/assets/dist';

        return [
            'assets' => $assets,
            'css' => $assets.'/css',
            'js' => $assets.'/js',
            'img' => $assets.'/img',
        ];
    }

    /**
     * Restituisce l'elenco degli assets del progetto.
     *
     * @return array
     */
    public static function getAssets()
    {
        // Assets aggiuntivi
        $config = self::getConfig();

        // Impostazione dei percorsi
        $paths = self::getPaths();
        $lang = Translator::getInstance()->getCurrentLocale();

        // Sezioni: nome - percorso
        $sections = [
            'css' => 'css',
            'print' => 'css',
            'js' => 'js',
        ];

        $assets = [];

        foreach ($sections as $section => $dir) {
            $result = array_unique(array_merge(self::$assets[$section], $config['assets'][$section]));

            foreach ($result as $key => $element) {
                $element = $paths[$dir].'/'.$element;
                $element = str_replace('|lang|', $lang, $element);

                $result[$key] = $element;
            }

            $assets[$section] = $result;
        }

        // JS aggiuntivi per gli utenti connessi
        if (Auth::check()) {
            $assets['js'][] = ROOTDIR.'/lib/functions.js';
            $assets['js'][] = ROOTDIR.'/lib/init.js';
        }

        return $assets;
    }

    /**
     * Restituisce un'insieme di array comprendenti le informazioni per la costruzione della query del modulo indicato.
     *
     * @param array $element
     *
     * @return array
     */
    public static function readQuery($element)
    {
        if (str_contains($element['option'], '|select|')) {
            $result = self::readNewQuery($element);
        } else {
            $result = self::readOldQuery($element);
        }

        return $result;
    }

    /**
     * Interpreta lo standard modulare per l'individuazione delle query di un modulo/plugin del progetto.
     *
     * @param array $element
     *
     * @return array
     */
    protected static function readNewQuery($element)
    {
        $fields = [];
        $summable = [];
        $search_inside = [];
        $search = [];
        $format = [];
        $slow = [];
        $order_by = [];

        $query = $element['option'];
        $views = self::getViews($element);

        $select = [];

        foreach ($views as $view) {
            $select[] = $view['query'].(!empty($view['name']) ? " AS '".$view['name']."'" : '');

            if ($view['enabled']) {
                $view['name'] = trim($view['name']);
                $view['search_inside'] = trim($view['search_inside']);
                $view['order_by'] = trim($view['order_by']);

                $fields[] = trim($view['name']);

                $search_inside[] = !empty($view['search_inside']) ? $view['search_inside'] : '`'.$view['name'].'`';
                $order_by[] = !empty($view['order_by']) ? $view['order_by'] : '`'.$view['name'].'`';
                $search[] = $view['search'];
                $slow[] = $view['slow'];
                $format[] = $view['format'];

                if ($view['summable']) {
                    $summable[] = 'SUM(`'.trim($view['name']."`) AS 'sum_".(count($fields) - 1)."'");
                }
            }
        }

        $select = empty($select) ? '*' : implode(', ', $select);

        $query = str_replace('|select|', $select, $query);

        return [
            'query' => self::replacePlaceholder($query),
            'fields' => $fields,
            'search_inside' => $search_inside,
            'order_by' => $order_by,
            'search' => $search,
            'slow' => $slow,
            'format' => $format,
            'summable' => $summable,
        ];
    }

    /**
     * Interpreta lo standard JSON per l'individuazione delle query di un modulo/plugin del progetto.
     *
     * @param array $element
     *
     * @return array
     */
    protected static function readOldQuery($element)
    {
        $options = str_replace(["\r", "\n", "\t"], ' ', $element['option']);
        $options = json_decode($options, true);
        $options = $options['main_query'][0];

        $fields = [];
        $order_by = [];

        $search = [];
        $slow = [];
        $format = [];

        $query = $options['query'];
        $views = explode(',', $options['fields']);
        foreach ($views as $view) {
            $fields[] = trim($view);
            $order_by[] = '`'.trim($view).'`';

            $search[] = 1;
            $slow[] = 0;
            $format[] = 0;
        }

        $search_inside = $order_by;

        return [
            'query' => self::replacePlaceholder($query),
            'fields' => $fields,
            'search_inside' => $search_inside,
            'order_by' => $order_by,
            'search' => $search,
            'slow' => $slow,
            'format' => $format,
            'summable' => [],
        ];
    }

    /**
     * Restituisce le singole componenti delle query per un determinato modulo/plugin.
     *
     * @param array $element
     *
     * @return array
     */
    protected static function getViews($element)
    {
        $database = Database::getConnection();

        $user = Auth::user();

        $views = $database->fetchArray('SELECT * FROM `zz_views` WHERE `id_module`='.prepare($element['id']).' AND
        `id` IN (
            SELECT `id_vista` FROM `zz_group_view` WHERE `id_gruppo`=(
                SELECT `idgruppo` FROM `zz_users` WHERE `id`='.prepare($user['id']).'
            ))
        ORDER BY `order` ASC');

        return $views;
    }

    /**
     * Sostituisce i valori previsti all'interno delle query di moduli/plugin.
     *
     * @param string $query
     * @param int    $custom
     *
     * @return string
     */
    public static function replacePlaceholder($query, $custom = null)
    {
        $id_module = filter('id_module');
        $user = Auth::user();

        // Sostituzione degli identificatori
        $id = empty($custom) ? $user['idanagrafica'] : $custom;
        $query = str_replace(['|idagente|', '|idtecnico|', '|idanagrafica|'], prepare($id), $query);

        // Sostituzione delle date
        $query = str_replace(['|period_start|', '|period_end|'], [$_SESSION['period_start'], $_SESSION['period_end']], $query);

        // Sostituzione dei segmenti
        $query = str_replace('|segment|', !empty($_SESSION['m'.$id_module]['id_segment']) ? ' AND id_segment = '.prepare($_SESSION['m'.$id_module]['id_segment']) : '', $query);

        return $query;
    }

    /**
     * Restituisce il codice HTML per il form contenente il file indicato.
     *
     * @param string $path
     * @param array  $result
     * @param array  $options
     *
     * @return string
     */
    public static function load($file, $result, $options)
    {
        $form = self::internalLoad('form.php', $result, $options);

        $response = self::internalLoad($file, $result, $options);

        $form = str_replace('|response|', $response, $form);

        return $form;
    }

    /**
     * Restituisce il codice HTML generato del file indicato.
     *
     * @param string $path
     * @param array  $result
     * @param array  $options
     * @param string $directory
     *
     * @return string
     */
    protected static function internalLoad($file, $result, $options, $directory = null)
    {
        $module = self::getCurrentModule();

        $id_module = filter('id_module');
        $id_record = filter('id_record');

        $directory = empty($directory) ? 'include|custom|/common/' : $directory;

        ob_start();
        include self::filepath($directory, $file);
        $response = ob_get_clean();

        return $response;
    }

    /**
     * Individua il percorso per il file da includere considerando gli eventuali custom.
     *
     * @param string $path
     * @param string $file
     *
     * @return string|null
     */
    public static function filepath($path, $file = null)
    {
        $path = str_contains($path, DOCROOT) ? $path : DOCROOT.'/'.ltrim($path, '/');
        $path = empty($file) ? $path : rtrim($path, '/').'/'.$file;

        $original_file = str_replace('|custom|', '', $path);
        $custom_file = str_replace('|custom|', '/custom', $path);

        $result = '';
        if (file_exists($custom_file)) {
            $result = $custom_file;
        } elseif (file_exists($original_file)) {
            $result = $original_file;
        }

        return slashes($result);
    }
}
