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

    /**
     * Crea un nuovo tipo di intervento.
     *
     * @param string $codice
     * @param string $descrizione
     *
     * @return self
     */
    public static function build($codice, $calcola_km, $tempo_standard, $costo_orario, $costo_km, $costo_diritto_chiamata, $costo_orario_tecnico, $costo_km_tecnico, $costo_diritto_chiamata_tecnico)
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

    /**
     * Ritorna l'attributo name del tipo di intervento.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return database()->table($this->table.'_lang')
            ->select('name')
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', \App::getLang())
            ->first()->name;
    }

   /**
     * Imposta l'attributo name del tipo di intervento.
     */
    public function setNameAttribute($value)
    {
        $table = database()->table($this->table.'_lang');

        $translated = $table
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', \App::getLang());

        if ($translated->count() > 0) {
            $translated->update([
                'name' => $value
            ]);
        } else {
            $table->insert([
                'id_record' => $this->id,
                'id_lang' => \App::getLang(),
                'name' => $value
            ]);
        }
    }

    /**
     * Ritorna l'id del tipo di intervento a partire dal nome.
     *
     * @param string $name il nome da ricercare
     *
     * @return \Illuminate\Support\Collection
     */
    public function getByName($name)
    {
        return database()->table($this->table.'_lang')
            ->select('id_record')
            ->where('name', '=', $name)
            ->where('id_lang', '=', \App::getLang())
            ->first();
    }
}
