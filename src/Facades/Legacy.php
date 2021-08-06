<?php

namespace DevCode\CausaliTrasporto\Facades;

use Illuminate\Support\Facades\Facade;

class Legacy extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'legacy';
    }
}
