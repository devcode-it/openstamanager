<?php

namespace Modules\Ordini\Components;

use Common\Components\Discount;
use Modules\Ordini\Ordine;

class Sconto extends Discount
{
    use RelationTrait;

    protected $table = 'or_righe_ordini';

    /**
     * Crea un nuovo sconto collegato ad un ordine.
     *
     * @return self
     */
    public static function build(Ordine $ordine)
    {
        $model = parent::build($ordine);

        return $model;
    }
}
