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

use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\GeocodingService;

if (!function_exists('geolocalizzazione')) {
    function geolocalizzazione($id_record, $is_sede = false)
    {
        $dbo = database();
        $lang = Models\Locale::find(setting('Lingua'))->language_code;

        if ($is_sede) {
            $sede = $dbo->table('an_sedi')->where('id', $id_record)->first();

            if (!empty($sede->indirizzo) && !empty($sede->citta) && !empty($sede->provincia) && empty($sede->lat) && empty($sede->lng)) {
                $indirizzo = $sede->indirizzo.', '.$sede->citta.', '.$sede->provincia;
                $data = GeocodingService::geocode($indirizzo);

                if ($data) {
                    $dbo->update('an_sedi', [
                        'gaddress' => $data['gaddress'],
                        'lat' => $data['lat'],
                        'lng' => $data['lng'],
                    ], ['id' => $sede->id]);
                }
            }
        } else {
            $anagrafica = Anagrafica::find($id_record);
            if (!empty($anagrafica->sedeLegale->indirizzo) && !empty($anagrafica->sedeLegale->citta) && !empty($anagrafica->sedeLegale->provincia) && empty($anagrafica->lat) && empty($anagrafica->lng)) {
                $indirizzo = $anagrafica->sedeLegale->indirizzo.', '.$anagrafica->sedeLegale->citta.', '.$anagrafica->sedeLegale->provincia;
                $data = GeocodingService::geocode($indirizzo);

                if ($data) {
                    $anagrafica->gaddress = $data['gaddress'];
                    $anagrafica->lat = $data['lat'];
                    $anagrafica->lng = $data['lng'];
                    $anagrafica->save();
                }
            }
        }

        return true;
    }
}
