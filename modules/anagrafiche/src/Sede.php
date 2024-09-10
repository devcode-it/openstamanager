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
use Geocoder\Provider\GoogleMaps;
use Illuminate\Database\Eloquent\Model;
use Ivory\HttpAdapter\CurlHttpAdapter;

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
            $model->nomesede = 'Sede legale';
        }
        $model->anagrafica()->associate($anagrafica);
        $model->save();

        return $model;
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function nazione()
    {
        return $this->belongsTo(Nazione::class, 'id_nazione');
    }

    public function save(array $options = [])
    {
        $this->fixRappresentanteFiscale();

        if (setting('Geolocalizzazione automatica')) {
            $this->geolocalizzazione();
        }

        return parent::save($options);
    }

    protected function fixRappresentanteFiscale()
    {
        $rappresentante_fiscale = post('is_rappresentante_fiscale');

        if (!empty($rappresentante_fiscale)) {
            self::where('idanagrafica', $this->idanagrafica)
                ->where('id', '!=', $this->id)
                ->update([
                    'is_rappresentante_fiscale' => 0,
                ]);

            $this->attributes['is_rappresentante_fiscale'] = $rappresentante_fiscale;
        }
    }

    protected function geolocalizzazione()
    {
        if (!empty($this->indirizzo) && !empty($this->citta) && !empty($this->provincia) && empty($this->gaddress)) {
            $indirizzo = urlencode($this->indirizzo.', '.$this->citta.', '.$this->provincia);

            if (setting('Gestore mappa') == 'OpenStreetMap') {
                // TODO: da riscrivere con Guzzle e spostare su hook
                if (!function_exists('curl_init')) {
                    // cURL non è attivo
                    flash()->error(tr('cURL non attivo, impossibile continuare l\'operazione.'));

                    return false;
                } else {
                    $ch = curl_init();
                }
                $url = 'https://nominatim.openstreetmap.org/search.php?q='.$indirizzo.'&format=jsonv2&accept-language='.$lang;
                $user_agent = 'traccar';
                curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $data = json_decode(curl_exec($ch));
                curl_close($ch);

                // Salvataggio informazioni
                $this->gaddress = $data[0]->display_name;
                $this->lat = $data[0]->lat;
                $this->lng = $data[0]->lon;
            } elseif (setting('Gestore mappa') == 'Google Maps') {
                $curl = new CurlHttpAdapter();
                $geocoder = new GoogleMaps($curl, 'IT-it', null, true, $google);

                // Ricerca indirizzo
                $address = $geocoder->geocode($indirizzo)->first();
                $coordinates = $address->getCoordinates();

                // Salvataggio informazioni
                $this->gaddress = $data[0]->display_name;
                $this->lat = $coordinates->getLatitude();
                $this->lng = $coordinates->getLongitude();
            }
        }
    }
}
