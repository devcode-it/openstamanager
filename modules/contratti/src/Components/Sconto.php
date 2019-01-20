<?php

namespace Modules\Contratti\Components;

use Common\Components\Discount;
use Modules\Contratti\Contratto;

class Sconto extends Discount
{
    use RelationTrait;

    protected $table = 'co_righe_contratti';

    /**
     * Crea una nuovo sconto globale collegato alla contratto, oppure restituisce quello esistente.
     *
     * @param Contratto $contratto
     *
     * @return self
     */
    public static function build(Contratto $contratto)
    {
        $model = $contratto->scontoGlobale;

        if ($model == null) {
            $model = parent::build();

            $model->setContratto($contratto);
        }

        return $model;
    }
}
