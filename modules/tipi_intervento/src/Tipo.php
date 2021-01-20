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

namespace Modules\TipiIntervento;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Anagrafiche\Anagrafica;

class Tipo extends Model
{
    use SimpleModelTrait;

    protected $table = 'in_tipiintervento';
    protected $primaryKey = 'idtipointervento';

    /**
     * Crea un nuovo tipo di intervento.
     *
     * @param string $codice
     * @param string $descrizione
     * @param string $tempo_standard
     *
     * @return self
     */
    public static function build($codice, $descrizione)
    {
        $model = new static();

        $model->codice = $codice;
        $model->descrizione = $descrizione;

        // Salvataggio delle informazioni
        $model->save();

        return $model;
    }

    public function fixTecnici()
    {
        // Fix per le relazioni con i tecnici
        $tecnici = Anagrafica::fromTipo('Tecnico')->get();
        foreach ($tecnici as $tecnico) {
            Anagrafica::fixTecnico($tecnico);
        }
    }

    /**
     * Restituisce l'identificativo.
     *
     * @return string
     */
    public function getIdAttribute()
    {
        return $this->idtipointervento;
    }

    /**
     * Imposta il tempo stamdard per il tipo di intervento.
     *
     * @param string $value
     */
    public function setTempoStandardAttribute($value)
    {
        $result = round(($value / 2.5), 1) * 2.5;

        $this->attributes['tempo_standard'] = $result;
    }

    public function preventivi()
    {
        return $this->hasMany(Preventivo::class, 'idtipointervento');
    }

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'idtipointervento');
    }
}
