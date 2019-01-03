<?php

namespace Modules\DDT\Components;

use Modules\DDT\DDT;

trait RelationTrait
{
    public function getParentID()
    {
        return 'idddt';
    }

    public function parent()
    {
        return $this->belongsTo(DDT::class, $this->getParentID());
    }

    public function ddt()
    {
        return $this->parent();
    }
}
