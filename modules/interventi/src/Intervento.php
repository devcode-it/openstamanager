<?php

namespace Modules\Interventi;

use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Components\Riga;
use Modules\Interventi\Components\Articolo;

class Intervento extends Document
{
    protected $table = 'in_interventi';

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'idstatointervento');
    }

    public function articoli()
    {
        return $this->hasMany(Articolo::class, 'idintervento');
    }

    public function righe()
    {
        return $this->hasMany(Riga::class, 'idintervento');
    }

    public function descrizioni()
    {
        return null;
    }

    public function scontoGlobale()
    {
        return null;
    }
}
