<?php

namespace Update\v2_4_10\Components;

use Common\Components\Row;
use Update\v2_4_10\Fattura;

class Riga extends Row
{
    use RelationTrait;

    protected $table = 'co_righe_documenti';

    /**
     * Crea una nuova riga collegata ad una fattura.
     *
     * @return self
     */
    public static function build(Fattura $fattura)
    {
        $model = parent::build($fattura);

        return $model;
    }
}
