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

namespace Modules\Anagrafiche;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Anagrafiche\GeocodingService;

class Sede extends Model
{
    use SimpleModelTrait;

    protected $table = 'an_sedi';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Crea una nuova sede.
     *
     * @return self
     */
    public static function build(?Anagrafica $anagrafica = null, $is_sede_legale = false)
    {
        $model = parent::make();

        if (!empty($is_sede_legale)) {
            $model->nome_sede = 'Sede legale';
        }
        $model->anagrafica()->associate($anagrafica);
        $model->save();

        return $model;
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'id_anagrafica');
    }

    public function nazione()
    {
        return $this->belongsTo(Nazione::class, 'id_nazione');
    }

    #[\Override]
    public function save(array $options = [])
    {
        $this->fixRappresentanteFiscale();

        if (setting('Geolocalizzazione automatica')) {
            $this->geolocalizzazione();
        }

        return parent::save($options);
    }

    // Mutators per il trim automatico dei campi
    public function setIndirizzoAttribute($value)
    {
        $this->attributes['indirizzo'] = trim((string) $value);
    }

    public function setCittaAttribute($value)
    {
        $this->attributes['citta'] = trim((string) $value);
    }

    public function setCapAttribute($value)
    {
        $this->attributes['cap'] = trim((string) $value);
    }

    public function setProvinciaAttribute($value)
    {
        $this->attributes['provincia'] = trim((string) $value);
    }

    public function setTelefonoAttribute($value)
    {
        $this->attributes['telefono'] = trim((string) $value);
    }

    public function setFaxAttribute($value)
    {
        $this->attributes['fax'] = trim((string) $value);
    }

    public function setCellulareAttribute($value)
    {
        $this->attributes['cellulare'] = trim((string) $value);
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = trim((string) $value);
    }

    public function setnome_sede($value)
    {
        $this->attributes['nome_sede'] = trim((string) $value);
    }

    protected function fixRappresentanteFiscale()
    {
        $rappresentante_fiscale = post('is_rappresentante_fiscale');

        if (!empty($rappresentante_fiscale)) {
            self::where('id', $this->id)
                ->where('id', '!=', $this->id)
                ->update([
                    'is_rappresentante_fiscale' => 0,
                ]);

            $this->attributes['is_rappresentante_fiscale'] = $rappresentante_fiscale;
        }
    }

    protected function geolocalizzazione()
    {
        $new_indirizzo = $this->indirizzo.', '.$this->citta.', '.$this->provincia;
        $prev_indirizzo = $this->original['indirizzo'].', '.$this->original['citta'].', '.$this->original['provincia'];

        if (
            !empty($this->indirizzo)
            && !empty($this->citta)
            && !empty($this->provincia)
            && $new_indirizzo != $prev_indirizzo
        ) {
            $indirizzo = $this->indirizzo.', '.$this->citta.', '.$this->provincia;
            $data = GeocodingService::geocode($indirizzo);

            if ($data) {
                $this->gaddress = $data['gaddress'];
                $this->lat = $data['lat'];
                $this->lng = $data['lng'];
            }
        }
    }
}
