<?php

namespace Common;

use Illuminate\Database\Eloquent\Model as Original;

abstract class Model extends Original
{
    // Retrocompatibilità MySQL
    const UPDATED_AT = null;

    /**
     * Crea una nuova istanza del modello.
     *
     * @return static
     */
    public static function make()
    {
        return new static();
    }
}
