<?php

namespace Plugins\ImportFE;

use API\Services;

/**
 * Classe per l'interazione con API esterne.
 *
 * @since 2.4.3
 */
class Interaction extends Services
{
    public static function listToImport()
    {
        $directory = FatturaElettronica::getImportDirectory();

        $list = [];

        $files = glob($directory.'/*.xml*');
        foreach ($files as $file) {
            $list[] = basename($file);
        }

        // Ricerca da remoto
        if (self::isEnabled()) {
            $response = static::request('POST', 'fatture_da_importare');
            $body = static::responseBody($response);

            if ($body['status'] == '200') {
                $files = $body['results'];

                foreach ($files as $file) {
                    $list[] = basename($file);
                }
            }
        }

        return array_clean($list);
    }

    public static function getImportXML($name)
    {
        $directory = FatturaElettronica::getImportDirectory();
        $file = $directory.'/'.$name;

        if (!file_exists($file)) {
            $response = static::request('POST', 'fattura_da_importare', [
                'name' => $name,
            ]);
            $body = static::responseBody($response);

            FatturaElettronica::store($name, $body['content']);
        }

        return $name;
    }

    public static function processXML($filename)
    {
        $response = static::request('POST', 'fattura_xml_salvata', [
                'filename' => $filename,
            ]);

        $body = static::responseBody($response);

        $message = '';
        if ($body['status'] != '200') {
            $message = $body['status'].' - '.$body['message'];
        }

        return $message;
    }
}
