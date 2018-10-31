<?php

namespace Modules\Anagrafiche;

use Common\Model;

class Sede extends Model
{
    protected $table = 'an_sedi';

    public function getPartitaIvaAttribute()
    {
        return $this->piva;
    }

    public function setPartitaIvaAttribute($value)
    {
        $this->attributes['piva'] = trim(strtoupper($value));
    }

    public function setCodiceFiscaleAttribute($value)
    {
        $this->attributes['codice_fiscale'] = trim(strtoupper($value));
    }

    public function anagrafica()
    {
        return $this->belognsTo(Anagrafica::class, 'idanagrafica');
    }

    public function nazione()
    {
        return $this->belongsTo(Nazione::class, 'id_nazione');
    }

}
