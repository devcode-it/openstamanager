<?php

namespace Modules\Fatture\Components;

use Common\Components\Discount;
use Modules\Fatture\Fattura;

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
