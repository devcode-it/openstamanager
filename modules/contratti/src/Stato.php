<?php

namespace Modules\Contratti;

use Common\Model;

class Stato extends Model
{
    protected $table = 'co_staticontratti';

    public function preventivi()
    {
        return $this->hasMany(Contratto::class, 'idstato');
    }
}
