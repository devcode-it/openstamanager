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
use Traits\RecordTrait;
class Tipo extends Model
{
    use SimpleModelTrait;
    use RecordTrait;
    protected $table = 'in_tipiintervento';

    protected static $translated_fields = [
        'name',
    ];

    /**
     * Crea un nuovo tipo di intervento.
     *
     * @param string $codice
     * @param string $descrizione
     *
     * @return self
     */
    public static function build($codice = null, $calcola_km = null, $tempo_standard = null, $costo_orario = null, $costo_km = null, $costo_diritto_chiamata = null, $costo_orario_tecnico = null, $costo_km_tecnico = null, $costo_diritto_chiamata_tecnico = null)
    {
        $model = new static();
        $model->codice = $codice;
        $model->calcola_km = $calcola_km;
        $model->tempo_standard = $tempo_standard;
        $model->costo_orario = $costo_orario;
        $model->costo_km = $costo_km;
        $model->costo_diritto_chiamata = $costo_diritto_chiamata;
        $model->costo_orario_tecnico = $costo_orario_tecnico;
        $model->costo_km_tecnico = $costo_km_tecnico;
        $model->costo_diritto_chiamata_tecnico = $costo_diritto_chiamata_tecnico;
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
     * Imposta il tempo standard per il tipo di intervento.
     *
     * @param string $value
     */
    public function setTempoStandardAttribute($value)
    {
        $result = round($value / 2.5, 1) * 2.5;

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
    
    public function getModuleAttribute()
    {
        return '';
    }

    public static function getTranslatedFields(){
        return self::$translated_fields;
    }
}
