<?php

namespace Modules\Contratti\Components;

use Common\Components\Discount;
use Modules\Contratti\Contratto;

class Sconto extends Discount
{
    use RelationTrait;

    protected $table = 'co_righe_contratti';

    /**
     * Crea un nuovo sconto collegato ad un contratto.
     *
     * @param Contratto $contratto
     *
     * @return self
     */
    public static function build(Contratto $contratto)
    {
        $model = parent::build($contratto);

        return $model;
    }
}
