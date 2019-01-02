<?php

namespace Modules\DDT\Components;

use Common\Components\Discount;
use Modules\DDT\DDT;

class Sconto extends Discount
{
    use RelationTrait;

    protected $table = 'dt_righe_ddt';

    /**
     * Crea una nuovo sconto globale collegato alla ddt, oppure restituisce quello esistente.
     *
     * @param DDT $ddt
     *
     * @return self
     */
    public static function build(DDT $ddt)
    {
        $model = $ddt->scontoGlobale;

        if ($model == null) {
            $model = parent::build();

            $model->setDDT($ddt);
        }

        return $model;
    }
}
