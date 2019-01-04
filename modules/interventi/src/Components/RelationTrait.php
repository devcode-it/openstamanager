<?php

namespace Modules\Interventi\Components;

use Modules\Interventi\Intervento;

trait RelationTrait
{
    public function getParentID()
    {
        return 'idintervento';
    }

    public function parent()
    {
        return $this->belongsTo(Intervento::class, $this->getParentID());
    }

    public function intervento()
    {
        return $this->parent();
    }
}
