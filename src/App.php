<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Util\Messages;

/**
 * Classe per la gestione delle utenze.
 *
 * @since 2.4
 */
class App
{
    public static $docroot;
    public static $rootdir;
    public static $baseurl;

    /** @var array Identificativo del modulo corrente */
    protected static $current_module;
    /** @var int Identificativo dell'elemento corrente */
    protected static $current_element;

    /** @var Messages Gestione dei messaggi flash */
    protected static $flash = null;

    /** @var bool Stato di debug */
    protected static $config = [];

    /** @var array Elenco degli assets del progetto */
    protected static $assets = [
        // CSS
        'css' => [
            'app.min.css',
            'style.min.css',
            'themes.min.css',
            'dropzone.min.css',
        ],

        // Print CSS
        'print' => [
            'print.min.css',
        ],

        // JS
        'js' => [
            'app.min.js',
            'functions.min.js',
            'custom.min.js',
            'dropzone.min.js',
            'i18n/parsleyjs/|lang|.min.js',
            'i18n/select2/|lang|.min.js',
            'i18n/moment/|lang|.min.js',
            'i18n/fullcalendar/|lang|.min.js',
        ],
    ];

    /**
     * Restituisce la configurazione dell'installazione in utilizzo del progetto.
     *
     * @return array
     */
    public static function getConfig()
    {
        if (empty(self::$config['db_host'])) {
            if (file_exists(base_dir().'/config.inc.php')) {
                include base_dir().'/config.inc.php';

                $config = get_defined_vars();
            } else {
                $config = [];
            }

            $defaultConfig = self::getDefaultConfig();

            $result = array_merge($defaultConfig, $config);

            // Operazioni di normalizzazione sulla configurazione
            $result['debug'] = isset(self::$config['debug']) ? self::$config['debug'] : !empty($result['debug']);
            $result['lang'] = $result['lang'] == 'it' ? 'it_IT' : $result['lang'];

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

        if (!isset(self::$config['debug'])) {
            App::getConfig();
        }

        return self::$config['debug'];
    }

    /**
     * Restituisce l'oggetto dedicato alla gestione dei messaggi per l'utente.
     *
     * @return Messages
     */
    public static function flash()
    {
        if (empty(self::$flash)) {
            $storage = null;
            self::$flash = new Messages($storage, 'messages');
        }

        return self::$flash;
    }

    /**
     * Individua i percorsi di base necessari per il funzionamento del gestionale.
     * <b>Attenzione<b>: questo metodo deve essere eseguito all'interno di un file nella cartella principale del progetto per permettere il corretto funzionamento degli URL.
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

            self::$docroot = $docroot;
            self::$rootdir = $rootdir;
            self::$baseurl = $baseurl;
        }
    }

    /**
     * Individua i percorsi principali del progetto.
     *
     * @return array
     */
    public static function getPaths()
    {
        $assets = base_path().'/assets/dist';

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

        $version = Update::getVersion();

        // Impostazione dei percorsi
        $paths = self::getPaths();
        $lang = trans()->getCurrentLocale();

        // Sezioni: nome - percorso
        $sections = [
            'css' => 'css',
            'print' => 'css',
            'js' => 'js',
        ];

        $first_lang = explode('_', $lang);
        $lang_replace = [
            $lang,
            strtolower($lang),
            strtolower($first_lang[0]),
            strtoupper($first_lang[0]),
            str_replace('_', '-', $lang),
            str_replace('_', '-', strtolower($lang)),
        ];

        $assets = [];

        foreach ($sections as $section => $dir) {
            $result = array_unique(array_merge(self::$assets[$section], $config['assets'][$section]));

            foreach ($result as $key => $element) {
                $element = $paths[$dir].'/'.$element;

                foreach ($lang_replace as $replace) {
                    $name = str_replace('|lang|', $replace, $element);

                    if (file_exists(base_dir().str_replace(base_path(), '', $name))) {
                        $assets_element = $name;
                        break;
                    }
                }

                $result[$key] = $assets_element.'?v='.$version;
            }

            $assets[$section] = $result;
        }

        return $assets;
    }

    /**
     * Restituisce il codice HTML per il form contenente il file indicato.
     *
     * @param string $file
     * @param array  $result
     * @param array  $options
     * @param bool   $disableForm
     *
     * @return string
     */
    public static function load($file, $result, $options, $disableForm = false)
    {
        $form = $disableForm ? '|response|' : self::internalLoad('form.php', $result, $options);

        $response = self::internalLoad($file, $result, $options);

        $form = str_replace('|response|', $response, $form);

        return $form;
    }

    /**
     * Restituisce il codice HTML generato del file indicato.
     *
     * @param string $file
     * @param array  $result
     * @param array  $options
     * @param string $directory
     *
     * @return string
     */
    public static function internalLoad($file, $result, $options, $directory = null)
    {
        $module = Modules::getCurrent();

        $database = $dbo = database();

        $id_module = $module['id'];
        $id_record = filter('id_record');
        $id_plugin = $options['id_plugin'];

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
        $path = str_contains($path, base_dir()) ? $path : base_dir().'/'.ltrim($path, '/');
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

    /**
     * Restituisce la configurazione di default del progetto.
     *
     * @return array
     */
    protected static function getDefaultConfig()
    {
        if (file_exists(base_dir().'/config.example.php')) {
            include base_dir().'/config.example.php';
        }

        $db_host = '';
        $db_username = '';
        $db_password = '';
        $db_name = '';
        $port = '';
        $lang = '';

        $formatter = [
            'timestamp' => 'd/m/Y H:i',
            'date' => 'd/m/Y',
            'time' => 'H:i',
            'number' => [
                'decimals' => ',',
                'thousands' => '.',
            ],
        ];

        return get_defined_vars();
    }
}
