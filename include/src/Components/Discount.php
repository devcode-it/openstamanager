<?php

namespace Common\Components;

use Common\Document;
use Illuminate\Database\Eloquent\Builder;

abstract class Discount extends Row
{
    protected $guarded = [];

    public static function build(Document $document)
    {
        $model = parent::build($document, true);

        $model->is_sconto = 1;
        $model->qta = 1;

        return $model;
    }

    public function getIvaAttribute()
    {
        return $this->attributes['iva'];
    }

    public function isMaggiorazione()
    {
        return $this->totale_imponibile < 0;
    }

    /**
     * Effettua i conti per l'IVA.
     */
    protected function fixIva()
    {
        $this->attributes['iva'] = parent::getIvaAttribute();

        $descrizione = $this->aliquota->descrizione;
        if (!empty($descrizione)) {
            $this->attributes['desc_iva'] = $descrizione;
        }

        $this->fixIvaIndetraibile();
    }

    protected static function boot($bypass = false)
    {
        parent::boot(true);

        $table = parent::getTableName();

        static::addGlobalScope('discounts', function (Builder $builder) use ($table) {
            $builder->where($table.'.is_sconto', '=', 1);
        });
    }
}
