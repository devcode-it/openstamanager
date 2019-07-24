<?php

namespace Modules\Interventi;

use Common\Model;

class Stato extends Model
{
    protected $primaryKey = 'idstatointervento';
    protected $table = 'in_statiintervento';

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'idstatointervento');
    }
}
