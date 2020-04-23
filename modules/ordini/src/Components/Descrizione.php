<?php

namespace Modules\Ordini\Components;

use Common\Components\Description;
use Modules\Ordini\Ordine;

class Descrizione extends Description
{
    use RelationTrait;

    protected $table = 'or_righe_ordini';

    /**
     * Crea una nuova riga collegata ad una ordine.
     *
     * @return self
     */
    public static function build(Ordine $ordine)
    {
        $model = parent::build($ordine);

        return $model;
    }
}
