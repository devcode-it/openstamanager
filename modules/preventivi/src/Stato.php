<?php

namespace Modules\Preventivi;

use Common\Model;

class Stato extends Model
{
    protected $table = 'co_statipreventivi';

    public function preventivi()
    {
        return $this->hasMany(Preventivo::class, 'id_stato');
    }
}
