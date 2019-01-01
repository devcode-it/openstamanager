<?php

namespace Modules\Ordini\Components;

use Common\Components\Description;
use Modules\Ordini\Ordine;

class Descrizione extends Description
{
    use RelationTrait;

    protected $table = 'or_righe_ordini';

    /**
     * Crea una nuova riga collegata ad una ordine.
     *
     * @param Ordine $ordine
     *
     * @return self
     */
    public static function make(Ordine $ordine)
    {
        $model = parent::make($ordine);

        return $model;
    }
}
