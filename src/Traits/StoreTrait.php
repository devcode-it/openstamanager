<?php

namespace Traits;

trait StoreTrait
{
    /** @var Illuminate\Support\Collection Collezione degli oggetti disponibili */
    protected static $collection = null;
    /** @var bool Controllo sul salvataggio globale */
    protected static $all = false;

    /** @var string Nome della colonna "id" (Primary Key) */
    protected static $id = 'id';
    /** @var string Nome della colonna "name" */
    protected static $name = 'name';

    /**
     * Restituisce tutti gli oggetti.
     *
     * @return array
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
     * @return StoreTrait
     */
    public static function get($identifier)
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

        self::$collection->push($result);

        return $result;
    }
}
