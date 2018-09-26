<?php

namespace Base;

use Illuminate\Database\Eloquent\Builder;

abstract class Description extends Model
{
    protected static function boot($bypass)
    {
        parent::boot();

        if (!$bypass) {
            static::addGlobalScope('descriptions', function (Builder $builder) {
                $builder->where('is_descrizione', '=', 0);
            });
        }
    }
}
