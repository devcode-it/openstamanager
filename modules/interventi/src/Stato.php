<?php

namespace Modules\Interventi;

use Common\Model;

class Stato extends Model
{
    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $table = 'in_statiintervento';

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'id_stato');
    }
}
