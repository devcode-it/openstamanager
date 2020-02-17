<?php

namespace Plugins\PianificazioneInterventi\Components;

use Common\Components\Row;
use Modules\Contratti\Contratto;
use Plugins\PianificazioneInterventi\Promemoria;

class Riga extends Row
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
