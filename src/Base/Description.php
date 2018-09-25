<?php

namespace Base;

use Illuminate\Database\Eloquent\Builder;

abstract class Description extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('descriptions', function (Builder $builder) {
            $builder->where('is_descrizione', '=', 0);
        });
    }
}
