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

if (!function_exists('geolocalizzazione')) {
    function geolocalizzazione($id_record, $is_sede = false)
    {
        $dbo = database();

        if( $is_sede ){
            $sede = $dbo->table('an_sedi')->where('id',$id_record)->first();

            if (!empty($sede->indirizzo) && !empty($sede->citta) && !empty($sede->provincia) && empty($sede->lat) && empty($sede->lng)) {
                $indirizzo = urlencode($sede->indirizzo.', '.$sede->citta.', '.$sede->provincia);

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
                $dbo->update('an_sedi', [
                    'gaddress' => $data[0]->display_name,
                    'lat' => $data[0]->lat,
                    'lng' => $data[0]->lon,
                ],['id' => $sede->id]);
            }
        }else{
            $anagrafica = Anagrafica::find($id_record);
            if (!empty($anagrafica->sedeLegale->indirizzo) && !empty($anagrafica->sedeLegale->citta) && !empty($anagrafica->sedeLegale->provincia) && empty($anagrafica->lat) && empty($anagrafica->lng)) {
                $indirizzo = urlencode($anagrafica->sedeLegale->indirizzo.', '.$anagrafica->sedeLegale->citta.', '.$anagrafica->sedeLegale->provincia);

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
                $anagrafica->gaddress = $data[0]->display_name;
                $anagrafica->lat = $data[0]->lat;
                $anagrafica->lng = $data[0]->lon;
                $anagrafica->save();
            }
        }

        return true;
    }
}