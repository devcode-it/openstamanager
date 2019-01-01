<?php

namespace Modules\DDT;

use Common\Model;

class Tipo extends Model
{
    protected $table = 'dt_tipiddt';

    public function ddt()
    {
        return $this->hasMany(DDT::class, 'idtipoddt');
    }
}
