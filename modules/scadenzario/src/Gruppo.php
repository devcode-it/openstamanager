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

use Carbon\Carbon;
use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Fatture\Fattura;

class Gruppo extends Model
{
    use SimpleModelTrait;

    protected $table = 'co_gruppi_scadenze';

    protected $dates = [
        'data_emissione',
    ];

    public static function build($descrizione, Fattura $fattura = null)
    {
        $model = new static();

        $model->descrizione = $descrizione;

        if (!empty($fattura)) {
            $model->fattura()->associate($fattura);
            $model->data_emissione = $fattura->data;
        } else {
            $model->data_emissione = new Carbon();
        }

        $model->save();

        return $model;
    }

    /**
     * Metodo per la gestione dei trigger alla modifica delle scadenze del gruppo.
     * @param Scadenza $trigger
     */
    public function triggerScadenza(Scadenza $trigger){
        $this->totale_pagato = $this->scadenze()->sum('pagato');
        $this->save();
    }

    /**
     * Metodo per rimuovere completamente le scadenze associate al gruppo.
     * Da utilizzare per le Fatture.
     */
    public function rimuoviScadenze() {
        $this->scadenze->delete();
    }

    public function delete()
    {
        $this->rimuoviScadenze();

        return parent::delete();
    }

    // Relazioni Eloquent

    public function fattura()
    {
        return $this->belongsTo(Fattura::class, 'id_documento');
    }

    public function scadenze()
    {
        return $this->hasMany(Scadenza::class, 'id_gruppo');
    }
}
