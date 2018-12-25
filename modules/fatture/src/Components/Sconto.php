<?php

namespace Modules\Fatture\Components;

use Common\Components\Discount;
use Modules\Fatture\Fattura;

class Sconto extends Discount
{
    use RelationTrait;

    protected $table = 'co_righe_documenti';

    /**
     * Crea una nuovo sconto globale collegato alla fattura, oppure restituisce quello esistente.
     *
     * @param Fattura $fattura
     *
     * @return self
     */
    public static function make(Fattura $fattura)
    {
        $model = $fattura->scontoGlobale;

        if ($model == null) {
            $model = parent::make();

            $model->setFattura($fattura);
        }

        return $model;
    }
}
