<?php

namespace Modules\Interventi;

use Common\Model;

class TipoSessione extends Model
{
    public $incrementing = false;
    protected $table = 'in_tipiintervento';

    public function preventivi()
    {
        return $this->hasMany(Preventivo::class, 'id_tipo_intervento');
    }

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'id_tipo_intervento');
    }
}
