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

namespace API;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Models\Cache;

/**
 * Classe per l'interazione con API esterne.
 *
 * @since 2.4.3
 */
class Services
{
    protected static $client;

    /**
     * Controlla se il gestionale ha accesso a Services.
     *
     * @return bool
     */
    public static function isEnabled()
    {
        return !empty(setting('OSMCloud Services API Token'));
    }

    /**
     * Restituisce le informazioni disponibili su Services.
     *
     * @return array
     */
    public static function getInformazioni($force = false)
    {
        try {
            $response = self::request('GET', 'info');
            $content = self::responseBody($response);

            // Verifica che la risposta contenga i dati attesi
            if (!is_array($content) || !isset($content['risorse-api'])) {
                return ['risorse-api' => []];
            }

            return $content;
        } catch (\Exception $e) {
            // Log dell'errore per debug
            if (function_exists('logger')) {
                logger()->error('Errore nel recupero informazioni Services: ' . $e->getMessage());
            }

            // Restituisce un array vuoto in caso di errore
            return ['risorse-api' => []];
        }
    }

    /**
     * Restituisce i servizi attivi attraverso Services.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getServiziAttivi($force = false)
    {
        return collect(self::getInformazioni($force)['risorse-api']);
    }

    /**
     * Restituisce i servizi in scadenza per data di conclusione prossima.
     *
     * @param Carbon $limite_scadenze
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getServiziInScadenza($limite_scadenze)
    {
        return self::getServiziAttivi()
            ->filter(fn ($item) => is_array($item) && isset($item['expiration_at']) && Carbon::parse($item['expiration_at'])->greaterThan(Carbon::now()) && Carbon::parse($item['expiration_at'])->lessThan($limite_scadenze));
    }

    /**
     * Restituisce i servizi scaduti.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getServiziScaduti()
    {
        return self::getServiziAttivi()
            ->filter(fn ($item) => is_array($item) && isset($item['expiration_at']) && Carbon::parse($item['expiration_at'])->lessThan(Carbon::now()));
    }

    /**
     * Restituisce le risorse attive in Services.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getRisorseAttive($force = false)
    {
        return collect(self::getInformazioni($force)['risorse-api']);
    }

    /**
     * Controlla se il gestionale ha accesso a una specifica risorsa di Services.
     *
     * @return bool
     */
    public static function verificaRisorsaAttiva($servizio)
    {
        return self::isEnabled() && self::getRisorseAttive()->search(fn ($item) => is_array($item) && isset($item['name']) && $item['name'] == $servizio) !== false;
    }

    /**
     * Restituisce le risorse in scadenza per assenza di crediti oppure per data di fine prossima.
     *
     * @param Carbon $limite_scadenze
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getRisorseInScadenza($limite_scadenze)
    {
        return self::getRisorseAttive()
            ->filter(fn ($item) => is_array($item) && ((isset($item['expiration_at']) && Carbon::parse($item['expiration_at'])->greaterThan(Carbon::now()) && Carbon::parse($item['expiration_at'])->lessThan($limite_scadenze))
                || (isset($item['credits']) && $item['credits'] < 100)));
    }

    /**
     * Restituisce le risorse scadute per assenza di crediti oppure per data di fine prossima.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getRisorseScadute()
    {
        return self::getRisorseAttive()
            ->filter(fn ($item) => is_array($item) && ((isset($item['expiration_at']) && Carbon::parse($item['expiration_at'])->lessThan(Carbon::now()))
                || (isset($item['credits']) && $item['credits'] < 0)));
    }

    /**
     * Effettua una richiesta a Services.
     *
     * @param array $data
     * @param array $options
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public static function request($type, $resource, $data = [], $options = [])
    {
        $client = static::getClient();

        $json = array_merge($data, [
            'token' => setting('OSMCloud Services API Token'),
            'version' => setting('OSMCloud Services API Version') ?: 'v3',
            'resource' => $resource,
        ]);

        $options = array_merge($options, [
            'json' => $json,
            'http_errors' => false,
        ]);

        return $client->request($type, '', $options);
    }

    /**
     * Restituisce il corpo JSON della risposta in array.
     *
     * @return array
     */
    public static function responseBody($response)
    {
        $body = $response->getBody();

        return json_decode((string) $body, true) ?: [];
    }

    /**
     * Restituisce l'oggetto per la connessione all'API del progetto.
     *
     * @return Client
     */
    protected static function getClient()
    {
        if (!isset(self::$client)) {
            $url = setting('OSMCloud Services API URL') ?: 'https://services.osmcloud.it/api/';

            self::$client = new Client([
                'base_uri' => $url,
                'verify' => false,
            ]);
        }

        return self::$client;
    }
}
