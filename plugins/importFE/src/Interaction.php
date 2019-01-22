<?php

namespace Plugins\ImportFE;

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

        $code = $body['code'];

        if($code=='200'){
            $list = $body['results'];

            $files = glob($directory.'/*.xml');
            foreach ($files as $file) {
                $list[] = basename($file);
            }

            return array_clean($list);
        }
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

    public static function processXML($filename)
    {
            $response = static::request('POST', 'process_xml', [
                'filename' => $filename,
            ]);

            $body = static::responseBody($response);

            if($body['processed']=='0'){
                $message = $body['code']." - ".$body['message'];
            }else{
                $message = "";
            }

        return $message;
    }
}
