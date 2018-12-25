<?php

namespace Modules\Interventi\Components;

use Common\Components\Row;
use Modules\Interventi\Intervento;

class Riga extends Row
{
    use RelationTrait;

    protected $table = 'in_righe_interventi';

    /**
     * Crea una nuova riga collegata ad un intervento.
     *
     * @param Intervento $intervento
     *
     * @return self
     */
    public static function make(Intervento $intervento)
    {
        $model = parent::make($intervento);

        return $model;
    }
}
