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
use Models\User;
use Util\FileSystem;

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
            $cache = Cache::where('name', 'Informazioni su Services')->first();

            // Aggiornamento dei contenuti della cache
            if (!$cache->isValid() || $force || empty($cache->content)) {
                // Calcolo spazio occupato
                $spazio_occupato = self::calcolaSpazioOccupato();

                // Conteggio utenti attivi
                $utenti_attivi = self::contaUtentiAttivi();

                // Recupero ultimi 100 accessi
                $ultimi_accessi = self::getUltimiAccessi();

                $response = self::request('GET', 'info', [
                    'spazio_occupato' => $spazio_occupato,
                    'utenti_attivi' => $utenti_attivi,
                    'versione' => \Update::getVersion(),
                    'ultimi_accessi' => $ultimi_accessi,
                    'sync_at' => Carbon::now()->toDateTimeString(),
                    'url_installazione' => setting('Base URL'),
                ]);
                $content = self::responseBody($response);
                $cache->set($content);

                return $content;
            }

            return $cache->content;
        } catch (\Exception $e) {
            // Log dell'errore per debug
            if (function_exists('logger')) {
                logger()->error('Errore nel recupero informazioni Services: '.$e->getMessage());
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
        return collect(self::getInformazioni($force)['servizi']);
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
            ->filter(fn ($item) => is_array($item) && isset($item['data_conclusione']) && Carbon::parse($item['data_conclusione'])->greaterThan(Carbon::now()) && Carbon::parse($item['data_conclusione'])->lessThan($limite_scadenze));
    }

    /**
     * Restituisce i servizi scaduti.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getServiziScaduti()
    {
        return self::getServiziAttivi()
            ->filter(fn ($item) => is_array($item) && isset($item['data_conclusione']) && Carbon::parse($item['data_conclusione'])->lessThan(Carbon::now()));
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

    /**
     * Calcola lo spazio occupato dal sistema.
     *
     * @return int Spazio occupato in bytes
     */
    protected static function calcolaSpazioOccupato()
    {
        try {
            // Prova a recuperare dalla cache
            $cache = Cache::where('name', 'Spazio utilizzato')->first();
            if ($cache && $cache->isValid()) {
                return (int) $cache->content;
            }
            $osm_size = FileSystem::folderSize(base_dir(), ['htaccess']);

            return $osm_size;
        } catch (\Exception) {
            // In caso di errore, restituisce 0
            return 0;
        }
    }

    /**
     * Conta gli utenti attivi nel sistema.
     *
     * @return int Numero di utenti attivi
     */
    protected static function contaUtentiAttivi()
    {
        try {
            $result = User::where('enabled', 1)->count();

            return (int) $result;
        } catch (\Exception) {
            // In caso di errore, restituisce 0
            return 0;
        }
    }

    /**
     * Recupera gli ultimi 100 accessi.
     *
     * @return string JSON
     */
    protected static function getUltimiAccessi()
    {
        $database = database();
        $logs = $database->fetchArray('SELECT username, ip, created_at FROM zz_logs ORDER BY created_at DESC LIMIT 100');

        return json_encode($logs);
    }
}
