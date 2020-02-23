<?php

namespace Plugins\PianificazioneInterventi\Components;

use Common\Components\Description;
use Modules\Contratti\Contratto;
use Plugins\PianificazioneInterventi\Promemoria;

class Descrizione extends Description
{
    use RelationTrait;

    protected $table = 'co_righe_promemoria';

    /**
     * Crea una nuova riga collegata ad un contratto.
     *
     * @return self
     */
    public static function build(Promemoria $promemoria)
    {
        $model = parent::build($promemoria);

        return $model;
    }
}
