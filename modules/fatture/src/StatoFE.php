<?php

namespace Modules\Fatture;

use Common\Model;

class StatoFE extends Model
{
    public $incrementing = false;
    protected $table = 'fe_stati_documento';
    protected $primaryKey = 'codice';

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'codice_stato_fe');
    }
}
