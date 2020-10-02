<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Modules\Scadenzario;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Fatture\Fattura;

class Scadenza extends Model
{
    use SimpleModelTrait;

    protected $table = 'co_scadenziario';

    protected $dates = [
        'scadenza',
        'data_pagamento',
    ];

    public static function build($descrizione, $importo, $data_scadenza, $type = 'fattura', $is_pagato = false)
    {
        $model = new static();

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
