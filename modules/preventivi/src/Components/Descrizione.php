<?php

namespace Modules\Preventivi\Components;

use Common\Components\Description;
use Modules\Preventivi\Preventivo;

class Descrizione extends Description
{
    use RelationTrait;

    protected $table = 'co_righe_preventivi';

    /**
     * Crea una nuova riga collegata ad una preventivo.
     *
     * @return self
     */
    public static function build(Preventivo $preventivo)
    {
        $model = parent::build($preventivo);

        return $model;
    }
}
