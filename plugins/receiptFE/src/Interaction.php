<?php

namespace Plugins\ReceiptFE;

use API\Services;

/**
 * Classe per l'interazione con API esterne.
 *
 * @since 2.4.3
 */
class Interaction extends Services
{
    public static function getReceiptList()
    {
        $directory = Ricevuta::getImportDirectory();

        $list = [];

        $files = glob($directory.'/*.xml*');
        foreach ($files as $file) {
            $list[] = basename($file);
        }

        // Ricerca da remoto
        if (self::isEnabled()) {
            $response = static::request('POST', 'notifiche_da_importare');
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

    public static function getReceipt($name)
    {
        $directory = Ricevuta::getImportDirectory();
        $file = $directory.'/'.$name;

        if (!file_exists($file)) {
            $response = static::request('POST', 'notifica_da_importare', [
                'name' => $name,
            ]);
            $body = static::responseBody($response);

            Ricevuta::store($name, $body['content']);
        }

        return $name;
    }

    public static function processReceipt($filename)
    {
        $response = static::request('POST', 'notifica_xml_salvata', [
            'filename' => $filename,
        ]);
        $body = static::responseBody($response);

        $result = true;
        if ($body['status'] != '200') {
            $result = $body['status'].' - '.$body['message'];
        }

        return $result;
    }
}
