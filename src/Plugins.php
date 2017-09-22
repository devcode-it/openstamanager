<?php

/**
 * Classe per la gestione delle informazioni relative ai moduli installati.
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
     * Restituisce tutte le informazioni di tutti i moduli installati.
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
     * Restituisce un'insieme di array comprendenti le informazioni per la costruzione della query del modulo indicato.
     *
     * @param int $id
     *
     * @return array
     */
    public static function getQuery($id)
    {
        if (empty(self::$queries[$id])) {
            $database = Database::getConnection();

            $module = self::get($id);

            $fields = [];
            $summable = [];
            $search_inside = [];
            $search = [];
            $slow = [];
            $order_by = [];
            $select = '*';

            $options = !empty($module['options2']) ? $module['options2'] : $module['options'];
            $options = Modules::readOldQuery($options);

            $query = $options['query'];
            $fields = explode(',', $options['fields']);
            foreach ($fields as $key => $value) {
                $fields[$key] = trim($value);
                $search[] = 1;
                $slow[] = 0;
                $format[] = 0;
            }

            $search_inside = $fields;
            $order_by = $fields;

            $result = [];
            $result['query'] = $query;
            $result['select'] = $select;
            $result['fields'] = $fields;
            $result['search_inside'] = $search_inside;
            $result['order_by'] = $order_by;
            $result['search'] = $search;
            $result['slow'] = $slow;
            $result['format'] = $format;
            $result['summable'] = $summable;

            self::$queries[$id] = $result;
        }

        return self::$queries[$id];
    }
}
