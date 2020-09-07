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
}
