<?php

namespace Modules\Anagrafiche;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Models\Locale;

class GeocodingService
{
    protected static $client;

    public static function geocodeOpenStreetMap($indirizzo, $lang)
    {
        try {
            $client = self::getClient();
            $url = 'https://nominatim.openstreetmap.org/search.php?q='.urlencode($indirizzo).'&format=jsonv2&accept-language='.$lang;

            $response = $client->get($url);
            $body = $response->getBody();
            $data = json_decode($body);

            if (!is_array($data) || empty($data)) {
                return null;
            }

            return [
                'gaddress' => $data[0]->display_name,
                'lat' => $data[0]->lat,
                'lng' => $data[0]->lon,
            ];
        } catch (ConnectException $e) {
            flash()->error(tr('Impossibile connettersi al servizio di geolocalizzazione: ').$e->getMessage());

            return null;
        } catch (RequestException $e) {
            flash()->error(tr('Errore nella richiesta di geolocalizzazione: ').$e->getMessage());

            return null;
        } catch (\Exception $e) {
            flash()->error(tr('Errore durante la geolocalizzazione: ').$e->getMessage());

            return null;
        }
    }

    public static function geocodeGoogleMaps($indirizzo, $apiKey)
    {
        try {
            $client = self::getClient();
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($indirizzo).'&key='.$apiKey;

            $response = $client->get($url);
            $body = $response->getBody();
            $data = json_decode($body, true);

            if (!is_array($data) || $data['status'] != 'OK' || empty($data['results'])) {
                return null;
            }

            return [
                'gaddress' => $data['results'][0]['formatted_address'],
                'lat' => $data['results'][0]['geometry']['location']['lat'],
                'lng' => $data['results'][0]['geometry']['location']['lng'],
            ];
        } catch (ConnectException $e) {
            flash()->error(tr('Impossibile connettersi al servizio di geolocalizzazione: ').$e->getMessage());

            return null;
        } catch (RequestException $e) {
            flash()->error(tr('Errore nella richiesta di geolocalizzazione: ').$e->getMessage());

            return null;
        } catch (\Exception $e) {
            flash()->error(tr('Errore durante la geolocalizzazione: ').$e->getMessage());

            return null;
        }
    }

    public static function geocode($indirizzo, $provider = null)
    {
        if (empty($provider)) {
            $provider = setting('Gestore mappa');
        }

        if (empty($indirizzo)) {
            return null;
        }

        $lang = Locale::find(setting('Lingua'))->language_code;

        if ($provider == 'OpenStreetMap') {
            return self::geocodeOpenStreetMap($indirizzo, $lang);
        } elseif ($provider == 'Google Maps') {
            $apiKey = setting('Google Maps API key per Tecnici');
            if (empty($apiKey)) {
                flash()->error(tr('Google Maps API key non configurata.'));

                return null;
            }

            return self::geocodeGoogleMaps($indirizzo, $apiKey);
        }

        return null;
    }

    protected static function getClient()
    {
        if (!isset(self::$client)) {
            self::$client = new Client([
                'timeout' => 10,
                'connect_timeout' => 5,
                'headers' => [
                    'User-Agent' => 'OpenSTAManager',
                ],
            ]);
        }

        return self::$client;
    }
}