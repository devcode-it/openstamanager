<?php

namespace Plugins\ImportFE;

use GuzzleHttp\Client;
use Modules;
use Uploads;

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
                'verify' => false
            ]);
        }

        return self::$client;
    }

    public function isEnabled()
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

    public static function listToImport()
    {
        $directory = FatturaElettronica::getImportDirectory();

        $response = static::request('GET', 'get_fatture_da_importare');
        $body = $response->getBody();

        $list = json_decode($body, true) ?: [];

        $files = glob($directory.'/*.xml');
        foreach ($files as $file) {
            $list[] = basename($file);
        }

        return array_clean($list);
    }

    public static function getImportXML($name)
    {
        $directory = FatturaElettronica::getImportDirectory();
        $file = $directory.'/'.$name;

        if (!file_exists($file)) {
            $response = static::request('GET', 'get_fattura_da_importare', [
                'name' => $name,
            ]);

            $body = $response->getBody();

            FatturaElettronica::store($name, $body);
        }

        return $name;
    }
}
