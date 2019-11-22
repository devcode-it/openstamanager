<?php

namespace Update\v2_4_10;

use Common\Model;

class TipoFattura extends Model
{
    protected $table = 'co_tipidocumento';

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'idtipodocumento');
    }
}
