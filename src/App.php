<?php

/**
 * Classe per la gestione delle utenze.
 *
 * @since 2.4
 */
class App
{
    /** @var int Identificativo del modulo corrente */
    protected static $current_module;
    /** @var int Identificativo dell'elemento corrente */
    protected static $current_element;

    /**
     * Restituisce l'identificativo del modulo attualmente in utilizzo.
     *
     * @return int
     */
    public static function getCurrentModule()
    {
        if (empty(self::$current_module)) {
            self::$current_module = filter('id_module');
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
            self::$current_element = filter('id_record');
        }

        return self::$current_element;
    }

    /**
     * Restituisce la configurazione dell'installazione.
     *
     * @return array
     */
    public function getConfig()
    {
        if (file_exists(DOCROOT.'/config.inc.php')) {
            include DOCROOT.'/config.inc.php';
        }

        return get_defined_vars();
    }
}
