<?php

namespace Modules\Interventi\Components;

use Common\Components\Discount;
use Modules\Interventi\Intervento;

class Sconto extends Discount
{
    use RelationTrait;

    protected $table = 'in_righe_interventi';

    /**
     * Crea un nuovo sconto collegata ad un intervento.
     *
     * @return self
     */
    public static function build(Intervento $intervento)
    {
        $model = parent::build($intervento);

        $model->prezzo_vendita = 0;

        return $model;
    }
}
