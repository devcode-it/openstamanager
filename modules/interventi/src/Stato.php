<?php

namespace Modules\Interventi;

use Common\Model;

class Stato extends Model
{
    protected $table = 'in_statiintervento';

    public function interventi()
    {
        return $this->hasMany(Ordine::class, 'idstatointervento');
    }
}
