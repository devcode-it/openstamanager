<?php

namespace Util;

/**
 * @since 2.3
 */
class Singleton
{
    /** @var \Util\Singleton Oggetto istanziato */
    protected static $instance = null;

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
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
}
