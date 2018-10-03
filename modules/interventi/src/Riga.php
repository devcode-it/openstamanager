<?php

namespace Modules\Interventi;

use Base\Row;

class Riga extends Row
{
    protected $table = 'in_righe_interventi';

    /**
     * Crea una nuova riga collegata ad un intervento.
     *
     * @param Intervento $intervento
     *
     * @return self
     */
    public static function make(Intervento $intervento)
    {
        $model = parent::make();

        $model->intervento()->associate($intervento);

        return $model;
    }

    public function intervento()
    {
        return $this->belongsTo(Intervento::class, 'idintervento');
    }
}
