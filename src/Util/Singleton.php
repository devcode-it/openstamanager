<?php

namespace Util;

/**
 * Classe astratta per la generazione di oggetti istanziabili una singola volta.
 *
 * @since 2.3
 */
abstract class Singleton
{
    /** @var Singleton Oggetti istanziati */
    protected static $instance = [];

    /**
     * Protected constructor to prevent creating a new instance of the <b>Singleton</b> via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
    }

    /**
     * Restituisce l'istanza della classe in oggetto.
     *
     * @return Singleton
     */
    public static function getInstance()
    {
        $class = get_called_class();

        if (!isset(self::$instance[$class])) {
            self::$instance[$class] = new static();
        }

        return self::$instance[$class];
    }

    /**
     * Private clone method to prevent cloning of the instance of the <b>Singleton</b> instance.
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the <b>Singleton</b> instance.
     */
    private function __wakeup()
    {
    }
}
