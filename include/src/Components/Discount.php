<?php

namespace Common\Components;

use Illuminate\Database\Eloquent\Builder;
use Common\Model;
use Common\Document;

abstract class Discount extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('is_discount', function (Builder $builder) {
            $builder->where('sconto_globale', '=', 1);
        });
    }

    public static function make()
    {
        $model = parent::make();

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
}
