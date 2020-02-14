<?php

namespace Update\v2_4_10\Components;

use Common\Components\Discount;
use Update\v2_4_10\Fattura;

class Sconto extends Discount
{
    use RelationTrait;

    protected $table = 'co_righe_documenti';

    /**
     * Crea un nuovo sconto collegato ad una fattura.
     *
     * @return self
     */
    public static function build(Fattura $fattura)
    {
        $model = parent::build($fattura);

        return $model;
    }
}
