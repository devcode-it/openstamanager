<?php

namespace Modules\Fatture\Components;

use Modules\Fatture\Fattura;

trait RelationTrait
{
    public function getParentID()
    {
        return 'iddocumento';
    }

    public function parent()
    {
        return $this->belongsTo(Fattura::class, $this->getParentID());
    }

    public function fattura()
    {
        return $this->parent();
    }

    public function getNettoAttribute()
    {
        $result = $this->totale - $this->ritenuta_acconto;

        if ($this->parent->split_payment) {
            $result = $result - $this->iva;
        }

        return $result;
    }
}
