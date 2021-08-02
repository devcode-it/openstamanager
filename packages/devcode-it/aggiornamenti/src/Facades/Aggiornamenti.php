<?php

namespace DevCode\Aggiornamenti\Facades;

use Illuminate\Support\Facades\Facade;

class Aggiornamenti extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'aggiornamenti';
    }
}
