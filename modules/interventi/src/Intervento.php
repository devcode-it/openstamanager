<?php

namespace Modules\Interventi;

use Common\Model;
use Modules\Anagrafiche\Anagrafica;

class Intervento extends Model
{
    protected $table = 'in_interventi';

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'id_stato');
    }

    public function articoli()
    {
        return $this->hasMany(Articolo::class, 'idintervento');
    }

    public function righe()
    {
        return $this->hasMany(Riga::class, 'idintervento');
    }
}
