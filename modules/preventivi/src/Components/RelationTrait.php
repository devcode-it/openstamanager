<?php

namespace Modules\Preventivi\Components;

use Modules\Preventivi\Preventivo;

trait RelationTrait
{
    public function getParentID()
    {
        return 'idpreventivo';
    }

    public function parent()
    {
        return $this->belongsTo(Preventivo::class, $this->getParentID());
    }

    public function preventivo()
    {
        return $this->parent();
    }
}
