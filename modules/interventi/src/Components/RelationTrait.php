<?php

namespace Modules\Interventi\Components;

use Modules\Interventi\Intervento;

trait RelationTrait
{
    protected $disableOrder = true;

    public function getParentID()
    {
        return 'idintervento';
    }

    public function parent()
    {
        return $this->belongsTo(Intervento::class, $this->getParentID());
    }

    public function intervento()
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
     * Restituisce il prezzo unitario della riga.
     */
    public function getPrezzoUnitarioVenditaAttribute()
    {
        if (!isset($this->prezzo_unitario_vendita_riga)) {
            $this->prezzo_unitario_vendita_riga = $this->prezzo_vendita;
        }

        return !is_nan($this->prezzo_unitario_vendita_riga) ? $this->prezzo_unitario_vendita_riga : 0;
    }

    /**
     * Restituisce il costo unitario della riga.
     */
    public function getPrezzoUnitarioAcquistoAttribute()
    {
        return $this->prezzo_acquisto;
    }

    /**
     * Effettua i conti per il subtotale della riga.
     */
    protected function fixSubtotale()
    {
        $this->prezzo_vendita = $this->prezzo_unitario_vendita;

        $this->fixIva();
    }
}
