<?php

namespace Modules\Interventi;

use Common\Model;

class Tipo extends Model
{
    protected $primaryKey = 'idtipointervento';
    protected $table = 'in_tipiintervento';

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'idtipointervento');
    }
}
