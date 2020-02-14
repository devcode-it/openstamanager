<?php

namespace Modules\Contratti\Components;

use Common\Components\Row;
use Modules\Contratti\Contratto;

class Riga extends Row
{
    use RelationTrait;

    protected $table = 'co_righe_contratti';

    /**
     * Crea una nuova riga collegata ad un contratto.
     *
     * @return self
     */
    public static function build(Contratto $contratto)
    {
        $model = parent::build($contratto);

        return $model;
    }
}
