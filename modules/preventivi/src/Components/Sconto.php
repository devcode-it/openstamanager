<?php

namespace Modules\Preventivi\Components;

use Common\Components\Discount;
use Modules\Preventivi\Preventivo;

class Sconto extends Discount
{
    use RelationTrait;

    protected $table = 'co_righe_preventivi';

    /**
     * Crea una nuovo sconto globale collegato alla preventivo, oppure restituisce quello esistente.
     *
     * @param Preventivo $preventivo
     *
     * @return self
     */
    public static function build(Preventivo $preventivo)
    {
        $model = $preventivo->scontoGlobale;

        if ($model == null) {
            $model = parent::build();

            $model->setPreventivo($preventivo);
        }

        return $model;
    }
}
