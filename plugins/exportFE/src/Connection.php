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

    public static function isEnabled()
    {
        return !empty(setting('OSMCloud Services API Token'));
    }

    protected function request($type, $resource, $data = [], $options = [])
    {
        $client = static::getClient();

        $json = array_merge($data, [
            'token' => setting('OSMCloud Services API Token'),
            'resource' => $resource,
        ]);

        if (!empty($options['multipart'])) {
            foreach ($json as $key => $value) {
                $options['multipart'][] = [
                    'name' => $key,
                    'contents' => $value,
                ];
            }
        } else {
            $options['json'] = $json;
        }

        $options = array_merge($options, [
            'http_errors' => false,
        ]);

        return $client->request($type, '', $options);
    }

    protected function responseBody($response)
    {
        $body = $response->getBody();

        return json_decode($body, true) ?: [];
    }
}
