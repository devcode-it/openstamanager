<?php

namespace Modules\DDT\Components;

use Common\Components\Row;
use Modules\DDT\DDT;

class Riga extends Row
{
    use RelationTrait;

    protected $table = 'dt_righe_ddt';

    /**
     * Crea una nuova riga collegata ad una ddt.
     *
     * @param DDT $ddt
     *
     * @return self
     */
    public static function build(DDT $ddt)
    {
        $model = parent::build($ddt);

        return $model;
    }
}
