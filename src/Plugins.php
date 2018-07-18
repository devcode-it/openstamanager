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
    /** @var array Elenco delle query generiche dei plugin */
    protected static $queries = [];

    /**
     * Restituisce tutte le informazioni di tutti i plugin installati.
     *
     * @return array
     */
    public static function getPlugins()
    {
        if (empty(self::$plugins)) {
            $database = Database::getConnection();

            $results = $database->fetchArray('SELECT *, (SELECT directory FROM zz_modules WHERE id=idmodule_from) AS module_dir FROM zz_plugins');

            $plugins = [];

            foreach ($results as $result) {
                $result['options'] = App::replacePlaceholder($result['options'], filter('id_parent'));
                $result['options2'] = App::replacePlaceholder($result['options2'], filter('id_parent'));

                $result['option'] = empty($result['options2']) ? $result['options'] : $result['options2'];

                $result['permessi'] = Modules::getPermission($result['idmodule_to']);

                $plugins[$result['id']] = $result;
                $plugins[$result['name']] = $result['id'];
            }

            self::$plugins = $plugins;
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
        if (!is_numeric($plugin) && !empty(self::getPlugins()[$plugin])) {
            $plugin = self::getPlugins()[$plugin];
        }

        return self::getPlugins()[$plugin];
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
