<?php

namespace Modules\Fatture;

use Base\Model;

class Tipo extends Model
{
    protected $table = 'co_tipidocumento';

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'idtipodocumento');
    }
}
