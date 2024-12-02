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

namespace Plugins\AssicurazioneCrediti;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Anagrafiche\Anagrafica;
use Modules\Scadenzario\Scadenza;

/*
 * Classe per la gestione delle assicurazioni crediti.
 *
 * @since 2.4.11
 */
class AssicurazioneCrediti extends Model
{
    use SimpleModelTrait;

    protected $table = 'an_assicurazione_crediti';

    /**
     * Registra una nuova assicurazione crediti.
     *
     * @return self
     */
    public static function build(?Anagrafica $anagrafica = null, $fido_assicurato = null, $data_inizio = null, $data_fine = null)
    {
        $model = new static();

        $model->anagrafica()->associate($anagrafica);
        $model->data_inizio = $data_inizio;
        $model->data_fine = $data_fine;
        $model->fido_assicurato = $fido_assicurato;

        $model->save();

        return $model;
    }

    /**
     * Metodo per ricalcolare il totale utlizzato della dichiarazione.
     */
    public function fixTotale()
    {
        $scadenze = Scadenza::where('idanagrafica', $this->id_anagrafica)->where('scadenza', '>=', $this->data_inizio)->where('scadenza', '<=', $this->data_fine)->get();
        $totale = 0;
        foreach ($scadenze as $scadenza) {
            $totale += $scadenza->da_pagare - $scadenza->pagato;
        }

        $this->totale = $totale;
    }

    // Relazioni Eloquent

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'id_anagrafica');
    }
}
