<?php

namespace Modules\Ordini;

use Common\Model;

class Stato extends Model
{
    protected $table = 'or_statiordine';

    public function fatture()
    {
        return $this->hasMany(Ordine::class, 'idstatoordine');
    }
}
