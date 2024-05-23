<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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
use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Fattura;
use Modules\Pagamenti\Pagamento;

class Scadenza extends Model
{
    use SimpleModelTrait;

    protected $table = 'co_scadenziario';

    protected $dates = [
        'scadenza',
        'data_pagamento',
    ];

    public static function build($idanagrafica = null, $descrizione = null, $importo = null, $data_scadenza = null, $id_pagamento = null, $id_banca_azienda = null, $id_banca_controparte = null, $type = 'fattura', $is_pagato = false)
    {
        $model = new static();

        $model->idanagrafica = $idanagrafica;
        $model->descrizione = $descrizione;
        $model->scadenza = $data_scadenza;
        $model->da_pagare = $importo;
        $model->tipo = $type;
        $model->id_pagamento = $id_pagamento;
        $model->id_banca_azienda = $id_banca_azienda;
        $model->id_banca_controparte = $id_banca_controparte;

        $model->pagato = $is_pagato ? $importo : 0;
        $model->data_pagamento = $is_pagato ? $data_scadenza : null;

        $model->save();

        return $model;
    }

    public function documento()
    {
        return $this->belongsTo(Fattura::class, 'iddocumento');
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function pagamento()
    {
        return $this->belongsTo(Pagamento::class, 'id_pagamento');
    }
}
