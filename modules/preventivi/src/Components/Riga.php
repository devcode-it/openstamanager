<?php

namespace Modules\Preventivi\Components;

use Common\Components\Row;
use Modules\Preventivi\Preventivo;

class Riga extends Row
{
    use RelationTrait;

    protected $table = 'co_righe_preventivi';

    /**
     * Crea una nuova riga collegata ad una preventivo.
     *
     * @param Preventivo $preventivo
     *
     * @return self
     */
    public static function make(Preventivo $preventivo)
    {
        $model = parent::make($preventivo);

        return $model;
    }
}
