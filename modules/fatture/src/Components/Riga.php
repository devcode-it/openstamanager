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
    public static function make(Fattura $fattura)
    {
        $model = parent::make($fattura);

        return $model;
    }
}
