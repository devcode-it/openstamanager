<?php

namespace Modules\Ordini\Components;

use Common\Components\Discount;
use Modules\Ordini\Ordine;

class Sconto extends Discount
{
    use RelationTrait;

    protected $table = 'or_righe_ordini';

    /**
     * Crea una nuovo sconto globale collegato alla ordine, oppure restituisce quello esistente.
     *
     * @param Ordine $ordine
     *
     * @return self
     */
    public static function make(Ordine $ordine)
    {
        $model = $ordine->scontoGlobale;

        if ($model == null) {
            $model = parent::make();

            $model->setOrdine($ordine);
        }

        return $model;
    }
}
