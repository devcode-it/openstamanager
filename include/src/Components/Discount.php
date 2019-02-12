<?php

namespace Common\Components;

use Common\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class Discount extends Model
{
    public static function build()
    {
        $model = parent::build();

        $model->sconto_globale = 1;

        return $model;
    }

    /**
     * Restituisce il totale dello sconto.
     */
    public function getTotaleAttribute()
    {
        return $this->imponibile + $this->iva;
    }

    /**
     * Restituisce il netto dello sconto.
     */
    public function getNettoAttribute()
    {
        return $this->totale;
    }

    /**
     * Restituisce l'imponibile scontato dello sconto.
     */
    public function getImponibileScontatoAttribute()
    {
        return $this->imponibile;
    }

    /**
     * Restituisce l'imponibile dello sconto.
     */
    public function getImponibileAttribute()
    {
        return $this->subtotale;
    }

    /**
     * Restituisce il "guadagno" dello sconto.
     */
    public function getGuadagnoAttribute()
    {
        return $this->imponibile;
    }

    /**
     * Restituisce il totale dello sconto.
     */
    public function getIvaAttribute()
    {
        return $this->attributes['iva'];
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('is_discount', function (Builder $builder) {
            $builder->where('sconto_globale', '=', 1);
        });
    }
}
