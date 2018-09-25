<?php

namespace Base;

use Illuminate\Database\Eloquent\Model as Original;

abstract class Model extends Original
{
    /**
     * Crea una nuova istanza del modello.
     *
     * @return static
     */
    public static function new()
    {
        return new static();
    }
}
