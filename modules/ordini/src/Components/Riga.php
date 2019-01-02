<?php

namespace Modules\Ordini\Components;

use Common\Components\Row;
use Modules\Ordini\Ordine;

class Riga extends Row
{
    use RelationTrait;

    protected $table = 'or_righe_ordini';

    /**
     * Crea una nuova riga collegata ad una ordine.
     *
     * @param Ordine $ordine
     *
     * @return self
     */
    public static function build(Ordine $ordine)
    {
        $model = parent::build($ordine);

        return $model;
    }
}
