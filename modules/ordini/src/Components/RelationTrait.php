<?php

namespace Modules\Ordini\Components;

use Modules\Ordini\Ordine;

trait RelationTrait
{
    public function getParentID()
    {
        return 'idordine';
    }

    public function parent()
    {
        return $this->belongsTo(Ordine::class, $this->getParentID());
    }

    public function ordine()
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
