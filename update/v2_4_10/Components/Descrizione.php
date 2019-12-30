<?php

namespace Update\v2_4_10\Components;

use Common\Components\Description;
use Update\v2_4_10\Fattura;

class Descrizione extends Description
{
    use RelationTrait;

    protected $table = 'co_righe_documenti';

    /**
     * Crea una nuova riga collegata ad una fattura.
     *
     * @param Fattura $fattura
     *
     * @return self
     */
    public static function build(Fattura $fattura)
    {
        $model = parent::build($fattura);

        return $model;
    }
}
