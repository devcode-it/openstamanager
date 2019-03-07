<?php

namespace Modules\Interventi;

use Common\Model;

class TipoSessione extends Model
{
    public $incrementing = false;
    protected $table = 'in_tipiintervento';
    protected $primaryKey = 'idtipointervento';

    /**
     * Restituisce l'identificativo.
     *
     * @return string
     */
    public function getIdAttribute()
    {
        return $this->idtipointervento;
    }

    public function preventivi()
    {
        return $this->hasMany(Preventivo::class, 'idtipointervento');
    }

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'idtipointervento');
    }
}
