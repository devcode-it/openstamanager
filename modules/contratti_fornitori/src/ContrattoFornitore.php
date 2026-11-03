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

namespace Modules\ContrattiFornitori;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Anagrafiche\Anagrafica;
use Traits\RecordTrait;

class ContrattoFornitore extends Model
{
    use RecordTrait;
    use SimpleModelTrait;

    protected $table = 'ac_contratti_fornitori';

    protected $casts = [
        'data_stipula' => 'date',
        'data_inizio' => 'date',
        'data_scadenza' => 'date',
        'data_limite_disdetta' => 'date',
        'rinnovo_automatico' => 'boolean',
        'importo' => 'decimal:2',
    ];

    protected $guarded = [];

    public function getModuleAttribute(): string
    {
        return 'Contratti fornitori';
    }

    public function fornitore()
    {
        return $this->belongsTo(Anagrafica::class, 'id_fornitore');
    }

    public function referenteInterno()
    {
        return $this->belongsTo(Anagrafica::class, 'idagente');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'id_stato');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function precedente()
    {
        return $this->belongsTo(self::class, 'id_contratto_precedente');
    }

    public function successivo()
    {
        return $this->belongsTo(self::class, 'id_contratto_successivo');
    }
}
