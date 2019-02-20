<?php

namespace Plugins\ReceiptFE;

use Plugins\ExportFE\Connection;

/**
 * Classe per l'interazione con API esterne.
 *
 * @since 2.4.3
 */
class Interaction extends Connection
{
    public static function getReceiptList()
    {
        $response = static::request('POST', 'notifiche_da_importare');
        $body = static::responseBody($response);

        return $body['results'];
    }

    public static function getReceipt($name)
    {
        $response = static::request('POST', 'notifica_da_importare', [
            'name' => $name,
        ]);
        $body = static::responseBody($response);

        return $body['content'];
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
