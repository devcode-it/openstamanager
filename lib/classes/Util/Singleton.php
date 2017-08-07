<?php

namespace Util;

/**
 * @since 2.3
 */
abstract class Singleton
{
    /** @var \Util\Singleton Oggetto istanziato */
    protected static $instance = [];

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
        $class = get_called_class(); // late-static-bound class name

        if (self::$instance[$class] === null) {
            self::$instance[$class] = new static();
        }

        return self::$instance[$class];
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
}
