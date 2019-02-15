<?php

namespace Modules\Contratti\Components;

use Modules\Contratti\Contratto;

trait RelationTrait
{
    public function getParentID()
    {
        return 'idcontratto';
    }

    public function parent()
    {
        return $this->belongsTo(Contratto::class, $this->getParentID());
    }

    public function contratto()
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
