<?php

/**
 * Classe per la gestione delle informazioni relative ai plugin installati.
 *
 * @since 2.3
 */
class Plugins
{
    /** @var array Elenco dei plugin disponibili */
    protected static $plugins = [];
    protected static $references = [];

    /**
     * Restituisce tutte le informazioni di tutti i plugin installati.
     *
     * @return array
     */
    public static function getPlugins()
    {
        if (empty(self::$plugins)) {
            $plugins = [];
            $references = [];

            $modules = Modules::getModules();
            foreach ($modules as $module) {
                foreach ($module->plugins as $result) {
                    $plugins[$result['id']] = $result;
                    $references[$result['name']] = $result['id'];
                }
            }

            self::$plugins = $plugins;
            self::$references = $references;
        }

        return self::$plugins;
    }

    /**
     * Restituisce le informazioni relative a un singolo modulo specificato.
     *
     * @param string|int $plugin
     *
     * @return array
     */
    public static function get($plugin)
    {
        $plugins = self::getPlugins();

        if (!is_numeric($plugin) && !empty(self::$references[$plugin])) {
            $plugin = self::$references[$module];
        }

        return $plugins[$plugin];
    }

    /**
     * Individua il percorso per il file.
     *
     * @param string|int $plugin
     * @param string     $file
     *
     * @return string|null
     */
    public static function filepath($plugin, $file)
    {
        $plugin = self::get($plugin);
        $directory = 'plugins/'.$plugin['directory'].'|custom|';

        return App::filepath($directory, $file);
    }
}
