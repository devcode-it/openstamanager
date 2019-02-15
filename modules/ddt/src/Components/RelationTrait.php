<?php

namespace Modules\DDT\Components;

use Modules\DDT\DDT;

trait RelationTrait
{
    public function getParentID()
    {
        return 'idddt';
    }

    public function parent()
    {
        return $this->belongsTo(DDT::class, $this->getParentID());
    }

    public function ddt()
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
