<?php

namespace Modules\Ordini;

use Common\Model;

class Tipo extends Model
{
    protected $table = 'or_tipiordine';

    public function fatture()
    {
        return $this->hasMany(Ordine::class, 'idtipoordine');
    }
}
