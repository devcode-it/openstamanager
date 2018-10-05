<?php

namespace Common;

use Illuminate\Database\Eloquent\Builder;

abstract class Description extends Model
{
    protected static function boot($bypass = false)
    {
        parent::boot();

        if (!$bypass) {
            static::addGlobalScope('descriptions', function (Builder $builder) {
                $builder->where('is_descrizione', '=', 0);
            });
        }
    }

    public static function make($bypass = false)
    {
        $model = parent::make();

        if (!$bypass) {
            $model->is_descrizione = 1;
        }

        return $model;
    }
}
