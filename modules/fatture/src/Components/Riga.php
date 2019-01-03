<?php

namespace Modules\Fatture\Components;

use Common\Components\Row;
use Modules\Fatture\Fattura;

class Riga extends Row
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
