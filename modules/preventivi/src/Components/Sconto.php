<?php

namespace Modules\Preventivi\Components;

use Common\Components\Discount;
use Modules\Preventivi\Preventivo;

class Sconto extends Discount
{
    use RelationTrait;

    protected $table = 'co_righe_preventivi';

    /**
     * Crea un nuovo sconto collegato ad un preventivo.
     *
     * @param Preventivo $preventivo
     *
     * @return self
     */
    public static function build(Preventivo $preventivo)
    {
        $model = parent::build($preventivo);

        return $model;
    }
}
