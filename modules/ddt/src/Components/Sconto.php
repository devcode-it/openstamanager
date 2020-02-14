<?php

namespace Modules\DDT\Components;

use Common\Components\Discount;
use Modules\DDT\DDT;

class Sconto extends Discount
{
    use RelationTrait;

    protected $table = 'dt_righe_ddt';

    /**
     * Crea un nuovo sconto collegato ad un ddt.
     *
     * @return self
     */
    public static function build(DDT $ddt)
    {
        $model = parent::build($ddt);

        return $model;
    }
}
