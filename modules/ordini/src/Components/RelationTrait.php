<?php

namespace Modules\Ordini\Components;

use Modules\Ordini\Ordine;

trait RelationTrait
{
    public function getParentID()
    {
        return 'idordine';
    }

    public function parent()
    {
        return $this->belongsTo(Ordine::class, $this->getParentID());
    }

    public function ordine()
    {
        return $this->parent();
    }

    public function getNettoAttribute()
    {
        $result = parent::getNettoAttribute();

        if ($this->parent->split_payment) {
            $result = $result - $this->iva;
        }

        return $result;
    }
}
