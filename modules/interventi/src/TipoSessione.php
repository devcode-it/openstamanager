<?php

namespace Modules\Interventi;

use Common\Model;

class TipoSessione extends Model
{
    protected $table = 'in_tipiintervento';
    protected $primaryKey = 'idtipointervento';
    public $incrementing = false;

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
}
