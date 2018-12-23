<?php

namespace Modules\Fatture;

use Common\Discount;

class Sconto extends Discount
{
    protected $table = 'co_righe_documenti';

    /**
     * Crea una nuova riga collegata ad una fattura.
     *
     * @param Fattura $fattura
     *
     * @return self
     */
    public static function make(Fattura $fattura)
    {
        $model = parent::make();

        $model->fattura()->associate($fattura);

        return $model;
    }

    public function fattura()
    {
        return $this->belongsTo(Fattura::class, 'iddocumento');
    }
}
