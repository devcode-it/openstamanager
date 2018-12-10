<?php

namespace Plugins\ExportFE;

use GuzzleHttp\Client;

/**
 * Classe per l'interazione con API esterne.
 *
 * @since 2.4.3
 */
class Interaction
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
        return false;
    }

    protected function request($type, $resource, $data = [], $options = [])
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

    public static function sendXML($id_record)
    {
        try {
            $fattura = new FatturaElettronica($id_record);
            $file = DOCROOT.'/'.FatturaElettronica::getDirectory().'/'.$fattura->getFilename();

            $response = static::request('POST', 'send', [
                'name' => $fattura->getFilename(),
            ], [
                'multipart' => [
                    [
                        'name'     => 'xml',
                        'contents' => fopen($file, 'r')
                    ],
                ]
            ]);

            $body = $response->getBody();

            if (!empty($body)) {
                return true;
            }
        } catch (UnexpectedValueException $e) {
            return false;
        }

        return true;
    }
}
