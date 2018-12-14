<?php

namespace Plugins\ImportFE;

use GuzzleHttp\Client;
use Plugins\ExportFE\Connection;

/**
 * Classe per l'interazione con API esterne.
 *
 * @since 2.4.3
 */
class Interaction extends Connection
{
    public static function listToImport()
    {
        $directory = FatturaElettronica::getImportDirectory();

        $response = static::request('POST', 'get_fatture_da_importare');
        $body = static::responseBody($response);

        $list = $body['results'];

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
            $response = static::request('POST', 'get_fattura_da_importare', [
                'name' => $name,
            ]);
            $body = static::responseBody($response);

            FatturaElettronica::store($name, $body['content']);
        }

        return $name;
    }
}
