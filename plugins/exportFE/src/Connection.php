<?php

namespace Plugins\ExportFE;

use GuzzleHttp\Client;

/**
 * Classe per l'interazione con API esterne.
 *
 * @since 2.4.3
 */
class Connection
{
    protected static $client = null;

    public static function isEnabled()
    {
        return !empty(setting('OSMCloud Services API Token'));
    }

    public static function request($type, $resource, $data = [], $options = [])
    {
        $client = static::getClient();

        $json = array_merge($data, [
            'token' => setting('OSMCloud Services API Token'),
            'resource' => $resource,
        ]);

        $options = array_merge($options, [
            'json' => $json,
            'http_errors' => false,
        ]);

        return $client->request($type, '', $options);
    }

    public static function responseBody($response)
    {
        $body = $response->getBody();

        return json_decode($body, true) ?: [];
    }

    /**
     * Restituisce l'oggetto per la connessione all'API del progetto.
     *
     * @return Client
     */
    protected static function getClient()
    {
        if (!isset(self::$client)) {
            self::$client = new Client([
                'base_uri' => 'https://services.osmcloud.it/v1/',
                'verify' => false,
            ]);
        }

        return self::$client;
    }
}
