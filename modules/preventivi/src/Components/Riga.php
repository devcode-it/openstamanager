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
     * @return self
     */
    public static function build(Preventivo $preventivo)
    {
        $model = parent::build($preventivo);

        return $model;
    }
}
