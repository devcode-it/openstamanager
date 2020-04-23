<?php

namespace Plugins\PianificazioneInterventi\Components;

use Common\Components\Discount;
use Plugins\PianificazioneInterventi\Promemoria;

class Sconto extends Discount
{
    use RelationTrait;

    protected $table = 'co_righe_promemoria';

    /**
     * Crea un nuovo sconto collegato ad un contratto.
     *
     * @return self
     */
    public static function build(Promemoria $promemoria)
    {
        $model = parent::build($promemoria);

        return $model;
    }
}
