<?php

use Models\Plugin;

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
     * @return Plugin
     */
    public static function get($plugin)
    {
        $plugins = self::getPlugins();

        if (!is_numeric($plugin) && !empty(self::$references[$plugin])) {
            $plugin = self::$references[$plugin];
        }

        return $plugins[$plugin];
    }

    /**
     * Restituisce il modulo attualmente in utilizzo.
     *
     * @return Plugin
     */
    public static function getCurrent()
    {
        return Plugin::getCurrent();
    }

    /**
     * Imposta il modulo attualmente in utilizzo.
     *
     * @param int $id
     */
    public static function setCurrent($id)
    {
        Plugin::setCurrent($id);

        // Fix modulo
        $plugin = self::getCurrent();
        if (isset($plugin)) {
            Modules::setCurrent($plugin->module->id);
        }
    }

    /**
     * Individua il percorso per il file.
     *
     * @param string|int $element
     * @param string     $file
     *
     * @return string|null
     */
    public static function filepath($element, $file)
    {
        $element = self::get($element);

        return $element ? $element->filepath($file) : null;
    }

    /**
     * Costruisce un link HTML per il modulo e il record indicati.
     *
     * @param string|int $plugin
     * @param int        $id_record
     * @param string     $testo
     * @param string     $alternativo
     * @param string     $extra
     * @param bool       $blank
     *
     * @return string
     */
    public static function link($plugin, $id_record = null, $testo = null, $alternativo = true, $extra = null, $blank = true)
    {
        $plugin = self::get($plugin);
        $alternativo = is_bool($alternativo) && $alternativo ? $testo : $alternativo;

        if (!empty($plugin) && in_array($plugin->permission, ['r', 'rw'])) {
            $anchor = 'tab_'.$plugin->id;

            return Modules::link($plugin->originalModule->id, $id_record, $testo, $alternativo, $extra, $blank, $anchor);
        }

        return $alternativo;
    }
}
