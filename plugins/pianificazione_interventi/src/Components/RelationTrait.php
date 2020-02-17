<?php

namespace Plugins\PianificazioneInterventi\Components;

use Plugins\PianificazioneInterventi\Promemoria;

trait RelationTrait
{
    protected $disableOrder = true;

    public function getParentID()
    {
        return 'id_promemoria';
    }

    public function parent()
    {
        return $this->belongsTo(Promemoria::class, $this->getParentID());
    }

    public function contratto()
    {
        return $this->parent();
    }

    public function fixIvaIndetraibile()
    {
    }

    public function getQtaEvasaAttribute()
    {
        return 0;
    }

    public function setQtaEvasaAttribute($value)
    {
    }

    /**
     * Effettua i conti per il subtotale della riga.
     */
    protected function fixSubtotale()
    {
        $this->fixIva();
    }
}
