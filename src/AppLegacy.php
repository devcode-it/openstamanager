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

use Illuminate\Support\Facades\App;
use Util\Messages;

/**
 * Classe per la gestione delle utenze.
 *
 * @since 2.4
 */
class AppLegacy
{
    /**
     * @var \Intl\Formatter
     */
    public static $formatter;
    /** @var string Simbolo della valuta corrente */
    protected static $currency;

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
            AppLegacy::getConfig();
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
            self::$flash = new Messages('messages');
        }

        return self::$flash;
    }

    /**
     * Individua i percorsi principali del progetto.
     *
     * @return array
     */
    public static function getPaths()
    {
        $assets = url('/').'/assets';

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
        $lang = App::getLocale();

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
            $result = array_unique(array_merge(
                self::$assets[$section],
                isset($config['assets']) ? ($config['assets'][$section] ?: []) : []
            ));

            foreach ($result as $key => $element) {
                $base = str_replace(base_url(), '', $paths[$dir]);
                $element = $base.'/'.$element;

                $assets_element = null;
                foreach ($lang_replace as $replace) {
                    $name = str_replace('|lang|', $replace, $element);

                    if (file_exists(base_path('public'.$name))) {
                        $assets_element = asset($name);
                    }
                }

                if (!empty($assets_element)) {
                    $result[$key] = $assets_element.'?v='.$version;
                }
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
        $path = string_contains($path, base_dir()) ? $path : base_dir().'/'.ltrim($path, '/');
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
     * Restituisce il simbolo della valuta del gestione.
     *
     * @since 2.4.9
     *
     * @return string
     */
    public static function getCurrency()
    {
        if (!isset(self::$currency)) {
            $id = setting('Valuta');
            $valuta = database()->fetchOne('SELECT symbol FROM zz_currencies WHERE id = '.prepare($id));

            self::$currency = $valuta['symbol'];
        }

        return self::$currency;
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
