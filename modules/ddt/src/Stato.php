<?php

namespace Modules\DDT;

use Common\Model;

class Stato extends Model
{
    protected $table = 'dt_statiddt';

    public function fatture()
    {
        return $this->hasMany(DDT::class, 'idstatoddt');
    }
}
