<?php

namespace Modules\Preventivi\Components;

use Modules\Preventivi\Preventivo;

trait RelationTrait
{
    public function getParentID()
    {
        return 'idpreventivo';
    }

    public function parent()
    {
        return $this->belongsTo(Preventivo::class, $this->getParentID());
    }

    public function preventivo()
    {
        return $this->parent();
    }

    /**
     * Effettua i conti per la Rivalsa INPS.
     */
    protected function fixRivalsaINPS()
    {
    }

    /**
     * Effettua i conti per la Ritenuta d'Acconto, basandosi sul valore del campo calcolo_ritenuta_acconto.
     */
    protected function fixRitenutaAcconto()
    {
    }
}
