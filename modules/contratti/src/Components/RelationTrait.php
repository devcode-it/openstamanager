<?php

namespace Modules\Contratti\Components;

use Modules\Contratti\Contratto;

trait RelationTrait
{
    public function getParentID()
    {
        return 'idcontratto';
    }

    public function parent()
    {
        return $this->belongsTo(Contratto::class, $this->getParentID());
    }

    public function contratto()
    {
        return $this->parent();
    }
}
