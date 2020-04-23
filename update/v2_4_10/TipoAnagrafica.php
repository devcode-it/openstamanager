<?php

namespace Update\v2_4_10;

use Common\Model;

class TipoAnagrafica extends Model
{
    protected $table = 'an_tipianagrafiche';
    protected $primaryKey = 'idtipoanagrafica';

    protected $appends = [
        'id',
    ];

    protected $hidden = [
        'idtipoanagrafica',
    ];

    /**
     * Restituisce l'identificativo.
     *
     * @return int
     */
    public function getIdAttribute()
    {
        return $this->idtipoanagrafica;
    }

    public function anagrafiche()
    {
        return $this->hasMany(Anagrafica::class, 'an_tipianagrafiche_anagrafiche', 'idtipoanagrafica', 'idanagrafica');
    }
}
