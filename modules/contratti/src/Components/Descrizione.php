<?php

namespace Modules\Contratti\Components;

use Common\Components\Description;
use Modules\Contratti\Contratto;

class Descrizione extends Description
{
    use RelationTrait;

    protected $table = 'co_righe_contratti';

    /**
     * Crea una nuova riga collegata ad una contratto.
     *
     * @param Contratto $contratto
     *
     * @return self
     */
    public static function build(Contratto $contratto)
    {
        $model = parent::build($contratto);

        return $model;
    }
}
