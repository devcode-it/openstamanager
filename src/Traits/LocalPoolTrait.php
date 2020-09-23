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

namespace Traits;

use Illuminate\Support\Collection;

trait LocalPoolTrait
{
    /** @var Collection Collezione degli oggetti disponibili */
    protected static $collection = null;
    /** @var bool Controllo sul salvataggio globale */
    protected static $all = false;

    /** @var int Identificatore dell'oggetto in utilizzo */
    protected static $current;

    /** @var string Nome della colonna "id" (Primary Key) */
    protected static $id = 'id';
    /** @var string Nome della colonna "name" */
    protected static $name = 'name';

    /**
     * Restituisce tutti gli oggetti.
     *
     * @return Collection
     */
    public static function getAll()
    {
        if (!self::$all) {
            self::$collection = self::all();

            self::$all = true;
        }

        return self::$collection;
    }

    /**
     * Restituisce l'oggetto relativo all'identificativo specificato.
     *
     * @param string|int $identifier
     *
     * @return static
     */
    public static function pool($identifier)
    {
        // Inizializzazione
        if (!isset(self::$collection)) {
            self::$collection = collect();
        }

        // Ricerca
        $result = self::$collection->first(function ($item) use ($identifier) {
            return $item->{self::$name} == $identifier || $item->{self::$id} == $identifier;
        });

        if (!empty($result)) {
            return $result;
        }

        // Consultazione Database
        $result = self::where(self::$id, $identifier)
            ->orWhere(self::$name, $identifier)
            ->first();

        if (!empty($result)) {
            self::$collection->push($result);
        }

        return $result;
    }

    /**
     * Restituisce l'oggetto attualmente impostato.
     *
     * @return static
     */
    public static function getCurrent()
    {
        if (!isset(self::$current)) {
            return null;
        }

        return self::pool(self::$current);
    }

    /**
     * Imposta il modulo attualmente in utilizzo.
     *
     * @param int $id
     */
    public static function setCurrent($id)
    {
        self::$current = $id;
    }
}
