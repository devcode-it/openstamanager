<?php

namespace Modules\Scadenzario;

use Common\Model;
use Modules\Fatture\Fattura;

class Scadenza extends Model
{
    protected $table = 'co_scadenziario';

    protected $dates = [
        'scadenza',
        'data_pagamento',
    ];

    public static function build($descrizione, $importo, $data_scadenza, $type = 'fattura', $is_pagato = false)
    {
        $model = parent::build();

        $model->descrizione = $descrizione;
        $model->scadenza = $data_scadenza;
        $model->da_pagare = $importo;
        $model->tipo = $type;

        $model->pagato = $is_pagato ? $importo : 0;
        $model->data_pagamento = $is_pagato ? $data_scadenza : null;

        $model->save();

        return $model;
    }

    public function documento()
    {
        return $this->belongsTo(Fattura::class, 'iddocumento');
    }
}
